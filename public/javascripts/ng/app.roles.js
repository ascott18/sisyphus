
var app = angular.module('sisyphus', ['sisyphus.helpers']);

app.controller('RolesController', function($scope, $http) {
    $http.get('/users/all-roles').then(
        function(response){
            $scope.roles = response.data;
        }
    );

    $scope.removePermission = function(role, permission){
        $http.post('/users/remove-permission', {
            role_id: role.id,
            permission_id: permission.id
        }).then(
            function(response){
                for (i=0; i<role.permissions.length; i++){
                    if (role.permissions[i].id == permission.id){
                        role.permissions.splice(i, 1);
                        break;
                    }
                }
            }
        );
    };

    $scope.deleteRole = function(role){
        $http.post('/users/delete-role', {
            role_id: role.id
        }).then(
            function(){
                $scope.roles.splice($scope.roles.indexOf(role), 1);
            }
        );
    };

    $scope.createRole = function(newRoleName){
        $http.post('/users/create-role', {
            name: newRoleName
        }).then(
            function(response){
                var newRole = response.data.role;
                newRole['numUsers'] = 0;
                newRole['permissions'] = [];
                $scope.roles.push(newRole);
                $scope.creatingRole = false;
                $scope.newRoleName = '';
            }
        );
    }
});

app.filter('notInArray', function($filter){
    return function(list, arrayFilter, element){
        if(arrayFilter){
            var elements = [];
            for(i=0;i<arrayFilter.length;i++)
                elements.push(arrayFilter[i][element]);

            return $filter("filter")(list, function(listItem){
                return elements.indexOf(listItem[element]) == -1;
            });
        }
    };
});




app.controller('AddPermissionController', function($scope, $http) {
    $scope.allPermissions = allPermissions;
    $scope.selectedPermission = null;

    $scope.addPermission = function(){
        var selectedPermission = $scope.selectedPermission;

        if (!selectedPermission.name) return;

        $http.post('/users/add-permission', {
            role_id: $scope.role.id,
            permission_id: selectedPermission.id
        }).then(
            function(response){
                $scope.role.permissions.push(selectedPermission);
                $scope.role.addingPermission = false;
            }
        );
    };

});