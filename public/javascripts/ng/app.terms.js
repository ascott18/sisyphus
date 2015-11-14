
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

        var getRequestString = '/terms/term-list?page=' + page;

        if(tableState.sort.predicate) {
            getRequestString += '&sort=' + encodeURIComponent(tableState.sort.predicate);
            if(tableState.sort.reverse)
                getRequestString += '&dir=desc';
        }
        if(tableState.search.predicateObject) {
             var predicateObject = tableState.search.predicateObject;
            if(predicateObject.term)
                getRequestString += '&term=' + encodeURIComponent(predicateObject.term);
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