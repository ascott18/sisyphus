
var app = angular.module('sisyphus', ['sisyphus.helpers', 'sisyphus.helpers.isbnHyphenate']);

stripHyphens = function(isbn13) {
    return isbn13.replace(/-/g, "");
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

app.directive('cart', function() {
    return {
        templateUrl: '/javascripts/ng/templates/cart.html'
    };
});

app.directive('bookDetails', function() {
   return {
       restrict: 'E',
       scope: {
           book: '='
       },
       templateUrl: '/javascripts/ng/templates/bookDetails.html'
   }
});

app.controller('OrdersController', ['$scope', '$http', 'CartService',
    function($scope, $http, CartService){

    $scope.STAGE_SELECT_COURSE = 1;
    $scope.STAGE_SELECT_BOOKS = 2;
    $scope.STAGE_REVIEW_ORDERS = 3;
    $scope.STAGE_CONFIRMATION = 4;

    $scope.cartBooks = CartService.cartBooks;

    $scope.stage = $scope.STAGE_SELECT_COURSE;

    $scope.selectedCourse;


    $scope.getStage = function(){
        return $scope.stage;
    };

    $scope.setStage = function(stage){
        $scope.stage = stage;
    };

    $scope.placeRequestForCourse = function(course) {
        $scope.setStage($scope.STAGE_SELECT_BOOKS);

        $scope.selectedCourse = course;

        $http.get('/orders/past-courses/' + course.course_id).then(
            function success(response) {
                console.log("got courses", response.data);
                var pastCourses = response.data;
                var pastBooks = [];

                for (var i = 0; i < pastCourses.length; i++) {
                    var pastCourse = pastCourses[i];
                    for (var j = 0; j < pastCourse.orders.length; j++) {
                        var order = pastCourse.orders[j];
                        var bookData = {};
                        bookData['book'] = order.book;
                        bookData['course'] = pastCourse;
                        pastBooks.push(bookData);
                    }
                }


                course['pastBooks'] = pastBooks;
                $scope.selectedCourse = course;
            },
            function error(response) {
                // TODO: handle properly
                console.log("Couldn't get past courses", response);
            }
        );

    };

    $scope.courseNeedsOrders = function(course)
    {
        return course.orders.length == 0 && !course.no_book
    };

    $scope.noBook= function(course)
    {
        $http.post('/orders/no-book', {course_id: course.course_id}).then(
            function success(response){
                course.no_book=true;

                // TODO: handle this properly - display a little thing that says "Saving" or "Saved"?
                console.log("Saved!", response);
            },
            function error(response){
                // TODO: handle this properly.
                console.log("Not Saved!", response);
            });
    };

    var readUrl = '/orders/read-courses';
    if (requested_user_id)
        readUrl += '?user_id=' + requested_user_id;

    $http.get(readUrl).then(
        function success(response) {
            $scope.gotCourses = true;
            $scope.courses = response.data;
        }
    );



    $scope.deleteBookFromCart = function(bookData) {
        var index = $scope.cartBooks.indexOf(bookData);
        if (index > -1) {
            $scope.cartBooks.splice(index, 1);
            if (!bookData.isNew) {
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

    $scope.submitOrders = function() {

        $http.post('/orders/submit-order', {course_id:$scope.selectedCourse.course_id, cart:CartService.cartBooks}).then(
            function success(response){
                $scope.setStage($scope.STAGE_CONFIRMATION);
                console.log("Saved!", response);
            },
            function error(response){
                // TODO: handle this properly.
                alert("notsaved!");
                console.log("Not Saved!", response);
            });
    }

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
                    if (data) {
                        $scope.book['title'] = data.title;
                        $scope.book['edition'] = data.edition;
                        $scope.book['publisher'] = data.publisher;
                        $scope.authors = data.authors;

                        $scope.isAutoFilled = true;
                    }

                    console.log("got book", response.data);
                },
                function error(response) {
                    // TODO: handle properly
                    console.log("Couldn't get past courses", response);
                }
            );
        }
    };

    $scope.removeAuthor = function(index) {
        if (index >= 0 && index < $scope.authors.length) {
            $scope.authors.splice(index, 1);
        }
    };

    $scope.addNewBookToCart = function(book, form){
        $scope.submitted = true;

        if (form.$valid) {
            $scope.master = angular.copy(book);
            $scope.master["authors"] = $scope.authors;
            $scope.master["isNew"] = true;
            var bookData = {};
            bookData['book'] = $scope.master;
            bookData['book']['isbn13'] = stripHyphens(book['isbn13'])
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