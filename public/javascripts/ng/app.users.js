
var app = angular.module('sisyphus', ['sisyphus.helpers', 'smart-table']);

app.controller('UsersController', function($scope, $http, StHelper) {
    $scope.removeDepartment = function(user, department){

        $http.post('/users/remove-department', {
            user_id: user.user_id,
            department: department
        }).then(
            function(response){
                for (i=0; i<user.departments.length; i++){
                    if (user.departments[i].department == department){
                        user.departments.splice(i, 1);
                        break;
                    }
                }
            }
        );
    };

    $scope.callServer = function(tableState) {
        var config = {
            url: '/users/user-list'
        };

        StHelper.callServer(tableState, config, $scope );
    };
});


app.controller('UsersModifyController', function ($scope) {
    $scope.submit = function(form, e){
        if (form.$valid)
            form.submit();
        else{
            form.$setSubmitted(true);
            e.preventDefault();
        }
    }
});

app.controller('NewDepartmentController', function($scope, $http) {
    $scope.canAddDepartment = function(){
        var user = $scope.user;

        if (!$scope.newDept)
            return false;

        var newDept = $scope.newDept.toUpperCase().trim();

        if (!newDept || newDept.length < 2 || newDept.length > 10)
            return false;

        for (i=0; i<user.departments.length; i++){
            if (user.departments[i].department == newDept)
                return false;
        }

        return true;
    };

    $scope.addDepartment = function(){
        if (!$scope.canAddDepartment()) return;

        var user = $scope.user;

        var newDept = $scope.newDept.toUpperCase().trim();

        $http.post('/users/add-department', {
            user_id: user.user_id,
            department: newDept
        }).then(
            function(response){
                user.departments.push({department: newDept});
                user.newDept = "";
                user.addingDept = false;
            }
        );
    };

});



app.controller('ChangeRoleController', function($scope, $http) {

    $scope.allRoles = allRoles;

    var userRoles = $scope.user.roles;

    $scope.selectedRole = userRoles[0] || {name:"", display_name:"Faculty"};

    $scope.changeRole = function(){
        $http.post('/users/set-role', {
            user_id: $scope.user.user_id,
            role: $scope.selectedRole.name
        }).then(
            function(response){
                $scope.user.roles = [$scope.selectedRole];
                $scope.user.editingRole = false;
            }
        );
    };

});

