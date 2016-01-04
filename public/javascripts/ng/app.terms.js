var app = angular.module('sisyphus', ['sisyphus.helpers', 'ui.bootstrap', 'smart-table']);


app.controller('TermsController', function($scope) {
    // Can't use 'new' in angular expressions, so we just do this instead.
    $scope.createDate = function(date){
        return new Date(date);
    }
});

app.controller('TermsTableController', function($scope, $http) {

    var ctrl = this;
    this.displayed = [];
    this.callServer = function callServer(tableState) {

        ctrl.isLoading = true;
        var pagination = tableState.pagination;
        var start = pagination.start || 0;
        var end = pagination.number || 15;

        var page = (start/end)+1;

        var getRequestString = '/terms/term-list?page=' + page;                                 // term list uri
        if(tableState.sort.predicate) {
            getRequestString += '&sort=' + encodeURIComponent(tableState.sort.predicate);      // build sort
            if(tableState.sort.reverse) {
                getRequestString += '&dir=desc';
            }
        }
        if(tableState.search.predicateObject) {
            var predicateObject = tableState.search.predicateObject;
            getRequestString += '&term=' + encodeURIComponent(predicateObject.term);      // build search for term
            if(predicateObject.year)
                getRequestString += '&year=' + encodeURIComponent(predicateObject.year);    // build search for year
        }

        $http.get(getRequestString).then(
            function success(response) {
                tableState.pagination.numberOfPages = response.data.last_page;               // update number of pages with laravel response
                tableState.pagination.number = response.data.per_page;                       // update entries per page with laravel response
                ctrl.displayed = response.data.data;                                         // save laravel response data for table
                ctrl.isLoading=false;
            }
        );
    }

    this.callServerDetail = function callServer(tableState) {
        ctrl.isLoading = true;

        var pagination = tableState.pagination;
        var start = pagination.start || 0;
        var end = pagination.number || 10;
        var page = (start/end)+1;

        var getRequestString = '/terms/term-detail-list?&page=' + page;                                 // term list uri

        getRequestString += '&term_id=' + $scope.term_id;                                               // get term id

        if(tableState.sort.predicate) {
            getRequestString += '&sort=' + encodeURIComponent(tableState.sort.predicate);               // build sort
            if(tableState.sort.reverse)
                getRequestString += '&dir=desc';
        }

        if(tableState.search.predicateObject) {
            var predicateObject = tableState.search.predicateObject;
            if(predicateObject.section)
                getRequestString += '&section=' + encodeURIComponent(predicateObject.section);
            if(predicateObject.name)
                getRequestString += '&name=' + encodeURIComponent(predicateObject.name);
        }

        $http.get(getRequestString).then(
            function success(response) {
                tableState.pagination.numberOfPages = response.data.last_page;               // update number of pages with laravel response
                tableState.pagination.number = response.data.per_page;                       // update entries per page with laravel response
                ctrl.displayed = response.data.data;                                         // save laravel response data for table
                ctrl.isLoading=false;
            }
        );

    }
});
