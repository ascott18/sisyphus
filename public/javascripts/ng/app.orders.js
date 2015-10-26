
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
        {title: "Stu's old crappy book that he used one time like 2 years ago", mine:true},
        {title: "ANother really dumb book that he tried once and didnt like at all", mine:false},
        {title: "Stu's favorite crappy book that he forces on all his students", mine:false},
        {title: "I'm sick of coming up with clever fake books names", mine:true}
    ]


    $scope.addInputBookToCart = function(){
        
    }

});