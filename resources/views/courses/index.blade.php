@extends('layouts.master')

@section('area', 'Courses')
@section('page', 'All Courses')

@section('content')

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-pencil fa-fw"></i> All Courses</h3>
                </div>
                <div class="panel-body">
                    <div ng-controller="CoursesController as cc" class="table-responsive">
                        <table st-pipe="cc.callServer" st-table="cc.displayed" class="table table-bordered table-hover table-striped">
                            <thead>
                            <tr>
                                <th st-sort="section">Section</th>
                                <th st-sort="course_name">Name</th>
                                <th width="110px">Details</th>
                            </tr>
                            <tr>
                                <th><input type="text" class="form-control" placeholder="Search..." st-search="section"/></th>
                                <td><input type="text" class="form-control" placeholder="Search..." st-search="name"/></td>
                                <td></td>
                            </tr>
                            </thead>
                            <tbody>

                            <tr ng-repeat="course in cc.displayed">
                                <td>
                                    [[ course.department ]] [[ course.course_number ]]-[[ course.course_section ]]
                                </td>
                                <td>[[ course.course_name ]]</td>
                                <td><a class="btn btn-sm btn-info" href="/courses/details/[[course.course_id]]" role="button">
                                        Details <i class="fa fa-arrow-right"></i>
                                    </a>
                                </td>
                            </tr>

                            </tbody>
                            <tfoot>
                            <tr>
                                <td class="text-center" st-pagination="" st-items-by-page="10" colspan="4">
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
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.4.5/angular.min.js"></script>
    <script src="/javascripts/ng/smart-table/smart-table.min.js"></script>
    <script src="/javascripts/ng/app.js"></script>
    <script src="/javascripts/ng/app.courses.js"></script>
@stop
