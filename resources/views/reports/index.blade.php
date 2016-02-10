@extends('layouts.master', [
    'breadcrumbs' => [
        ['Reports', '/reports'],
        ['Create Reports'],
    ]
])

@section('content')

    <div class="row"
         ng-controller="ReportsController"
         ng-init="init({{$terms}},{{$departments}})"
    >
        <div class="col-lg-12" ng-show="getStage() == STAGE_SELECT_FIELDS">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-bar-chart"></i> What would you like to base your report on?</h3>
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


        <div ng-cloak class="col-lg-12" ng-show="ReportType && getStage() == STAGE_SELECT_FIELDS">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-bar-chart"></i> Create Report</h3>
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div ng-class="[ReportType == 'orders' ? 'col-lg-7' : 'col-lg-12']">
                            <h4>Select Term</h4>
                            <select class="form-control"
                                    ng-init="TermSelected=(terms | filter:{term_id: {{$currentTermId}}})[0]; onSelectTerm()"
                                    ng-model="TermSelected"
                                    ng-options="term.display_name for term in terms track by term.term_id"
                                    ng-change="onSelectTerm()">
                            </select>

                            <div ng-show="ReportType == 'orders'">
                                <br>
                                <div class="row">
                                    <div class="col-md-6">
                                        <h4>Minimum Date</h4>
                                        <input type="hidden" name="reportDateStart" ng-value="reportDateStart | date:'yyyy-MM-dd'">

                                        <uib-datepicker ng-model="reportDateStart"
                                                        max-date="reportDateEnd"
                                                        show-weeks="false"></uib-datepicker>
                                    </div>

                                    <div class="col-md-6">
                                        <h4>Maximum Date</h4>
                                        <input type="hidden" name="reportDateEnd" ng-value="reportDateEnd  | date:'yyyy-MM-dd'" >
                                        <uib-datepicker ng-model="reportDateEnd"
                                                        min-date="reportDateStart"
                                                        show-weeks="false"></uib-datepicker>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-5 col-md-12 col-md-vspace col-lg-vspace">
                            <div class="row">
                                <div class="col-sm-6">

                                    <h4>Select Subject(s)</h4>
                                    <select class="form-control"
                                            multiple
                                            size="12"
                                            ng-multiple="true"
                                            ng-model="DeptsSelected"
                                            ng-options="dept for dept in departments|orderBy:dept">
                                    </select>
                                </div>
                                <div class="col-sm-6 col-sm-vspace">
                                    <h4>Select Report Columns</h4>
                                    <select class="form-control"
                                            multiple
                                            size="12"
                                            ng-multiple="true"
                                            ng-model="ColumnsSelected"
                                            ng-options="optionProperties.name for optionProperties in options | filter:{doesAutoEnforce: '!true'} | filter:shouldOptionShow">
                                    </select>
                                </div>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" ng-model="groupBySection" />
                                    Condense Similar Rows
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-12 col-xs-vspace">
                        <h4>What would you like included in your report?</h4>
                        <p class="text-muted">(Must select at least one)</p>
                        <br>

                        <div ng-show="ReportType == 'courses'">
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
                                ng-click="submit()"
                                ng-disabled="!isCheckboxChecked()">
                            Submit <i class="fa fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>



        <div ng-cloak class="col-lg-12" ng-if="getStage() == STAGE_VIEW_REPORT">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-bar-chart"></i>
                        [[TermSelected.display_name]] [[ReportType == 'orders' ? 'Requests' : 'Courses' ]] Report. Created [[reportCreatedDate | moment:'ll']].
                        <span ng-show="ReportType == 'orders'">
                            Data range: [[ reportDateStart | moment:'ll' ]] - [[ reportDateEnd | moment:'ll' ]]
                        </span>
                    </h3>
                </div>
                <div class="panel-body">
                    <button class="btn btn-primary"
                            style="margin-right: 15px"
                            ng-click="setStage(STAGE_SELECT_FIELDS)">
                        <i class="fa fa-arrow-left"></i> Change Settings
                    </button>
                    <button class="btn btn-primary"
                            ng-csv="reportRows"
                            ng-disabled="!reportRows || reportRows.length == 0"
                            csv-header="getReportHeaderRow()"
                            filename="[[getReportCsvFileName()]]">
                        <i class="fa fa-download"></i> Download CSV
                    </button>


                    <style>
                        @media print{
                            body {
                                font-size: 8pt;
                            }
                            .panel-body > button.btn {
                                display: none;
                            }
                        }

                        #reportTable th,
                        #reportTable td {
                            padding: 2px 5px;
                        }
                    </style>

                    <br>
                    <br>
                    <table class="table table-hover" id="reportTable" super-fast-table table-data="reportRows">
                        <thead>
                            <tr>
                                <th ng-repeat="optionProperties in ColumnsSelected"
                                    ng-style="{width: optionProperties.width || ''}">
                                    [[optionProperties.name]]
                                </th>
                            </tr>
                        </thead>
                        <tbody >
                            {{-- Will be replaced by the super-fast-table directive --}}
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