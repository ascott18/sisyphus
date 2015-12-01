
var app = angular.module('sisyphus', ['sisyphus.helpers', 'sisyphus.helpers.isbnHyphenate']);

app.service("CartService", function () {
    this.cartBooks = [
        //{title: "Stu's happy fun land book"},
        //{title: "Some other book"},
        //{title: "Naming things is hard"}
    ];
});

app.controller('OrdersController', ['$scope', '$http', 'CartService',
    function($scope, $http, CartService){

    $scope.STAGE_SELECT_COURSE = 1;
    $scope.STAGE_SELECT_BOOKS = 2;
    $scope.cartBooks = CartService.cartBooks;

    $scope.stage = $scope.STAGE_SELECT_COURSE;

    $scope.getStage = function(){
        return $scope.stage;
    };

    $scope.setStage = function(stage){
        $scope.stage = stage;
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
            })
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

    $scope.pastBooks = [
        {title: "Stu's favorite book that he always uses", mine:true},
        {title: "Stu's old book that he used 2 years ago", mine:false},
        {title: "Another book that he tried once", mine:false},
        {title: "Yet another example book", mine:true}
    ];



    $scope.deleteBookFromCart = function(book) {
        var index = $scope.cartBooks.indexOf(book);
        if (index > -1) {
            var book = $scope.cartBooks.splice(index, 1)[0];
            if (!book.isNew) {
                $scope.pastBooks.push(book);
            }
        }
    };

    $scope.addBookToCart = function(book) {
        var index = $scope.pastBooks.indexOf(book);
        if (index > -1) {
            var book = $scope.pastBooks.splice(index, 1)[0];
            $scope.cartBooks.push(book);
        }
    };

}]);



app.controller("NewBookController", ["$scope", "$http", "CartService", function($scope, $http, CartService) {
    $scope.authors = [];
    $scope.master = {};
    $scope.book = {};

    $scope.addAuthor = function(author) {
        $scope.authors.push({name: ""});

    };

    $scope.removeAuthor = function(index) {
        if (index >= 0 && index < $scope.authors.length) {
            $scope.authors.splice(index, 1);
        }
    };

    $scope.addNewBookToCart = function(book){
        $scope.master = angular.copy(book);
        $scope.master["authors"] = $scope.authors;
        $scope.master["isNew"] = true;
        CartService.cartBooks.push($scope.master);
        $scope.master = {};
        $scope.authors = [];
        $scope.reset();
    };

    $scope.reset = function() {
        $scope.book = angular.copy($scope.master);
    };

    $scope.reset();

}]);