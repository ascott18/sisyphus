/**
 * Created by Admin00 on 11/16/2015.
 */
var app = angular.module('sisyphus', ['sisyphus.helpers']);

app.controller("EditBookController", ["$scope", "$http", function($scope, $http) {
    $scope.authors= [];
    $scope.book = {};

    $scope.setBook = function(book) {
        $scope.book = book;
    }

    $scope.addAuthors= function(authors){
        $scope.authors=authors;
    }

    $scope.addAuthor = function(author) {
        $scope.authors.push({name: ""});

    };

    $scope.removeAuthor = function(index) {
        if (index >= 0 && index < $scope.authors.length) {
            $scope.authors.splice(index, 1);
        }
    };




}]);
