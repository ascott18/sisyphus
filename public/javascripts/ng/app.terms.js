var app = angular.module('sisyphus', ['sisyphus.helpers', 'ui.bootstrap', 'smart-table']);


app.controller('TermsController', function($scope) {
    // Can't use 'new' in angular expressions, so we just do this instead.
    $scope.createDate = function(date){
        return new Date(date);
    }
});

app.controller('TermsTableController', function($scope, StHelper) {

    $scope.callServer = function(tableState) {
        tableState.term_selected = $scope.TermSelected;

        var config = {
            url: '/terms/term-list'
        };

        StHelper.callServer(tableState, config, $scope );
    };
});
