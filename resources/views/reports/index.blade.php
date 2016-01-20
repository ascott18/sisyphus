@extends('layouts.master')

@section('area', 'Reports')
@section('page', 'Select Report')

@section('content')

    <div class="row"
         ng-controller="ReportsController"
         ng-init="terms = {{$terms}}"
    >
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-bar-chart"></i>Create Report</h3>
                </div>
                <div class="panel-body">
                    <h4>Select Term</h4>
                    <select class="form-control"
                            ng-init="TermSelected='(terms | filter:{term_id: {{$currentTermId}}})[0]'"
                            ng-model="TermSelected"
                            ng-options="term.display_name for term in terms track by term.term_id">
                        <option hidden value=""></option>
                    </select>
                    <h4>-OR-</h4>
                    <h4>Choose a date range</h4>
                    @foreach($terms as $term)
                        <div class="col-lg-4 col-md-6" ng-if="TermSelected=='{{$term->term_id}}'">
                            <h3>Order Start Date</h3>
                            <input type="hidden" name="order_start_date" ng-value="order_start_date | date:'yyyy-MM-dd'">

                            <uib-datepicker ng-model="order_start_date"
                                            ng-init="order_start_date = createDate('{{$term->order_start_date->toFormattedDateString()}}')"
                                            max-date="order_due_date"
                                            show-weeks="false"></uib-datepicker>
                        </div>

                        <div class="col-lg-4 col-md-6" ng-if="TermSelected=='{{$term->term_id}}'">
                            <h3>Order Due Date</h3>
                            <input type="hidden" name="order_due_date" ng-value="order_due_date  | date:'yyyy-MM-dd'" >
                            <uib-datepicker ng-model="order_due_date"
                                            ng-init="order_due_date = createDate('{{$term->order_due_date->toFormattedDateString()}}')"
                                            min-date="order_start_date"
                                            show-weeks="false"></uib-datepicker>
                        </div>
                    @endforeach
                    <h4>Data</h4>
                    <ul class="list-group">
                        @foreach($options as $option)
                            <li class="list-group-item">{{$option}}</li>
                        @endforeach
                    </ul>

                </div>
            </div>
        </div>
    </div>

@stop

@section('scripts-head')
    <script src="/javascripts/ng/app.reports.js"></script>
    <script src="/javascripts/ui-bootstrap-tpls-0.14.3.min.js"></script>
@stop