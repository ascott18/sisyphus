
var app = angular.module('sisyphus', ['sisyphus.helpers', 'smart-table']);


app.controller('BooksController', function($scope, $http) {
    var ctrl = this;

    this.displayed = [];

    this.callServer = function callServer(tableState) {
        ctrl.isLoading = true;

        var pagination = tableState.pagination;
        var start = pagination.start || 0;
        var end = pagination.number || 10;
        var page = (start/end)+1;

        var getRequestString = '/books/book-list?page=' + page;

        if(tableState.sort.predicate) {
            getRequestString += '&sort=' + encodeURIComponent(tableState.sort.predicate);
            if(tableState.sort.reverse)
                getRequestString += '&dir=desc';
        }

        if(tableState.search.predicateObject) {
            var predicateObject = tableState.search.predicateObject;
            if(predicateObject.title)
                getRequestString += '&title=' + encodeURIComponent(predicateObject.title);
            if(predicateObject.publisher)
                getRequestString += '&publisher=' + encodeURIComponent(predicateObject.publisher);
            if(predicateObject.isbn13)
                getRequestString += '&isbn13=' + encodeURIComponent(predicateObject.isbn13);
        }

        $http.get(getRequestString).then(
            function success(response) {
                tableState.pagination.numberOfPages = response.data.last_page;
                tableState.pagination.number = response.data.per_page;
                ctrl.displayed = response.data.data;
                ctrl.isLoading=false;
            },
            function error(response) {
                // TODO: handle properly
                console.log("Couldn't get recipients", response);
            }
        );

    }
});

app.controller('BookDetailsController', function($scope, $http) {
    var ctrl = this;

    $scope.book_id = book_id_init;

    this.displayed = [];

    this.callServer = function callServer(tableState) {
        ctrl.isLoading = true;

        var pagination = tableState.pagination;
        var start = pagination.start || 0;
        var end = pagination.number || 10;
        var page = (start/end)+1;

        var getRequestString = '/books/book-detail-list?page=' + page;

        getRequestString += '&book_id=' + $scope.book_id;

        if(tableState.sort.predicate) {
            getRequestString += '&sort=' + encodeURIComponent(tableState.sort.predicate);
            if(tableState.sort.reverse)
                getRequestString += '&dir=desc';
        }

        /*
        if(tableState.search.predicateObject) {
            var predicateObject = tableState.search.predicateObject;
            if(predicateObject.title)
                getRequestString += '&title=' + encodeURIComponent(predicateObject.title);
            if(predicateObject.publisher)
                getRequestString += '&publisher=' + encodeURIComponent(predicateObject.publisher);
            if(predicateObject.isbn13)
                getRequestString += '&isbn13=' + encodeURIComponent(predicateObject.isbn13);
        }
        */

        $http.get(getRequestString).then(
            function success(response) {
                tableState.pagination.numberOfPages = response.data.last_page;
                tableState.pagination.number = response.data.per_page;
                ctrl.displayed = response.data.data;
                ctrl.isLoading=false;
            },
            function error(response) {
                // TODO: handle properly
                console.log("Couldn't get recipients", response);
            }
        );

    }
});