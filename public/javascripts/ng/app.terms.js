
var app = angular.module('sisyphus', ['sisyphus.helpers', 'ui.bootstrap']);

app.controller('TermsController', function($scope) {

    $scope.order_start_date = order_start_date_init;
    $scope.order_due_date = order_due_date_init;

});