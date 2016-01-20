var app = angular.module('sisyphus', ['sisyphus.helpers', 'ui.bootstrap' , 'smart-table']);

app.controller('ReportsController', function($scope, $http) {
    $scope.createDate = function(date){
        return new Date(date);
    }
    $scope.updateTerm=function()
    {
        $scope.TermSelected="";
    }
});