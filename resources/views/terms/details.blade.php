@extends('layouts.master', [
    'breadcrumbs' => [
        ['Terms', '/terms'],
        [$term->display_name],
    ]
])


@section('content')

    <div class="row" ng-controller="TermsController">
        <div class="col-lg-12">

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-calendar fa-fw"></i> Term Settings</h3>
                </div>
                <div class="panel-body">

                    <div class="col-lg-12">
                    </div>


                    <div class="col-lg-4 col-md-12">
                        @can('modify-courses')
                        <a href="/courses/create/{{$term->term_id}}" class="btn btn-primary">
                            <i class="fa fa-plus"></i> Create Course
                        </a>
                        &nbsp;
                        @endcan
                        {{--<a href="/terms/check/{{$term->term_id}}" class="btn btn-primary">View Checksheet</a>--}}
                        <a href="/reports/index/{{$term->term_id}}" class="btn btn-primary">
                            <i class="fa fa-bar-chart"></i> Create Report
                        </a> &nbsp;

                        <br>
                        <br>
                        <a href="/courses/index/{{$term->term_id}}" class="btn btn-primary">
                            <i class="fa fa-university"></i> View Courses
                        </a> &nbsp;

                        @can('modify-courses')
                        <a href="/terms/import/{{$term->term_id}}" class="btn btn-primary">
                            <i class="fa fa-upload"></i> Import Courses
                        </a>
                        @endcan

                        <br>
                        <br>
                        <br>

                        <dl class="dl-horizontal">
                            <dt>Term</dt>
                            <dd>{{ $term->term_name }}</dd>

                            <dt>Year</dt>
                            <dd>{{ $term->year }}</dd>

                            <dt>Request Start Date</dt>
                            <dd>{{ $term->order_start_date->toFormattedDateString() }}</dd>

                            <dt>Request Due Date</dt>
                            <dd>{{ $term->order_due_date->toFormattedDateString() }}</dd>

                            <dt>Status</dt>
                            <dd>{{ $term->status }}</dd>
                        </dl>
                    </div>

                    @can('edit-terms')
                    <form action="/terms/details/{{$term->term_id}}" method="POST">
                        {!! csrf_field() !!}


                        <div class="col-lg-4 col-md-6">
                            <h3>Request Start Date</h3>
                            <input type="hidden" name="order_start_date" ng-value="order_start_date | date:'yyyy-MM-dd'">

                            <uib-datepicker ng-model="order_start_date"
                                            ng-init="order_start_date = createDate('{{$term->order_start_date->toFormattedDateString()}}')"
                                            max-date="order_due_date"
                                            show-weeks="false"></uib-datepicker>
                        </div>

                        <div class="col-lg-4 col-md-6">
                            <h3>Request Due Date</h3>
                            <input type="hidden" name="order_due_date" ng-value="order_due_date  | date:'yyyy-MM-dd'">
                            <uib-datepicker ng-model="order_due_date"
                                            ng-init="order_due_date = createDate('{{$term->order_due_date->toFormattedDateString()}}')"
                                            min-date="order_start_date"
                                            show-weeks="false"></uib-datepicker>
                        </div>

                        <div class="col-lg-12 ">
                            <button type="submit" class="btn btn-success pull-right" style="margin-top: 15px;">
                                <i class="fa fa-check"></i> Save Dates
                            </button>
                        </div>
                    </form>
                    @endcan

                </div>
            </div>
        </div>
    </div>

@stop


@section('scripts-head')
    <script src="/javascripts/ui-bootstrap-tpls-0.14.3.min.js"></script>
    <script src="/javascripts/ng/app.terms.js"></script>
@stop
