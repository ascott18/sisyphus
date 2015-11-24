
var app = angular.module('sisyphus', ['sisyphus.helpers', 'smart-table']);


app.controller('CoursesController', function($scope, $http) {
    var ctrl = this;

    this.displayed = [];

    this.callServer = function callServer(tableState) {
        ctrl.isLoading = true;

        var pagination = tableState.pagination;
        var start = pagination.start || 0;
        var end = pagination.number || 10;
        var page = (start/end)+1;

        var getRequestString = '/courses/course-list?page=' + page;                                 // set course list uri


        if(tableState.sort.predicate) {
            getRequestString += '&sort=' + tableState.sort.predicate;                               // build sort string
            if(tableState.sort.reverse)
                getRequestString += '&dir=desc';
        }

        if(tableState.search.predicateObject) {
            var predicateObject = tableState.search.predicateObject;
            if(predicateObject.section)
                getRequestString += '&section=' + encodeURIComponent(predicateObject.section);     // search for section
            if(predicateObject.name)
                getRequestString += '&name=' + encodeURIComponent(predicateObject.name);           // search for name
        }


        $http.get(getRequestString).then(
            function success(response) {
                tableState.pagination.numberOfPages = response.data.last_page;                    // update number of pages with laravel response
                tableState.pagination.number = response.data.per_page;                            // update entries per page with laravel response
                ctrl.displayed = response.data.data;                                              // save laravel response data
                ctrl.isLoading=false;
            },
            function error(response) {
                // TODO: handle properly
                console.log("Couldn't get recipients", response);
            }
        );

    }
});