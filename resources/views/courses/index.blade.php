@extends('layouts.master')

@section('area', 'Courses')
@section('page', 'All Courses')

@section('content')

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default"  ng-controller="CoursesController as cc">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-pencil fa-fw"></i> All Courses</h3>
                </div>
                <div class="form-group col-md-12">
                    <br>
                    <label>Select Term:
                    <select class="form-control" ng-model="TermSelected" ng-change="updateTerm(TermSelected)">
                        <option value="">All Terms</option>
                        @foreach($terms as $term)
                            <option value="<?php echo $term->term_id;?>">{{$term->termName()}} {{$term->year}}</option>
                        @endforeach
                    </select>
                    </label>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table st-pipe="cc.callServer" st-table="cc.displayed"
                               class="table table-bordered table-hover table-striped"
                               empty-placeholder>
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
                                    [[ course.department ]] [[ course.course_number | zpad:3 ]]-[[ course.course_section | zpad:2 ]]
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
    <script src="/javascripts/angular.min.js"></script>
    <script src="/javascripts/ng/smart-table/smart-table.min.js"></script>
    <script src="/javascripts/ng/app.js"></script>
    <script src="/javascripts/ng/helper.isbnHyphenate.js"></script>
    <script src="/javascripts/ng/app.courses.js"></script>
@stop
