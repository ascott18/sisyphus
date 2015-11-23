
var app = angular.module('sisyphus', ['sisyphus.helpers', 'smart-table', 'filters']);


angular.module('filters', []).filter('zpad', function() {
    return function(input, n) {
        if(input === undefined)
            input = ""
        if(input.length >= n)
            return input
        var zeros = "0".repeat(n);
        return (zeros + input).slice(-1 * n)
    };
});


app.controller('BooksController', function($scope, $http) {
    var ctrl = this;

    this.displayed = [];

    this.callServer = function callServer(tableState) {
        ctrl.isLoading = true;

        var pagination = tableState.pagination;
        var start = pagination.start || 0;
        var end = pagination.number || 10;
        var page = (start/end)+1;

        var getRequestString = '/books/book-list?page=' + page;                                         // book list uri

        if(tableState.sort.predicate) {
            getRequestString += '&sort=' + encodeURIComponent(tableState.sort.predicate);               // build search
            if(tableState.sort.reverse)
                getRequestString += '&dir=desc';
        }

        if(tableState.search.predicateObject) {
            var predicateObject = tableState.search.predicateObject;
            if(predicateObject.title)
                getRequestString += '&title=' + encodeURIComponent(predicateObject.title);              // search title
            if(predicateObject.publisher)
                getRequestString += '&publisher=' + encodeURIComponent(predicateObject.publisher);      // search publisher
            if(predicateObject.isbn13)
                getRequestString += '&isbn13=' + encodeURIComponent(predicateObject.isbn13);            // search isbn
        }

        $http.get(getRequestString).then(
            function success(response) {
                tableState.pagination.numberOfPages = response.data.last_page;                          // update number of pages with what laravel gives back
                tableState.pagination.number = response.data.per_page;                                  // update how many per page based on laravel response
                ctrl.displayed = response.data.data;                                                    // get return data
                ctrl.isLoading=false;
            },
            function error(response) {
                // TODO: handle properly
                console.log("Couldn't get book list", response);
            }
        );

    }
});

app.controller('BookDetailsController', function($scope, $http) {
    var ctrl = this;

    $scope.book_id = book_id_init;

    this.displayed = [];

    this.callServer = function callServer(tableState) { // TODO: move this into other controller
        ctrl.isLoading = true;

        var pagination = tableState.pagination;
        var start = pagination.start || 0;
        var end = pagination.number || 10;
        var page = (start/end)+1;

        var getRequestString = '/books/book-detail-list?page=' + page;                                  // book list uri

        getRequestString += '&book_id=' + $scope.book_id;                                               // get book id

        if(tableState.sort.predicate) {
            getRequestString += '&sort=' + encodeURIComponent(tableState.sort.predicate);               // build sort
            if(tableState.sort.reverse)
                getRequestString += '&dir=desc';
        }

        if(tableState.search.predicateObject) {
            var predicateObject = tableState.search.predicateObject;
            if(predicateObject.section)
                getRequestString += '&section=' + encodeURIComponent(predicateObject.section);
            if(predicateObject.course_name)
                getRequestString += '&course_name=' + encodeURIComponent(predicateObject.course_name);
            if(predicateObject.ordered_by_name)
                getRequestString += '&ordered_by=' + encodeURIComponent(predicateObject.ordered_by_name);
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
                console.log("Couldn't get book details", response);
            }
        );

    }
});