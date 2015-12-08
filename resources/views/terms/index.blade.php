@extends('layouts.master')

@section('area', 'Terms')
@section('page', 'All Terms')

@section('content')

    <div class="row">
        <div class="col-lg-12">

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-pencil fa-fw"></i> Manage Terms</h3>
                </div>
                <div class="panel-body">

                    <div ng-controller="TermsTableController as tc" class="table-responsive">
                        <table  st-pipe="tc.callServer" st-table="tc.displayed" class="table table-bordered table-hover table-striped"
                                empty-placeholder>
                            <thead>
                            <tr>
                                <th st-sort="term">Term</th>
                                <th>Year</th>
                                <th>Status</th>
                                <th st-sort="order_start_date">Order Start Date</th>
                                <th st-sort="order_due_date">Order Due Date</th>
                                <th>Details</th>
                            </tr>
                            <tr>
                                <th><input type="text" class="form-control" placeholder="Search..." st-search="term"/></th>
                                <th><input type="text" class="form-control" placeholder="Search..." st-search="year"/></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                                <tr ng-repeat="term in tc.displayed">
                                    <td> [[ term.termName ]] </td>
                                    <td> [[ term.year ]] </td>

                                    <td> [[ term.status ]] </td>

                                    <td> [[ term.orderStartDate ]] </td>
                                    <td> [[ term.orderDueDate ]] </td>
                                    <td style="width: 1%">
                                        <a href="/terms/details/[[ term.term_id ]]" class="btn btn-sm btn-primary">
                                            Details&nbsp; <i class="fa fa-arrow-right"></i>
                                        </a>
                                    </td>

                                </tr>
                            </tbody>
                            <tfoot>
                            <td class="text-center" st-pagination="" st-items-by-page="10" colspan="6">
                            </td>
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
    <script src="/javascripts/ui-bootstrap-tpls-0.14.3.min.js"></script>
    <script src="/javascripts/ng/smart-table/smart-table.min.js"></script>
    <script src="/javascripts/ng/app.js"></script>
    <script src="/javascripts/ng/app.terms.js"></script>
@stop
