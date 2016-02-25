@extends('layouts.master', [
    'breadcrumbs' => [
        ['Terms', '/terms'],
        ['All Terms'],
    ]
])


@section('content')

    <div class="row">
        <div class="col-lg-12">

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-calendar fa-fw"></i> Manage Terms</h3>
                </div>
                <div class="panel-body">

                    <div ng-controller="TermsTableController" class="table-responsive">
                        <table  st-pipe="callServer"
                                st-table="displayed"
                                class="table table-hover"
                                empty-placeholder>
                            <thead>
                            <tr>
                                <th st-sort="term">Term</th>
                                <th st-sort="year" st-sort-default="reverse">Year</th>
                                <th>Status</th>
                                <th st-sort="order_start_date">Request Start Date</th>
                                <th st-sort="order_due_date">Request Due Date</th>
                                <th width="1%"></th>
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
                                <tr ng-cloak ng-repeat="term in displayed">
                                    <td> [[ term.term_name ]] </td>
                                    <td> [[ term.year ]] </td>

                                    <td ng-class="{'text-muted': term.status.indexOf('eventually') > -1 || term.status == 'Concluded'}">
                                        [[ term.status ]]
                                    </td>

                                    <td> [[ term.order_start_date | moment:'ll' ]] </td>
                                    <td> [[ term.order_due_date | moment:'ll' ]] </td>
                                    <td style="width: 1%">
                                        <a href="/terms/details/[[ term.term_id ]]" class="btn btn-sm btn-primary">
                                            Details&nbsp; <i class="fa fa-arrow-right"></i>
                                        </a>
                                    </td>

                                </tr>
                            </tbody>
                            <tfoot>
                                <td class="text-center" st-pagination="" st-items-by-page="15" colspan="6">
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
    <script src="/javascripts/ui-bootstrap-tpls-0.14.3.min.js"></script>
    <script src="/javascripts/ng/app.terms.js"></script>
@stop
