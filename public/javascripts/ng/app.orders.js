
var app = angular.module('sisyphus', ['sisyphus.helpers', 'sisyphus.helpers.isbnHyphenate', 'smart-table']);

stripHyphens = function(isbn13) {
    if (!isbn13) return isbn13;
    return isbn13.replace(/[\s-]/g, "");
};

app.service("CartService", function () {
    this.cartBooks = [];
});

app.directive('bookEditor', function() {
    return {
        restrict: 'E',
        scope: {
            book: '='
        },
        templateUrl: '/javascripts/ng/templates/bookEditor.html'
    };
});

var ISBN13_REGEXP = /^\d{13}$/;
app.directive('isbn13', function() {
    return {
        require: 'ngModel',
        link: function(scope, elm, attrs, ctrl) {
            ctrl.$validators.isbn13 = function(modelValue, viewValue) {

                if (ctrl.$isEmpty(modelValue)) {
                    // consider empty models to be valid
                    return true;
                }
                viewValue = stripHyphens(viewValue);
                if (ISBN13_REGEXP.test(viewValue)) {
                    // it is valid
                    return true;
                }
                // it is invalid
                return false;
            };
        }
    };
});



app.directive('bookDetails', function($http) {
    return {
        restrict: 'E',
        transclude: true,
        scope: {
            book: '=',
            hideImage: '='
        },
        templateUrl: '/javascripts/ng/templates/bookDetails.html',
        link: function(scope, element, attrs) {

            $(element).find(".smallImage").on('mouseover', function(){
                $(element).find(".largeImage").fadeIn();
            });
            $(element).find(".largeImage").on('mouseleave',function(){
                $(element).find(".largeImage").fadeOut();
            });

            scope.lastIsbn = '';
            scope.getBookCoverImage = function() {
                if (!scope.book)
                    return;

                var isbn = scope.book.isbn13;
                if (!isbn){
                    return scope.thumbnail = noBookThumb;
                }
                if (isbn != scope.lastIsbn)
                {
                    scope.lastIsbn = isbn;
                    scope.thumbnail = '/books/cover?isbn=' + isbn;
                }
                else {
                    return scope.thumbnail;
                }
            }
        }
    }
});


app.controller('OrdersController', ['$scope', '$http', 'CartService', 'BreadcrumbService', 'HelpService',
    function($scope, $http, CartService, BreadcrumbService, HelpService){

    $scope.courses = [];
	$scope.STAGE_SELECT_COURSE = 1;
	$scope.STAGE_SELECT_BOOKS  = 2;
	$scope.STAGE_REVIEW_ORDERS = 3;
	$scope.STAGE_ORDER_SUCCESS = 4;

    $scope.setCourses = function (courses) {
        $scope.courses = courses;
		HelpService.addCourseHelpOption(courses);
    };

    $scope.cartBooks = CartService.cartBooks;

    $scope.stage = $scope.STAGE_SELECT_COURSE;

    $scope.getStage = function(){
        return $scope.stage;
    };

    $scope.setStage = function(stage){
        $scope.stage = stage;

        if ($scope.stage == $scope.STAGE_SELECT_COURSE) {
			HelpService.addCourseHelpOption($scope.courses);
        }
        else if ($scope.stage == $scope.STAGE_SELECT_BOOKS) {
			HelpService.addBookHelpOption([]);
        }
    };



    $scope.placeRequestForCourse = function(course) {
        $scope.setStage($scope.STAGE_SELECT_BOOKS);

        $scope.selectedCourse = course;

        BreadcrumbService.clear();
        BreadcrumbService.push($scope.$eval(
            '(listing = selectedCourse.listings[0]).department + " " + (listing.number | zpad:3) + "-" + (listing.section | zpad:2) + " " + listing.name'
        ));

        $http.get('/requests/past-courses/' + course.course_id).then(
            function success(response) {
                var pastCourses = response.data;

                course['pastBooks'] = Enumerable
                    .From(pastCourses)
                    // Select an object for each order that has the course, the order, and the book on it.
                    .SelectMany("$.orders", "course,order => {course:course, order:order, book:order.book}")
                    .Where(function(bookGrouping) {
                        if($scope.passedBookId) {
                            return bookGrouping.book.book_id != $scope.passedBookId;
                        } else {
                            return true;
                        }

                    })
                    // Group these objects by the book id, selecting a new object for each book that
                    // contains that book and the collection of the previously selected objects
                    // that belong to each book.
                    .GroupBy("$.book.book_id", "", function (key, bookGroupings) {
                        // For each book, associate a list of terms for which that book was ordered.
                        return {
                            book: bookGroupings.First().book,
                            terms: bookGroupings
                                // Grab only one object for each term, per user
                                .Distinct("bookGrouping => '' + bookGrouping.course.user_id + ' ' + bookGrouping.course.term_id")
                                // Count the number of sections that each user ordered the book for
                                // during this term, and associate that with the bookGrouping
                                // for that user/term combination.
                                .Do(function (termUserBookGrouping) {
                                    termUserBookGrouping['numSections'] = bookGroupings
                                        .Count(function (bookGrouping) {
                                            return bookGrouping.course.user_id == termUserBookGrouping.course.user_id
                                                && bookGrouping.course.term_id == termUserBookGrouping.course.term_id
                                        })
                                })
                                // For each term/user combination,
                                // group by the term and associate all the groupings for that term as 'orderData'
                                .GroupBy("$.course.term_id", "", "{term:$$.First().course.term, orderData: $$.ToArray()}")
                                .OrderByDescending('$.term.term_id')
                                .ToArray()
                        }
                    })
                    .OrderByDescending("$.terms[0].term_id")
                    .ToArray();
            }
        );
    };

    $scope.deleteOrder = function(course, order)
    {
        $http.post('/requests/delete/' + order.order_id).then(
            function success(response){
                course.orders.splice(course.orders.indexOf(order), 1);
            });
    };

    $scope.courseNeedsOrders = function(course)
    {
        return course.orders.length == 0 && !course.no_book
    };

    $scope.noBook = function(course)
    {
        $http.post('/requests/no-book', {course_id: course.course_id}).then(
            function success(response){
                course.no_book=true;
                course.orders = [];
            });
    };


    $scope.deleteBookFromCart = function(bookData) {
        var index = $scope.cartBooks.indexOf(bookData);
        if (index > -1) {
            $scope.cartBooks.splice(index, 1);
            if (bookData.book.book_id) {
                $scope.selectedCourse['pastBooks'].push(bookData);
            }
        }
    };

    $scope.addBookToCart = function(bookData) {
        var index = $scope.selectedCourse['pastBooks'].indexOf(bookData);
        if (index > -1) {
            $scope.selectedCourse['pastBooks'].splice(index, 1);
            $scope.cartBooks.push(bookData);
        }
    };

    $scope.addPassedBookToCart = function(book) { // isbn was passed in from book details page
        if(book.length != 0) {
            $scope.master = {};
            $scope.master['title'] = book[0].title;
            $scope.master['edition'] = book[0].edition;
            $scope.master['publisher'] = book[0].publisher;
            $scope.master['authors'] = book[0].authors;
            $scope.master['orders'] = book[0].orders;
            $scope.master['book_id'] = book[0].book_id;
            $scope.passedBookId = book[0].book_id;
            var bookData = {};
            bookData['book'] = $scope.master;
            bookData['book']['isbn13'] = stripHyphens(book[0].isbn13);
            CartService.cartBooks.push(bookData);
        }
    };

    $scope.submitOrders = function(form) {
        $scope.submitted = true;

        if (!$scope.submitting && form.$valid) {
            var sections = [];
            sections.push($scope.selectedCourse);

            if ($scope.additionalCourses != null) {
                for (var i = 0; i < $scope.additionalCourses.length; i++){
                    sections.push($scope.additionalCourses[i]);
                }
            }

            $scope.submitting = true;
            $http.post('/requests/submit-order', {courses:sections, cart:CartService.cartBooks}).then(
                function success(response){
                    $scope.additionalCourses = null;
                    $scope.submitting = false;
                    $scope.orderResults = response.data.orderResults;

                    $scope.setStage($scope.STAGE_ORDER_SUCCESS);
                },
                function failure(){
                    $scope.orderResults = null;
                    $scope.submitting = false;
                });
        }
    };

    $scope.orderWasACompleteFailure = function(){
        if (!$scope.orderResults)
            return false;

        return Enumerable
            .From($scope.orderResults)
            .SelectMany()
            .All(function(courseOrder){
                return courseOrder.notPlaced && !$scope.ordersAreEffectivelyEqual(courseOrder.order, courseOrder.newOrder)
            })
    };

    $scope.orderWasACompleteSuccess = function(){
        if (!$scope.orderResults)
            return true;

        return !Enumerable
            .From($scope.orderResults)
            .SelectMany()
            .Any(function(courseOrder){
                return courseOrder.notPlaced && !$scope.ordersAreEffectivelyEqual(courseOrder.order, courseOrder.newOrder)
            })
    };

    $scope.ordersAreEffectivelyEqual = function(order1, order2){
        return order1.notes == order2.notes && order1.required == order2.required;
    };

    $scope.toggleAdditionalCourseSelected = function(course){
        if ($scope.additionalCourses == null)
            $scope.additionalCourses = [];

        var existingItemIndex = $scope.additionalCourses.indexOf(course);

        if (existingItemIndex >= 0)
            $scope.additionalCourses.splice(existingItemIndex, 1);
        else
            $scope.additionalCourses.push(course);
    };

    $scope.isAdditionalCourseSelected = function(course){
        if ($scope.additionalCourses == null)
            return false;

        return $scope.additionalCourses.indexOf(course) >= 0;
    };

    $scope.isCourseSimilarToSelected = function(course)
    {
        if ($scope.selectedCourse == null)
        {
            return true;
        }

        if (course.term_id != $scope.selectedCourse.term_id || course.course_id == $scope.selectedCourse.course_id)
            return false;

        return Enumerable
            .From(course.listings)
            .Any(function(listing){
                return Enumerable
                    .From($scope.selectedCourse.listings)
                    .Any(function(listing2){
                        return listing.department == listing2.department
                        && listing.number == listing2.number
                        && listing.section != listing2.section
                    })
            });
    };

    $scope.getNumAdditionalCoursesSelected = function(){
        if ($scope.additionalCourses == null)
            return 0;

        return $scope.additionalCourses.length;
    };
}]);



app.controller("NewBookController", ["$scope", "$http", "CartService", function($scope, $http, CartService) {
    $scope.authors = [];
    $scope.master = {};
    $scope.book = {};
    $scope.submitted = false;
    $scope.isAutoFilled = false;


    $scope.addAuthor = function(author) {
        $scope.authors.push({name: ""});

    };

    $scope.isbnChanged = function(book) {
        if ($scope.isAutoFilled) {
            $scope.isAutoFilled = false;
            $scope.autofilledBook = null;
            $scope.authors = [];
            $scope.book = angular.copy($scope.master);
            $scope.book['isbn13'] = book['isbn13'];
        }
        var stripped = stripHyphens(book['isbn13']);
        if (ISBN13_REGEXP.test(stripped)) {
            $scope.autoFill();
        }
    };

    $scope.autoFill = function() {
        if ($scope.book.isbn13) {
            var stripped = stripHyphens($scope.book.isbn13);
            $http.get('/books/book-by-isbn?isbn13=' + stripped).then(
                function success(response) {
                    var data = response.data[0];
                    if (data && data.isbn13 == stripHyphens($scope.book.isbn13)) {
                        $scope.book['title'] = data.title;
                        $scope.book['edition'] = data.edition;
                        $scope.book['publisher'] = data.publisher;
                        $scope.authors = data.authors;

                        $scope.autofilledBook = data;
                        $scope.isAutoFilled = true;
                    }
                }
            );
        }
    };

    $scope.removeAuthor = function(index) {
        if (index >= 0 && index < $scope.authors.length) {
                $scope.authors.splice(index, 1);
        }
    };

    $scope.addNewBookToCart = function(book, form, ignoreValidation){
        $scope.submitted = true;

        if (ignoreValidation || form.$valid) {
            $scope.master = angular.copy(book);
            $scope.master["authors"] = $scope.authors;
            var bookData = {};
            bookData['book'] = $scope.master;
            bookData['book']['isbn13'] = stripHyphens(bookData['book']['isbn13']);
            CartService.cartBooks.push(bookData);
            $scope.master = {};
            $scope.authors = [];
            $scope.reset(form);
        }
    };

    $scope.reset = function(form) {
        $scope.submitted = false;
        if (form) {
            form.$setPristine();
            form.$setUntouched();
        }
        $scope.isAutoFilled = false;
        $scope.book = angular.copy($scope.master);
    };

    $scope.reset();

}]);