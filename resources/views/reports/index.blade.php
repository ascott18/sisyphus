@extends('layouts.master')

@section('area', 'Reports')
@section('page', 'Select Report')

@section('content')

    <div class="row"
         ng-controller="ReportsController"
         ng-init="init({{$terms}},{{$departments}})"
    >
        <div class="col-lg-12" ng-show="getStage()== STAGE_SELECT_FIELDS">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-bar-chart"></i>What would you like to base your report on?</h3>
                </div>
                <div class="panel-body">

                    <div class="radio">
                        <label >
                            <input type="radio" ng-model="ReportType" ng-change="resetInclude()" value="orders" />
                            Requests
                        </label>
                    </div>
                    <div class="radio">
                        <label >
                            <input type="radio" ng-model="ReportType" ng-change="resetInclude()" value="courses" />
                            Courses
                        </label>
                    </div>
                </div>
            </div>
        </div>


        <div class="col-lg-12" ng-show="ReportType">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-bar-chart"></i>Create Report</h3>
                </div>
                <div class="panel-body">
                    <form novalidate class="simple-form">
                        <div class="row">
                            <div class="col-lg-7">
                                <h4>Select Term</h4>
                                <select class="form-control"
                                        ng-init="TermSelected=(terms | filter:{term_id: {{$currentTermId}}})[0]; onSelectTerm()"
                                        ng-model="TermSelected"
                                        ng-options="term.display_name for term in terms track by term.term_id"
                                        ng-change="onSelectTerm()">
                                </select>

                                <div ng-show="ReportType == 'orders'">
                                    <br><br>
                                    <h4>Choose a date range</h4>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <h3>Minimum Date</h3>
                                            <input type="hidden" name="reportDateStart" ng-value="reportDateStart | date:'yyyy-MM-dd'">

                                            <uib-datepicker ng-model="reportDateStart"
                                                            max-date="reportDateEnd"
                                                            show-weeks="false"></uib-datepicker>
                                        </div>

                                        <div class="col-md-6">
                                            <h3>Maximum Date</h3>
                                            <input type="hidden" name="reportDateEnd" ng-value="reportDateEnd  | date:'yyyy-MM-dd'" >
                                            <uib-datepicker ng-model="reportDateEnd"
                                                            min-date="reportDateStart"
                                                            show-weeks="false"></uib-datepicker>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-5 col-md-12">
                                <div class="row">
                                    <div class="col-sm-6">

                                        <h4>Select Department(s)</h4>
                                        <select class="form-control"
                                                multiple
                                                size="5"
                                                ng-multiple="true"
                                                ng-model="DeptsSelected"
                                                ng-options="dept for dept in departments">
                                        </select>
                                    </div>
                                    <div class="col-sm-6">
                                        <h4>Select Report Columns</h4>
                                        <select class="form-control"
                                                multiple
                                                size="12"
                                                ng-multiple="true"
                                                ng-model="ColumnsSelected"
                                                ng-options="optionProperties.name for optionProperties in options">
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div ng-show="ReportType == 'courses'">
                            <h4>What would you like included in your report?</h4>
                            (Must select at least one)
                            <br>

                            <div class="checkbox">
                                <label >
                                    <input type="checkbox" ng-model="include.submitted" />
                                    Courses that have submitted orders
                                </label>
                            </div>
                            <div class="checkbox">
                                <label >
                                    <input type="checkbox" ng-model="include.notSubmitted" />
                                    Courses that have not submitted orders
                                </label>
                            </div>
                            <div class="checkbox">
                                <label >
                                    <input type="checkbox" ng-model="include.noBook" />
                                    Courses that specified no book
                                </label>
                            </div>
                        </div>
                        <div ng-show="ReportType == 'orders'">
                            <h4>What would you like included in your report?</h4>
                            (Must select at least one)
                            <br>

                            <div class="checkbox">
                                <label >
                                    <input type="checkbox" ng-model="include.nondeleted" />
                                    Placed Requests
                                </label>
                            </div>
                            <div class="checkbox">
                                <label >
                                    <input type="checkbox" ng-model="include.deleted" />
                                    Deleted Requests
                                </label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success"
                        ng-click="submit()" ng-disabled="!isCheckboxChecked()">Submit <span class="fa fa-arrow-right"></span></button>
                    </form>
                </div>
            </div>
        </div>



        <div class="col-lg-12" ng-show="getStage() == STAGE_CREATE_REPORT">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-bar-chart"></i>Create Report</h3>
                </div>
                <div class="panel-body">
                    <button class="btn btn-primary"
                            ng-csv="reportRows"
                            csv-header="getReportHeaderRow()"
                            filename="report.csv">
                        <i class="fa fa-download"></i> Download CSV
                    </button>

                    <table class="table table-hover" id="reportTable">
                        <thead>
                            <tr>
                                <th
                                    ng-repeat="optionProperties in ColumnsSelected">
                                    [[optionProperties.name]]
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr ng-repeat="row in reportRows" >
                                <td ng-repeat="cell in row track by $index">
                                    [[cell]]
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@stop

@section('scripts-head')
    <script src="/javascripts/angular-sanitize.min.js"></script>
    <script src="/javascripts/ng/csv/ng-csv.min.js"></script>
    <script src="/javascripts/ng/helper.isbnHyphenate.js"></script>
    <script src="/javascripts/ng/app.reports.js"></script>
    <script src="/javascripts/ui-bootstrap-tpls-0.14.3.min.js"></script>
@stop