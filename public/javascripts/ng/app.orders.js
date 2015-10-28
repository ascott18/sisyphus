
var app = angular.module('sisyphus', ['sisyphus.helpers'])

app.controller('OrdersController', function($scope, $http){

    $scope.STAGE_SELECT_COURSE = 1;
    $scope.STAGE_SELECT_BOOKS = 2;

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
    }

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
            $scope.courses = response.data;
        },
        function error(response) {
            // TODO: handle properly
            console.log("Couldn't get recipients", response);
        }
    );


    $scope.cartBooks =[
        {title: "Stu's happy fun land book"},
        {title: "Some other book"},
        {title: "Naming things is hard"}
    ];


    $scope.pastBooks = [
        {title: "Stu's old book that he used 2 years ago", mine:true},
        {title: "Another book that he tried once and didn't like", mine:false},
        {title: "Stu's favorite opsys book", mine:true},
        {title: "Another clever fake book name", mine:false}
    ]


    $scope.addInputBookToCart = function(){
        
    }

});