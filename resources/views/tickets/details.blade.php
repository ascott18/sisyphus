@extends('layouts.master', [
    'breadcrumbs' => [
        ['Tickets', '/tickets'],
        [$ticket->title, '.'],
    ]
])


@section('content')

    <div class="row">
        <div class="col-lg-7" ng-controller="TicketController as tc">
            <div class="panel panel-default" ng-init="setTicket({{$ticket}})">
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
                                            <b>[[comment.author.first_name]] [[comment.author.last_name]]</b>
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

                            <div class="panel-list-item clearfix">
                                <div class="form-group">
                                    <input class="form-control" type="text" placeholder="Your comments" ng-model="comment.body"/>
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
            <ticket-details
                    ticket="{{$ticket}}"
                    author="{{$ticket->user}}">
            </ticket-details>
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