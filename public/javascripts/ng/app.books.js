/**
 * Created by Admin00 on 11/16/2015.
 */
var app = angular.module('sisyphus', ['sisyphus.helpers', 'sisyphus.helpers.isbnHyphenate', 'smart-table']);

app.directive('bookEditor', function() {
    return {
        restrict: 'E',
        templateUrl: '/javascripts/ng/templates/bookEditor.html'
    };
});

app.controller('BooksController', ['$scope', '$http', function($scope, $http) {


    $scope.callServer = function callServer(tableState) {

        var pagination = tableState.pagination;
        var start = pagination.start || 0;
        var end = pagination.number || 10;
        var page = (start/end)+1;

        var getRequestString = '/books/book-list';

        var data = {
            page: page,
            table_state: tableState
        };

        var config = {
            params: data
        };

        $http.get(getRequestString, config).then(function(response){
            tableState.pagination.numberOfPages = response.data.last_page;                    // update number of pages with laravel response
            tableState.pagination.number = response.data.per_page;                            // update entries per page with laravel response
            $scope.displayed = response.data.data;                                              // save laravel response data
        });
    }
}]);

app.controller('EditBookController', function($scope, $http) {
    $scope.authors = [];
    $scope.book = {};
    $scope.submitted = false;

    $scope.addAuthor = function() {
        $scope.authors.push({name: ""});
    };

    $scope.removeAuthor = function(index) {
        if (index >= 0 && index < $scope.authors.length) {
            $scope.authors.splice(index, 1);
        }
    };

    $scope.setBook = function(book) {
        $scope.book = book;
    };

    $scope.addAuthors = function(authors) {
        $scope.authors = authors;
    };

    $scope.reset = function(form) {
        $scope.submitted = false;
        if (form) {
            form.$setPristine();
            form.$setUntouched();
        }
        $scope.book = angular.copy($scope.master);
    };


    $scope.submit = function(form, e){
        if (form.$valid)
            form.submit();
        else{
            form.$setSubmitted(true);
            e.preventDefault();
        }
    };

    $scope.reset();
});

app.controller('BookDetailsController', function($scope, StHelper, HelpService) {

    $scope.setHelpOption = function(book) {
        HelpService.addBookHelpOption([book]);
    };


    $scope.getBookCoverImageUrl = function(){
        return '/books/cover?isbn=' + $scope.book_isbn_13;
    };

    $scope.callServer = function(tableState) {

        var config = {
            url: '/books/book-detail-list',
            params: {
                book_id: $scope.book_id
            }
        };

        StHelper.callServer(tableState, config, $scope );
    };

});
