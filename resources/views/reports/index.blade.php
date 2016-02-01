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
                    <h3 class="panel-title"><i class="fa fa-bar-chart"></i>Choose Report Type</h3>
                </div>
                <div class="panel-body">
                    <form>
                        <input type="radio" ng-model="ReportType" ng-change="setNonDeleted()" value="requests">
                            Requests
                        </input></br>
                        <input type="radio" ng-model="ReportType" ng-change="setDeleted()" value="deleted">
                            Deleted Requests
                        </input></br>
                        <input type="radio" ng-model="ReportType" ng-change="resetInclude()" value="orders">
                            Courses with requests/Courses without requests/courses that chose no book
                        </input></br></br>
                    </form>
                </div>
            </div>
        </div>


        <div class="col-lg-12" ng-show="ReportType!=null">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-bar-chart"></i>Create Report</h3>
                </div>
                <div class="panel-body">
                    <form novalidate class="simple-form">
                        <div class="row">
                                <h4>Select Term</h4>
                                <select class="form-control"
                                        ng-init="TermSelected=(terms | filter:{term_id: {{$currentTermId}}})[0]; onSelectTerm()"
                                        ng-model="TermSelected"
                                        ng-options="term.display_name for term in terms track by term.term_id"
                                        ng-change="onSelectTerm()">
                                </select>

                                <h4>Choose a date range</h4>
                                <div class="col-lg-4 col-md-6" >
                                    <h3>Order Start Date</h3>
                                    <input type="hidden" name="reportDateStart" ng-value="reportDateStart | date:'yyyy-MM-dd'">

                                    <uib-datepicker ng-model="reportDateStart"
                                                    max-date="reportDateEnd"
                                                    show-weeks="false"></uib-datepicker>
                                </div>

                                <div class="col-lg-4 col-md-6">
                                    <h3>Order Due Date</h3>
                                    <input type="hidden" name="reportDateEnd" ng-value="reportDateEnd  | date:'yyyy-MM-dd'" >
                                    <uib-datepicker ng-model="reportDateEnd"
                                                    min-date="reportDateStart"
                                                    show-weeks="false"></uib-datepicker>
                                </div>
                        </div>
                        </br>
                        <h4>Select a department</h4>
                        <select class="form-control" ng-model="DeptSelected" ng-init="">
                            <option value="">All Courses</option>
                            <option ng-repeat="dept in departments" value=[[dept]]>[[dept]]</option>
                        </select>
                        </br>


                        <h4>Data</h4>
                        <ul class="list-group">
                            <div ng-repeat="option in options">
                                <li class="list-group-item cursor-pointer"
                                    ng-class="{active: isColumnSelected(option)}"
                                    ng-click="toggleColumn(option)">[[option]]</li>
                            </div>
                        </ul>

                        <div ng-show="ReportType == 'orders'">
                            <h4>What would you like included in your report?</h4>
                            (Must select at least one)</br></br>
                            <input type="checkbox" ng-model="include.submitted" >Classes that have submitted orders<br>
                            <input type="checkbox" ng-model="include.notSubmitted" >Classes that have not submitted orders<br>
                            <input type="checkbox" ng-model="include.noBook" >Classes that specified no book<br><br>
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

                    <a class="pure-button pure-button-primary" download="report.html" href=[[downloadLink]]>HTML Download</a>
                    <button type="submit" class="btn btn-primary"
                            >HTML <span class="fa fa-arrow-right"></span></button>
                    <button type="submit" class="btn btn-primary"
                            >CSV <span class="fa fa-arrow-right"></span></button>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th ng-if="isColumnSelected('Course Title')">
                                    Course Title
                                </th>
                                <th ng-if="isColumnSelected('Course Number')">
                                    Course Number
                                </th>
                                <th ng-if="isColumnSelected('Course Section')">
                                    Course Section
                                </th>
                                <th ng-if="isColumnSelected('Course Department')">
                                    Course Department
                                </th>
                                <th ng-if="isColumnSelected('Instructor')">
                                    Instructor
                                </th>
                                <th ng-if="isColumnSelected('Book Title')">
                                    Book Title
                                </th>
                                <th ng-if="isColumnSelected('Author')">
                                    Author
                                </th>
                                <th ng-if="isColumnSelected('Edition')">
                                    Edition
                                </th>
                                <th ng-if="isColumnSelected('ISBN')">
                                    ISBN
                                </th>
                                <th ng-if="isColumnSelected('Publisher')">
                                    Publisher
                                </th>
                                <th ng-if="isColumnSelected('Required')">
                                    Required
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr ng-repeat="row in reportData" >
                                <td ng-if="isColumnSelected('Course Title')">
                                    [[row.course.course_name]]
                                </td>
                                <td ng-if="isColumnSelected('Course Number')">
                                    [[row.course.course_number]]
                                </td>
                                <td ng-if="isColumnSelected('Course Section')">
                                    [[row.course.course_section]]
                                </td>
                                <td ng-if="isColumnSelected('Course Department')">
                                    {{--TODO--}}
                                </td>
                                <td ng-if="isColumnSelected('Instructor')">
                                    [[row.order.placed_by]]
                                </td>

                                <td ng-if="isColumnSelected('Book Title')">
                                    [[row.order.book.title]]
                                </td>
                                <td ng-if="isColumnSelected('Author')">
                                    <div ng-repeat="author in row.order.book.authors">
                                        [[author.name]]
                                    </div>
                                </td>
                                <td ng-if="isColumnSelected('Edition')">
                                    [[row.order.book.edition]]
                                </td>
                                <td ng-if="isColumnSelected('ISBN')">
                                    [[row.order.book.isbn13]]
                                </td>
                                <td ng-if="isColumnSelected('Publisher')">
                                    [[row.order.book.publisher]]
                                </td>
                                <td ng-if="isColumnSelected('Required')">
                                    [[row.order.required]]
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
    <script src="/javascripts/ng/app.reports.js"></script>
    <script src="/javascripts/ui-bootstrap-tpls-0.14.3.min.js"></script>
@stop