var app = angular.module('sisyphus', ['sisyphus.helpers', 'ui.bootstrap', 'smart-table']);


app.controller('TermsController', function($scope) {

    $scope.order_start_date = order_start_date_init;
    $scope.order_due_date = order_due_date_init;

});

app.controller('TermsTableController', function($scope, $http) {

    var ctrl = this;
    this.displayed = [];
    this.callServer = function callServer(tableState) {
        console.log("server called");

        ctrl.isLoading = true;
        var pagination = tableState.pagination;
        var start = pagination.start || 0;
        var end = pagination.number || 10;

        var page = (start/end)+1;

        var getRequestString = '/terms/term-list?page=' + page;                                 // term list uri
        if(tableState.sort.predicate) {
            getRequestString += '&sort=' + encodeURIComponent(tableState.sort.predicate);      // build sort
            if(tableState.sort.reverse) {
                if (predicateObject.term)
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
        console.log("server called");
        ctrl.isLoading = true;

        var pagination = tableState.pagination;
        var start = pagination.start || 0;
        var end = pagination.number || 10;
        var page = (start/end)+1;

        var getRequestString = '/terms/term-detail-list?&page=' + page;                                 // term list uri

        getRequestString += '&term_id=' + term_id_init;                                               // get term id

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
