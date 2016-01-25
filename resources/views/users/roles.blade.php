@extends('layouts.master', [
    'breadcrumbs' => [
        ['Users', '/users'],
        ['Role Management'],
    ]
])

<style>
    .perm-input select {
        margin: 10px 0;
    }
</style>
<script>
    allPermissions = {!! $permissions !!}

</script>

@section('content')

    <div class="row" ng-controller="RolesController">
        <div class="col-lg-12">
            <a class="btn btn-primary"
                    ng-click="creatingRole = true"
                    ng-show="!creatingRole">
                <i class="fa fa-plus"></i> Create new role
            </a>
            <span ng-show="creatingRole">
                <input type="text"
                       class="form-control"
                       placeholder="New Role Name"
                       style="display: inline-block; width: 300px;"
                       ng-model="newRoleName">
                <button class="btn btn-sm btn-success"
                        ng-disabled="!newRoleName"
                        ng-click="createRole(newRoleName)">
                    <i class="fa fa-check"></i> Create Role
                </button>
                <button class="btn btn-sm btn-default"
                        ng-click="creatingRole = false; newRoleName = '';">
                    <i class="fa fa-times"></i> Cancel
                </button>
            </span>
            <br>
            <br>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-key fa-fw"></i> All Roles</h3>
                </div>
                <div class="panel-body">

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th width="30%">Role Name</th>
                                <th width="35%">Permissions</th>
                                <th width="35%"></th>
                            </tr>
                            </thead>
                            <tbody>

                                <tr ng-cloak
                                    ng-repeat="role in roles | orderBy: 'display_name'">
                                    <td>
                                        <i class="fa fa-times text-danger cursor-pointer"
                                           ng-if="role.numUsers == 0"
                                           title="Delete Role"
                                           ng-confirm-click="deleteRole(role)"
                                           ng-confirm-click-message="Are you sure you want to delete the [[role.display_name]] role?">
                                        </i>
                                        [[ role.display_name ]]
                                        <br>
                                        <ng-pluralize class="text-muted"
                                                      count="role.numUsers"
                                                      when="{'0': 'No users',
                                                             'one': '{} user',
                                                             'other': '{} users'}">
                                        </ng-pluralize>
                                    </td>
                                    <td >
                                        <ul style="list-style-type: none;">
                                            <li ng-repeat="permission in role.permissions | orderBy: 'display_name'">
                                                <i class="fa fa-times text-danger cursor-pointer"
                                                   title="Remove Permission"
                                                   ng-confirm-click="removePermission(role, permission)"
                                                   ng-confirm-click-message="Are you sure you want to remove the [[permission.display_name]] permission from the [[role.display_name]] role?"></i>
                                                [[permission.display_name]]
                                            </li>
                                        </ul>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-default "
                                                ng-if="!role.addingPermission"
                                                ng-click="role.addingPermission = true">
                                            <i class="fa fa-plus"></i> Add Permission
                                        </button>

                                        <div class="perm-input"
                                             ng-controller="AddPermissionController"
                                             ng-if="role.addingPermission">
                                            <select class="form-control"
                                                    ng-model="selectedPermission"
                                                    ng-options="permission.display_name for permission in allPermissions | notInArray:role.permissions:'id' | orderBy: 'display_name' track by permission.name">
                                                <option value="" disabled selected style="display: none;"> Select a Permission... </option>
                                            </select>
                                            <button class="btn btn-sm btn-success"
                                                    ng-disabled="!selectedPermission.name"
                                                    ng-click="addPermission()">
                                                <i class="fa fa-check"></i> Add Permission
                                            </button>
                                            <button class="btn btn-sm btn-default"
                                                    ng-click="role.addingPermission = false">
                                                <i class="fa fa-times"></i> [[addedOne ? "Done" : "Cancel"]]
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td>Faculty</td>
                                    <td><span class="text-muted">Faculty is the default role. It has no special permissions.</span></td>
                                    <td></td>
                                </tr>

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop


@section('scripts-head')
    <script src="/javascripts/ng/app.roles.js"></script>
@stop
