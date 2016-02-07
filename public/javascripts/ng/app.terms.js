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

        tableState.term_selected = $scope.TermSelected;
        var getRequestString = '/terms/term-list';

        var config = {
            params: {
                page: page,
                table_state: tableState
            }
        };

        $http.get(getRequestString, config).then(function(response){
            tableState.pagination.numberOfPages = response.data.last_page;                    // update number of pages with laravel response
            tableState.pagination.number = response.data.per_page;                            // update entries per page with laravel response
            ctrl.displayed = response.data.data;                                              // save laravel response data
            ctrl.isLoading=false;
        });
    };
});
