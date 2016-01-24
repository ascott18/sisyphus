@extends('layouts.master')

@section('area', 'Tickets')
@section('page', 'All Tickets')

@section('content')

    <div class="row">
        <div class="col-lg-12">

            <a class="btn btn-primary"
               href="/tickets/create">
                <i class="fa fa-plus"></i> Create Ticket
            </a>
            <br>
            <br>


            <div class="panel panel-default"  ng-controller="TicketsIndexController as tc">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-life-ring fa-fw"></i> All Tickets</h3>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table st-pipe="tc.callServer" st-table="tc.displayed"
                               class="table table-hover"
                               empty-placeholder>
                            <thead>
                            <tr>
                                TODO: Nathan add sorting
                                <th width="170px" >Title</th>
                                <th width="250px" >Status</th>
                                <th >Created By</th>
                                <th width="1%"></th>
                            </tr>
                            <tr>
                                <th>
                                    <input type="text" class="form-control" placeholder="Search..." st-search="section"/>
                                </th>
                                <th>
                                    <select class="form-control"  ng-init="TermSelected = ''" ng-model="TermSelected" ng-change="updateTerm()">
                                        <option value="">All Terms</option>
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

                            <tr ng-repeat="ticket in tc.displayed">
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
