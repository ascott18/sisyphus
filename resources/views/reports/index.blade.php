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
                    <form novalidate class="simple-form">
                        <div class="row">
                                <h4>Select Term</h4>
                                <select class="form-control"
                                        ng-init="TermSelected=(terms | filter:{term_id: {{$currentTermId}}})[0]"
                                        ng-model="TermSelected"
                                        ng-options="term.display_name for term in terms track by term.term_id"
                                        ng-change="onSelectTerm()">
                                    <option hidden value=''>Custom</option>
                                </select>
                            <h4>-OR-</h4>
                                <h4>Choose a date range</h4>
                                <div class="col-lg-4 col-md-6" >
                                    <h3>Order Start Date</h3>
                                    <input type="hidden" name="reportDateStart" ng-value="reportDateStart | date:'yyyy-MM-dd'">

                                    <uib-datepicker ng-model="reportDateStart"
                                                    ng-init="reportDateStart = TermSelected.order_start_date"
                                                    ng-change="TermSelected=''"
                                                    max-date="reportDateEnd"
                                                    show-weeks="false"></uib-datepicker>
                                </div>

                                <div class="col-lg-4 col-md-6">
                                    <h3>Order Due Date</h3>
                                    <input type="hidden" name="reportDateEnd" ng-value="reportDateEnd  | date:'yyyy-MM-dd'" >
                                    <uib-datepicker ng-model="reportDateEnd"
                                                    ng-init="reportDateEnd = TermSelected.order_due_date"
                                                    ng-change="TermSelected=''"
                                                    min-date="reportDateStart"
                                                    show-weeks="false"></uib-datepicker>
                                </div>
                        </div>
                        <h4>Data</h4>
                            <ul class="list-group">
                                @foreach($options as $option=>$option_name)
                                    <li class="list-group-item cursor-pointer"
                                        ng-class="{active: isColumnSelected({{$option}})}"
                                        ng-click="toggleColumn({{$option}})">{{$option_name}}</li>
                                @endforeach
                            </ul>

                        <h4>What would you like included in your report?</h4>
                            <input type="checkbox" ng-model="include.deleted" ng-checked="true">Deleted Orders<br>
                            <input type="checkbox" ng-model="include.nondeleted" ng-checked="true">Non-Deleted Orders<br>
                            <input type="checkbox" ng-model="include.submitted" ng-checked="true">Classes that have submitted orders<br>
                            <input type="checkbox" ng-model="include.notSubmitted" ng-checked="true">Classes that have not submitted orders<br>
                            <input type="checkbox" ng-model="include.noBook" ng-checked="true">Classes that specified no book<br><br>

                        <button type="submit" class="btn btn-success"
                        ng-click="submit()">Submit <span class="fa fa-arrow-right"></span></button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@stop

@section('scripts-head')
    <script src="/javascripts/ng/app.reports.js"></script>
    <script src="/javascripts/ui-bootstrap-tpls-0.14.3.min.js"></script>
@stop