
var app = angular.module('sisyphus', [])

app.config(function($interpolateProvider) {
    $interpolateProvider.startSymbol('[[');
    $interpolateProvider.endSymbol(']]');
});

app.controller('OrdersController', function($scope){
    $scope.stage = 1;
})