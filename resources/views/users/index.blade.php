@extends('layouts.master')

@section('area', 'Users')
@section('page', 'User Management')

@section('content')

    <style>
        .role-input select {
            margin: 0 0 10px 0;
        }

        .dept-input input {
            margin: 10px 0;
            text-transform: uppercase;
        }

        .dept-input {
            width: 150px;
        }
    </style>

    <script>
        allRoles = {!! $roles !!}

        allRoles.unshift({name:"", display_name:"Faculty"});
    </script>

    <div class="row" ng-controller="UsersController as uc">
        <div class="col-lg-12">
            <a class="btn btn-primary"
               href="/users/roles">
                <i class="fa fa-key"></i> Manage Roles
            </a>
            <a class="btn btn-primary"
               href="/users/create">
                <i class="fa fa-plus"></i> Create User
            </a>
            <br>
            <br>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-group fa-fw"></i> All Users</h3>
                </div>
                <div class="panel-body">


                    <div class="table-responsive">
                        <table st-pipe="uc.callServer" st-table="users"
                               class="table table-hover">
                            <thead>
                            <tr>
                                <th width="1%"></th>
                                <th st-sort="last_name">Last Name</th>
                                <th st-sort="first_name">First Name</th>
                                <th st-sort="net_id">NetID</th>
                                <th st-sort="email">Email</th>
                                <th width="240px">Role</th>
                                <th width="170px">Departments</th>
                            </tr>
                            <tr>
                                <th width="1%"></th>
                                <th><input type="text" class="form-control" placeholder="Search..." st-search="lName"/></th>
                                <th><input type="text" class="form-control" placeholder="Search..." st-search="fName"/></th>
                                <th><input type="text" class="form-control" placeholder="Search..." st-search="netID"/></th>
                                <th><input type="text" class="form-control" placeholder="Search..." st-search="email"/></th>
                                <th width="240px"></th>
                                <th width="170px"></th>
                            </tr>
                            </thead>
                            <tbody>

                            <tr ng-cloak
                                ng-repeat="user in users">
                                <td>
                                    <a class="btn btn-default btn-xs" href="/users/edit/[[user.user_id]]">
                                        <i class="fa fa-pencil"></i> Edit
                                    </a>
                                </td>
                                <td>
                                    [[user.last_name]]
                                </td>
                                <td>
                                    [[user.first_name]]
                                </td>
                                <td>
                                    [[user.net_id]]
                                </td>
                                <td>
                                    [[user.email]]
                                </td>
                                <td>
                                    <span ng-if="!user.editingRole">
                                        [[user.roles[0] ? user.roles[0].display_name : "Faculty"]]

                                        <button class="btn btn-xs btn-default pull-right"
                                                ng-click="user.editingRole = true">
                                            <i class="fa fa-pencil"></i> Change
                                        </button>
                                    </span>

                                    <div class="role-input"
                                         ng-if="user.editingRole"
                                         ng-controller="ChangeRoleController">
                                        <select class="form-control"
                                                ng-model="selectedRole"
                                                ng-options="role.display_name for role in allRoles track by role.display_name">

                                        </select>

                                        <button class="btn btn-sm btn-success"
                                                ng-click="changeRole()">
                                            <i class="fa fa-check"></i> Change
                                        </button>
                                        <button class="btn btn-sm btn-default"
                                                ng-click="user.editingRole = false;">
                                            <i class="fa fa-times"></i> Cancel
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <button class="btn btn-xs btn-default pull-right"
                                            ng-if="!user.addingDept"
                                            ng-click="user.addingDept = true">
                                        <i class="fa fa-plus"></i> Add
                                    </button>

                                    <span ng-repeat="dept in user.departments | orderBy: 'department'">
                                        <i class="fa fa-times text-danger cursor-pointer"
                                           ng-confirm-click="removeDepartment(user, dept.department)"
                                           ng-confirm-click-message="Are you sure you want to remove [[user.first_name]] [[user.last_name]] from [[dept.department]]?"></i>
                                        [[dept.department]] <br>
                                    </span>

                                    <div class="dept-input"
                                         ng-if="user.addingDept"
                                         ng-controller="NewDepartmentController">
                                        <input type="text" class="form-control" placeholder="DEPT"
                                               ng-model="newDept"
                                               autofocus="true"
                                               ng-keypress="($event.keyCode == 13) ? addDepartment() : null">
                                        <button class="btn btn-sm btn-success"
                                                ng-disabled="!canAddDepartment()"
                                                ng-click="addDepartment()">
                                            <i class="fa fa-check"></i> Add
                                        </button>
                                        <button class="btn btn-sm btn-default"
                                                ng-click="user.addingDept = false; newDept='' ">
                                            <i class="fa fa-times"></i> Cancel
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            </tbody>
                            <tfoot>
                            <tr>
                                <td class="text-center" st-pagination="" st-items-by-page="10" colspan="7">
                                </td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@stop


@section('scripts-head')
    <script src="/javascripts/ng/app.users.js"></script>
@stop
