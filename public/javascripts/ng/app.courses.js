
var app = angular.module('sisyphus', ['sisyphus.helpers', 'smart-table', 'angularUtils.directives.dirPagination', 'sisyphus.helpers.isbnHyphenate']);

app.controller('CoursesIndexController', function($scope, StHelper) {

    $scope.updateTerm = function()
    {
        StHelper.reset($scope.stCtrl);
    };

    $scope.callServer = function(tableState, ctrl) {
        // Keep track of the smart-table controller so that we can
        // reset the table when the user changes the term filter.
        $scope.stCtrl = ctrl;

        tableState.term_selected = $scope.TermSelected;

        var config = {
            url: '/courses/course-list'
        };

        StHelper.callServer(tableState, config, $scope );
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

    $scope.deleteListing = function(listing){
        $scope.course.listings.splice($scope.course.listings.indexOf(listing), 1);
    };

    $scope.addListing = function(){
        var last = $scope.course.listings[$scope.course.listings.length - 1];
        $scope.course.listings.push({
            department: last.department,
            number: last.number,
            section: last.section
        })
    };

    $scope.makeFormKey = function(index, property){
        return "course[listings][" + index + "][" + property + "]";
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