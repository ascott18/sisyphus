
var app = angular.module('sisyphus', ['sisyphus.helpers', 'smart-table', 'angularUtils.directives.dirPagination', 'sisyphus.helpers.isbnHyphenate']);

app.controller('CoursesIndexController', function($scope, $http) {
    var ctrl1 = this;
    $scope.stCtrl=null;
    $scope.stTableRef=null;

    $scope.updateTerm=function()
    {
        if($scope.stCtrl)
            $scope.stCtrl.pipe();

        if($scope.stTableRef)
            $scope.stTableRef.pagination.start = 0;
    };

    this.displayed = [];

    this.callServer = function callServer(tableState, ctrl) {


        ctrl1.isLoading = true;
        if(!$scope.stCtrl&&ctrl)
        {
            $scope.stCtrl=ctrl;
        }

        if(!$scope.stTableRef&&tableState)
        {
            $scope.stTableRef=tableState;
        }

        if(!tableState&&$scope.stCtrl){
            $scope.stCtrl.pipe();
            return;
        }
        var pagination = tableState.pagination;
        var start = pagination.start || 0;
        var end = pagination.number || 10;
        var page = (start/end)+1;

        var searchObject = tableState.search.predicateObject;

        var getRequestString = '/courses/course-list?page=' + page +
                                '&term_id='     + $scope.TermSelected +
                                '&table_state=' + encodeURIComponent(JSON.stringify(tableState));


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


app.controller('CoursesDetailsController', function($http, $scope) {
    $scope.noBook = function(course_id)
    {
        $http.post('/requests/no-book', {course_id: course_id}).then(
            function success(response){
                location.reload();
            });
    };
});

app.controller('CoursesModifyController', function($filter, $scope) {
    $scope.getSelectedUser = function(){
        for(var i = 0; i < $scope.users.length; i++){
            if ($scope.users[i].user_id == $scope.course.user_id){
                return i;
            }
        }
    };

    $scope.userSearchOnBlur = function(query){
        var filteredUsers = $filter('filterSplit')($scope.users, query);
        if (filteredUsers.length == 1){
            $scope.course.user_id = filteredUsers[0].user_id;
        }
    };

    $scope.submit = function(form, e){
        if (form.$valid)
            form.submit();
        else{
            form.$setSubmitted(true);
            e.preventDefault();
        }
    }
});