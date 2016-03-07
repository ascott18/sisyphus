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

app.controller('TermsImportController', function($rootScope, $scope, $http) {

    $scope.browserTooOld = false;

    if (typeof FormData == 'undefined'){
        $rootScope.appErrors = $rootScope.appErrors || [];
        $rootScope.appErrors.push({messages: ["Your browser is too old. You will not be able to use this feature. Please switch to a modern browser! "]});
        $scope.browserTooOld = true;
        return;
    }

    $scope.noChangeListingLimit = 10;
    $scope.newCourseLimit = 10;
    $scope.willWere = 'will be';

    $scope.showAllNewCourses = function(){
        $scope.newCourseLimit = undefined;
    };

    $scope.showAllNoChangeListings = function(){
        $scope.noChangeListingLimit = undefined;
    };

    $scope.submitForPreview = function(){
        $scope.submittingPreview = true;

        var fd = new FormData();
        fd.append('file', $scope.file);

        $http.post('/terms/import-preview/' + $scope.term_id, fd, {
            transformRequest: angular.identity,
            headers: {'Content-Type': undefined}
        }).then(function(response){
            $scope.actions = response.data.actions;
            $scope.submittingPreview = false;
            $scope.havePreviewResponse = true;
        });
    };

    $scope.submitForImport = function(){
        $scope.actions = false;
        $scope.submittingImport = true;

        var fd = new FormData();
        fd.append('file', $scope.file);

        $http.post('/terms/import-data/' + $scope.term_id, fd, {
            transformRequest: angular.identity,
            headers: {'Content-Type': undefined}
        }).then(function(response){
            $scope.actions = response.data.actions;
            $scope.submittingImport = false;
            $scope.submittedImport = true;
            $scope.willWere = 'were';
        });
    };
});


// From https://jsfiddle.net/JeJenny/ZG9re/
app.directive('fileModel', ['$parse', function ($parse) {
    return {
        restrict: 'A',
        link: function(scope, element, attrs) {
            var model = $parse(attrs.fileModel);
            var modelSetter = model.assign;

            element.bind('change', function(){
                scope.$apply(function(){
                    modelSetter(scope, element[0].files[0]);
                });
            });

            modelSetter(scope, element[0].files[0]);
        }
    };
}]);