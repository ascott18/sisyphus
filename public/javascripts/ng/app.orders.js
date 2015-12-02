
var app = angular.module('sisyphus', ['sisyphus.helpers']);

app.service("CartService", function () {
    this.cartBooks = [];
});

app.directive('bookEditor', function() {
    return {
        restrict: 'E',
        templateUrl: '/javascripts/ng/templates/bookEditor.html'
    };
});

var ISBN13_REGEXP = /^\x20*(?=.{17}$)97(?:8|9)([ -])\d{1,5}\1\d{1,7}\1\d{1,6}\1\d$/;
app.directive('isbn13', function() {
    return {
        require: 'ngModel',
        link: function(scope, elm, attrs, ctrl) {
            ctrl.$validators.isbn13 = function(modelValue, viewValue) {
                if (ctrl.$isEmpty(modelValue)) {
                    // consider empty models to be valid
                    return true;
                }

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

app.controller('OrdersController',['$scope', '$http', 'CartService', function($scope, $http, CartService){

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



    $http.get('/orders/read-courses').then(
        function success(response) {
            console.log("got books")
            $scope.courses = response.data;
        },
        function error(response) {
            // TODO: handle properly
            console.log("Couldn't get recipients", response);
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

    $scope.addAuthor = function(author) {
        $scope.authors.push({name: ""});

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
        $scope.book = angular.copy($scope.master);
    };

    $scope.reset();

}]);