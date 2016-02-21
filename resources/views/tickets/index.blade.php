@extends('layouts.master', [
    'breadcrumbs' => [
        ['Tickets', '/tickets'],
        ['All Tickets'],
    ]
])


@section('content')

    <div class="row">
        <div class="col-lg-12">
            <br>
            <br>

            <div class="panel panel-default"  ng-controller="TicketsIndexController">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-life-ring fa-fw"></i> All Tickets</h3>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table st-pipe="callServer" st-table="displayed"
                               class="table table-hover"
                               empty-placeholder>
                            <thead>
                            <tr>
                                TODO: Nathan add sorting
                                <th width="170px" st-sort="title" >Title</th>
                                <th width="250px" >Status</th>
                                <th st-sort="name">Created By</th>
                                <th width="1%"></th>
                            </tr>
                            <tr>
                                <th>
                                    <input type="text" class="form-control" placeholder="Search..." st-search="title"/>
                                </th>
                                <th>
                                    <select class="form-control"  ng-init="statusSelected = ''" ng-model="statusSelected" ng-change="updateStatus()">
                                        <option value="">All Statuses</option>
                                            <option ng-repeat="status in statuses" value="status">
                                                [[ status ]]
                                            </option>

                                    </select>
                                </th>
                                <th>
                                    <input type="text" class="form-control" placeholder="Search..." st-search="name"/>
                                </th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>

                            <tr ng-cloak ng-repeat="ticket in displayed">
                                <td>
                                    [[ ticket.title ]]
                                </td>
                                <td>[[ ticket.status_display ]]</td>
                                <td>user name stuff</td>
                                <td><a class="btn btn-sm btn-info" href="/tickets/details/[[ticket.ticket_id]]" role="button">
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
    <script src="/javascripts/ng/app.tickets.js"></script>
    <script src="/javascripts/ng/pagination/dirPagination.js"></script>

    <script src='/javascripts/ng/text/textAngular-rangy.min.js'></script>
    <script src='/javascripts/ng/text/textAngular-sanitize.min.js'></script>
    <script src='/javascripts/ng/text/textAngular.min.js'></script>
@stop
