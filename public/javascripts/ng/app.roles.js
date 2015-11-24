
var app = angular.module('sisyphus', ['sisyphus.helpers', 'ui.bootstrap']);

app.controller('RolesController', function($scope, $http) {
    $http.get('/users/all-roles').then(
        function(response){
            $scope.roles = response.data;
        },
        function(response){
            // TODO: handle this properly.
            console.log("Error getting users");
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
            },
            function(response){
                // TODO: handle this properly.
                console.log("Error adding department");
            }
        );
    };
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
    $scope.selectedPermission = allPermissions[0];

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
            },
            function(response){
                // TODO: handle this properly.
                console.log("Error adding department");
            }
        );
    };

});