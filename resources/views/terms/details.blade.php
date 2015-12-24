@extends('layouts.master')

@section('area', 'Terms')
@section('page', $term->termName() . ' ' . $term->year)

@section('content')

    <div class="row" ng-controller="TermsController">
        <div class="col-lg-12">

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-calendar fa-fw"></i> Term Settings</h3>
                </div>
                <div class="panel-body">

                    <div class="col-lg-4">
                        <dl class="dl-horizontal">
                            <dt>Term</dt>
                            <dd>{{ $term->termName() }}</dd>

                            <dt>Year</dt>
                            <dd>{{ $term->year }}</dd>

                            <dt>Order Start Date</dt>
                            <dd>{{ $term->order_start_date->toFormattedDateString() }}</dd>

                            <dt>Order Due Date</dt>
                            <dd>{{ $term->order_due_date->toFormattedDateString() }}</dd>
                        </dl>

                        <a href="/terms/check/{{$term->term_id}}" class="btn btn-primary">View Checksheet</a>
                    </div>

                    @can('edit-terms')
                    <form action="/terms/details/{{$term->term_id}}" method="POST">
                        {!! csrf_field() !!}


                        <div class="col-lg-4">
                            <h3>Order Start Date</h3>
                            <input type="hidden" name="order_start_date" ng-value="order_start_date | date:'yyyy-MM-dd'">

                            <uib-datepicker ng-model="order_start_date"
                                            ng-init="order_start_date = ('{{$term->order_start_date->toFormattedDateString()}}')"
                                            max-date="order_due_date"
                                            show-weeks="false"></uib-datepicker>
                        </div>

                        <div class="col-lg-4">
                            <h3>Order Due Date</h3>
                            <input type="hidden" name="order_due_date" ng-value="order_due_date  | date:'yyyy-MM-dd'">
                            <uib-datepicker ng-model="order_due_date"
                                            ng-init="order_due_date = createDate('{{$term->order_due_date->toFormattedDateString()}}')"
                                            min-date="order_start_date"
                                            show-weeks="false"></uib-datepicker>
                        </div>

                        <button type="submit" class="btn btn-success pull-right">
                            <i class="fa fa-check"></i> Save
                        </button>
                    </form>
                    @endcan

                </div>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-university fa-fw"></i> Term Courses</h3>
                </div>
                <div class="panel-body">
                    <?php  $courses = $term->courses()->paginate(10); ?>

                    <div ng-controller="TermsTableController as ttc" class="table-responsive"
                            ng-init="term_id = {{$term->term_id}}">
                        <table st-pipe="ttc.callServerDetail" st-table="ttc.displayed"
                               class="table table-hover"
                               empty-placeholder="No courses found for this term.">
                            <thead>
                            <tr>
                                <th st-sort="section">Section</th>
                                <th st-sort="course_name">Name</th>
                                <th width="1%"></th>
                            </tr>
                            <tr>
                                <th><input type="text" class="form-control" placeholder="Search..." st-search="section"/></th>
                                <th><input type="text" class="form-control" placeholder="Search..." st-search="name"/></th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                                <tr ng-repeat="course in ttc.displayed">
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
    <script src="/javascripts/ui-bootstrap-tpls-0.14.3.min.js"></script>
    <script src="/javascripts/ng/app.terms.js"></script>
@stop
