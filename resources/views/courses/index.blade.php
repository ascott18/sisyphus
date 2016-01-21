@extends('layouts.master')

@section('area', 'Courses')
@section('page', 'All Courses')

@section('content')

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default"  ng-controller="CoursesIndexController as cc">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-university fa-fw"></i> All Courses</h3>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table st-pipe="cc.callServer" st-table="cc.displayed"
                               class="table table-hover"
                               empty-placeholder>
                            <thead>
                            <tr>
                                <th width="170px" st-sort="section">Section</th>
                                <th width="250px" st-sort="term_id" st-sort-default="reverse">Term</th>
                                <th st-sort="course_name">Name</th>
                                <th st-sort="professor">Professor</th>
                                <th>Resoponded</th>
                                <th width="1%"></th>
                            </tr>
                            <tr>
                                <th>
                                    <input type="text" class="form-control" placeholder="Search..." st-search="section"/>
                                </th>
                                <th>
                                    <select class="form-control" ng-init="TermSelected = ''" ng-model="TermSelected" ng-change="updateTerm()">
                                        <option value="">All Terms</option>
                                        @foreach($terms as $term)
                                            <option value="{{$term->term_id}}">{{$term->display_name}}</option>
                                        @endforeach
                                    </select>
                                </th>
                                <th>
                                    <input type="text" class="form-control" placeholder="Search..." st-search="name"/>
                                </th>
                                <th>
                                    <input type="text" class="form-control" placeholder="Search..." st-search="professor"/>
                                </th>
                                <th></th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>

                            <tr ng-repeat="course in cc.displayed">
                                <td>
                                    [[ course.department ]] [[ course.course_number | zpad:3 ]]-[[ course.course_section | zpad:2 ]]
                                </td>
                                <td>[[ course.term.term_name ]] [[ course.term.year ]]</td>
                                <td>[[ course.course_name ]]</td>
                                <td>[[ course.user.last_name ]], [[ course.user.first_name ]]</td>
                                <td>
                                    <div ng-show="course.order_count > 0 || course.no_book != 0">Yes</div>
                                    <div ng-show="course.order_count == 0 && course.no_book == 0">No</div>
                                </td>
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
    <script src="/javascripts/ng/helper.isbnHyphenate.js"></script>
    <script src="/javascripts/ng/pagination/dirPagination.js"></script>
    <script src="/javascripts/ng/app.courses.js"></script>
@stop
