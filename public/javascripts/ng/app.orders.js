
var app = angular.module('sisyphus', ['sisyphus.helpers'])

app.controller('OrdersController', function($scope){

    $scope.STAGE_SELECT_COURSE = 1;
    $scope.STAGE_SELECT_BOOKS = 2;

    $scope.stage = $scope.STAGE_SELECT_BOOKS;

    $scope.getStage = function(){
        return $scope.stage;
    };

    $scope.setStage = function(stage){
        $scope.stage = stage;
    };


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