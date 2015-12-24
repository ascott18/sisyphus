@extends('layouts.master')

@section('area', 'Users')
@section('page', 'Role Management')

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
                                        [[ role.display_name ]]
                                    </td>
                                    <td >

                                        <span ng-repeat="permission in role.permissions | orderBy: 'display_name'">
                                            <i class="fa fa-times text-danger cursor-pointer"
                                               ng-confirm-click="removePermission(role, permission)"
                                               ng-confirm-click-message="Are you sure you want to remove the [[permission.display_name]] permission from the [[role.display_name]] role?"></i>
                                            [[permission.display_name]] <br>
                                        </span>
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
                                                    ng-options="permission.display_name for permission in allPermissions | notInArray:role.permissions:'id' track by permission.name">
                                                <option value="" disabled selected style="display: none;"> Select a Permission... </option>
                                            </select>
                                            <button class="btn btn-sm btn-success"
                                                    ng-disabled="!selectedPermission.name"
                                                    ng-click="addPermission()">
                                                <i class="fa fa-check"></i> Add Permission
                                            </button>
                                            <button class="btn btn-sm btn-default"
                                                    ng-click="role.addingPermission = false">
                                                <i class="fa fa-times"></i> Cancel
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
    <script src="/javascripts/angular.min.js"></script>
    <script src="/javascripts/ui-bootstrap-tpls-0.14.3.min.js"></script>
    <script src="/javascripts/ng/app.js"></script>
    <script src="/javascripts/ng/app.roles.js"></script>
@stop
