
var app = angular.module('sisyphus', ['sisyphus.helpers']);

app.service("CartService", function () {
    this.cartBooks = [
        {title: "Stu's happy fun land book"},
        {title: "Some other book"},
        {title: "Naming things is hard"}
    ];
});

app.controller('OrdersController',['$scope', '$http', 'CartService', function($scope, $http, CartService){

    $scope.STAGE_SELECT_COURSE = 1;
    $scope.STAGE_SELECT_BOOKS = 2;

    $scope.stage = $scope.STAGE_SELECT_BOOKS;

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
                // TODO: handle this properly - display a little thing that says "Saving" or "Saved"?
                console.log("Saved!", response);
            },
            function error(response){
                // TODO: handle this properly.
                console.log("Not Saved!", response);
            })
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


    $scope.cartBooks = [
        {title: "Stu's happy fun land book"},
        {title: "Some other book"},
        {title: "Naming things is hard"}
    ];


    $scope.pastBooks = [
        {title: "Stu's old crappy book that he used one time like 2 years ago", mine:true},
        {title: "ANother really dumb book that he tried once and didnt like at all", mine:false},
        {title: "Stu's favorite crappy book that he forces on all his students", mine:false},
        {title: "I'm sick of coming up with clever fake books names", mine:true}
    ];


    $scope.addInputBookToCart = function(){

    };

    $scope.deleteBookFromCart = function(book) {
        //TODO: we need to know this book came from past books and was not a new book that was entered.
        transferBook($scope.cartBooks, $scope.pastBooks, book);
    };

    $scope.addBookToCart = function(book) {
        transferBook($scope.pastBooks, $scope.cartBooks, book);
    };

    transferBook = function(fromList, toList, book) {
        var index = fromList.indexOf(book);
        if (index > -1) {
            var book = fromList.splice(index, 1);
            toList.push(book[0]);
        }
    }

}]);



app.controller("NewBookController", function($scope, $http) {
    $scope.authors = [];

    $scope.addAuthor = function(author) {
        $scope.authors.push({name: ""});

    };

    $scope.removeAuthor = function(index) {
        if (index >= 0 && index < $scope.authors.length) {
            $scope.authors.splice(index, 1);
        }
    };
});