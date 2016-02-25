@extends('layouts.master', [
    'breadcrumbs' => [
        ['Tickets', '/tickets'],
        ['All Tickets'],
    ]
])


@section('content')

    <div class="row">
        <div class="col-lg-12">
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
                                    <select class="form-control"
                                            ng-options="status as status.value for status in statuses track by status.key"
                                            ng-model="statusSelected"
                                            ng-change="updateStatus()" >
                                        <option value="">All Statuses</option>
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
                                    <td>[[ ticket.status | status ]]</td>
                                    <td>user name stuff NATHAN DO THIS I GUESS</td>
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

@stop
