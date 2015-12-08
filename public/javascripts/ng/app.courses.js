
var app = angular.module('sisyphus', ['sisyphus.helpers', 'smart-table', 'sisyphus.helpers.isbnHyphenate']);

app.controller('CoursesController', function($scope, $http) {
    var ctrl1 = this;
    $scope.term="";
    $scope.stCtrl=null;

    $scope.updateTerm=function(value)
    {
        $scope.term=value;
        $scope.stCtrl.pipe();
    }

    this.displayed = [];

    this.callServer = function callServer(tableState, ctrl) {
        ctrl1.isLoading = true;
        if(!$scope.stCtrl&&ctrl)
        {
            $scope.stCtrl=ctrl;
        }

        if(!tableState&&$scope.stCtrl){
            $scope.stCtrl.pipe();
            return;
        }
        var pagination = tableState.pagination;
        var start = pagination.start || 0;
        var end = pagination.number || 10;
        var page = (start/end)+1;

        var getRequestString = '/courses/course-list?page=' + page;                                 // set course list uri

        if($scope.term!="")
        {
            getRequestString+= '&term_id=' + $scope.term;
        }


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
                ctrl1.displayed = response.data.data;                                              // save laravel response data
                ctrl1.isLoading=false;
            }
        );

    }
});
