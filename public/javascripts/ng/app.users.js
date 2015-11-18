
var app = angular.module('sisyphus', ['sisyphus.helpers', 'ui.bootstrap', 'smart-table']);

app.controller('UsersController', function($scope, $http) {
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
            },
            function(response){
                // TODO: handle this properly.
                console.log("Error removing department");
            }
        );
    };



    var ctrl = this;

    this.displayed = [];

    this.callServer = function callServer(tableState) {
        ctrl.isLoading = true;

        var pagination = tableState.pagination;
        var start = pagination.start || 0;
        var end = pagination.number || 10;
        var page = (start/end)+1;

        var getRequestString = '/users/user-list?page=' + page;                                 // user list URI


        if(tableState.sort.predicate) {
            getRequestString += '&sort=' + tableState.sort.predicate;                           // build sort string
            if(tableState.sort.reverse)
                getRequestString += '&dir=desc';
        }

        if(tableState.search.predicateObject) {
            var predicateObject = tableState.search.predicateObject;

            if(predicateObject.lName)
                getRequestString += '&lName=' + encodeURIComponent(predicateObject.lName);    // search for last name
            if(predicateObject.fName)
                getRequestString += '&fName=' + encodeURIComponent(predicateObject.fName);    // search for first name
            if(predicateObject.netID)
                getRequestString += '&netID=' + encodeURIComponent(predicateObject.netID);    // search for netID
            if(predicateObject.email)
                getRequestString += '&email=' + encodeURIComponent(predicateObject.email);    // search for email
        }


        $http.get(getRequestString).then(
            function success(response) {
                tableState.pagination.numberOfPages = response.data.last_page;                // update number of pages with laravel response
                tableState.pagination.number = response.data.per_page;                        // update number of entries per page with laravel response
                $scope.users = response.data.data; // using scope var since it was already there.
                ctrl.isLoading=false;
            },
            function error(response) {
                // TODO: handle properly
                console.log("Couldn't get users", response);
            }
        );

    }

    /*
    $http.get('/users/all-users').then(
        function(response){
            $scope.users = response.data;
        },
        function(response){
            // TODO: handle this properly.
            console.log("Error getting users");
        }
    );
    */
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
            },
            function(response){
                // TODO: handle this properly.
                console.log("Error adding department");
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
            },
            function(response){
                // TODO: handle this properly.
                console.log("Error adding department");
            }
        );
    };

});

