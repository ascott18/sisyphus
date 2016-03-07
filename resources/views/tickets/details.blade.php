@extends('layouts.master', [
    'breadcrumbs' => [
        ['Tickets', '/tickets'],
        [$ticket->title, '.'],
    ]
])


@section('content')

    <div class="row" ng-controller="TicketController as tc" ng-init="setTicket({{$ticket}})">
        <div class="col-lg-7" >
            <div class="panel panel-default" >
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-life-ring fa-fw"></i>
                        [[ ticket.title ]]
                    </h3>
                </div>

                <div ta-bind class="panel-body" ng-model="ticket.body"></div>

            </div>


            <form novalidate class="simple-form" name="form" >

                <div ng-class="col-md-12">
                    <div class="panel panel-default">

                        <div class="panel-heading">
                            <h3 class="panel-title"><i class="fa fa-comments fa-fw"></i> Comments</h3>
                        </div>

                        <div class="panel-body panel-list">
                            <div class="panel-list-item clearfix"
                                 ng-repeat="comment in comments">

                                <div style="padding-left: 10px; display: table-cell; vertical-align: top;">
                                    <small >
                                        <span>
                                            <b>[[comment.user.first_name]] [[comment.user.last_name]]</b>
                                            <span class="text-muted" >
                                                on <moment>[[comment.created_at ]] </moment>
                                            </span>
                                        </span>
                                    </small>
                                </div>

                                <div class="col-md-12">
                                    [[comment.body]]
                                </div>
                            </div>

                            {{--New Ticket Comment--}}

                            <div class="panel-list-item clearfix">

                                <div class="form-group">
                                    <input class="form-control" type="text" placeholder="Your comments" ng-model="comment.body"/>

                                </div>


                                <div class="form-group pull-left">

                                    <select class="form-control"
                                            ng-options="status as status.value for status in statuses track by status.key"
                                            ng-model="statusSelected">
                                    </select>

                                </div>

                                <div class="form-group pull-right">
                                    <button class="btn btn-success"
                                            ng-click="submitComment()">Add</button>
                                </div>



                            </div>

                        </div>

                    </div>
                </div>
            </form>

        </div>
        <div class="col-lg-5">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-info fa-fw"></i>
                        Details
                    </h3>
                </div>
                <div class="panel-body">

                    <dl class="col-md-12 dl-horizontal">
                        <dt>Assigned To</dt>
                        <dd>
                            [[ ticket['assignedToDisplay'] ]]
                        </dd>

                        <dt ng-if="ticket.urlValid">Linked To</dt>
                        <dd ng-if="ticket.urlValid">
                            <a ng-if="ticket.urlValid" ng-href="[[ ticket['url'] ]]">
                                [[ ticket['url'] ]]
                            </a>
                        </dd>

                        <dt>Created By</dt>
                        <dd>
                            [[ ticket.user['last_first_name'] ]]
                        </dd>

                        <dt>Created On</dt>
                        <dd>
                            [[ ticket['created_at'] ]]
                        </dd>

                        <dt>Email</dt>
                        <dd>
                            [[ ticket.user['email'] ]]
                        </dd>

                        <dt>Status</dt>
                        <dd>
                        <span ng-class="{'label label-default': ticket['status'] == 0,
                                         'label label-primary': ticket['status'] == 1,
                                         'label label-success': ticket['status'] == 2}">
                            [[ ticket['status'] | status ]]
                        </span>

                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>


@stop

@section('scripts-head')
    <script src="/javascripts/ng/app.tickets.js"></script>
@stop