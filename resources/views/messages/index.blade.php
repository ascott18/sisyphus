@extends('layouts.master')

@section('content')
    @include('shared.partial.header', ['headerText'=>'Messages', 'subHeaderText'=>'Create and send'])

    <div class="row" ng-app="sisyphus" ng-controller="MessagesController" xmlns="http://www.w3.org/1999/html">
        <div ng-show="stage == STAGE_COMPOSE">

            <div class="col-md-4 ">
                <ul class="list-group">
                    <li class="list-group-item cursor-pointer list-group-item-success"
                        ng-click="newMessage()">

                        <h4 class="list-group-item-heading no-pad-bottom ">
                            <i class="fa fa-plus fa-align-middle text-success" style="font-size: 1.5em"></i>&nbsp;
                             Create New Message</h4>
                    </li>

                    <div ng-show="reloadingMessages > 0" style="margin-top: 15px;">
                        <i class="fa fa-spinner fa-spin fa-3x" style="margin-left: 48%"></i>
                    </div>

                    <li class="list-group-item cursor-pointer"
                         ng-cloak
                         ng-class="{active: isMessageSelected(message)}"
                         ng-click="selectMessage(message)"
                         ng-repeat="message in messages | orderBy : ((message.isNew?10e10:0) + message.lastSent):true">

                        <div class="pull-right">
                            <button class="btn btn-xs btn-danger"
                                    ng-confirm-click="deleteMessage(message)"
                                    ng-confirm-click-message="Are you sure you want to delete message [[message.subject]]?">
                                <i class="fa fa-fw fa-trash"></i>
                            </button>
                            <button class="btn btn-xs btn-primary"
                                    ng-click="newMessage(message)">
                                <i class="fa fa-fw fa-copy"></i>
                            </button>
                        </div>

                        <h4 class="list-group-item-heading no-pad-bottom">[[message.subject]]</h4>
                        <small >
                            <span class="text-muted" ng-show="message['lastSent']">Sent On</span>
                            <span class="text-muted" ng-show="!message['lastSent']">Never Sent</span>
                            <span ng-bind="message.lastSent | date:'MM/dd/yyyy'"></span>
                        </small>

                    </li>
                </ul>
            </div>

            <div class="col-md-8" ng-cloak ng-show="anyMessageSelected()">

                <div class="clearfix">
                    <button class="btn btn-success btn-md pull-right"
                            ng-click="stage = STAGE_SEND">
                        Choose Recipients <i class="fa fa-arrow-right fa-fw"></i>
                    </button>
                </div>

                <div class="form-group" >
                    <label>Subject:</label>
                    <input type="text" class="form-control" ng-model="selectedMessage.subject">
                </div>


                <label>Body:</label>
                <div text-angular ng-model="selectedMessage.body">

                </div>
            </div>
            <div class="col-md-8" ng-show="!anyMessageSelected()">
                <h2 class="text-muted" style="width: 100%; display: block; text-align:center;">Select a message to edit.</h2>
            </div>
        </div>

        <div class="col-lg-12" ng-cloak ng-show="stage == STAGE_SEND">
            <button class="btn btn-primary btn-md "
                    ng-click="stage = STAGE_COMPOSE">
                <i class="fa fa-arrow-left fa-fw"></i> Back
            </button>

            <br>

            <div class="row">
                <div class="col-md-6">
                    <h3>Message</h3>
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <span ng-bind-html="selectedMessage.subject"></span>
                        </div>
                        <div class="panel-body">
                            <span ng-bind-html="selectedMessage.body"></span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <h3>Recipients</h3>
                    <div class="input-group">
                        <input type="text" class="form-control"  placeholder="Search" ng-model="recipientSearch" >
                        <span class="input-group-addon">
                            <i class="fa fa-search"></i>
                        </span>
                    </div>
                    <br>

                    <div class="clearfix">
                        <span>Selected: [[(recipients | filter: {selected: true}).length]] / [[recipients.length]]</span>
                        <div class="btn-group pull-right">
                            <button class="btn btn-default" ng-click="selectNoUsers()">
                                None
                            </button>
                            <button class="btn btn-default" ng-click="selectAllUsers()">
                                All
                            </button>
                            <button class="btn btn-default" ng-click="selectUsersMissingOrders()">
                                Missing Orders
                            </button>
                        </div>
                    </div>

                    <div class="list-group">
                        <div class="list-group-item cursor-pointer"
                             ng-class="{active: isRecipientSelected(recipient)}"
                             ng-click="toggleRecipient(recipient)"
                             dir-paginate="recipient in recipients | filter: recipientSearch | orderBy: 'last_name' | itemsPerPage:10">

                             <span>[[recipient.last_name]], [[recipient.first_name]]</span>
                        </div>
                    </div>

                    <dir-pagination-controls>

                    </dir-pagination-controls>
                </div>
            </div>
        </div>
    </div>

@stop




@section('scripts-head')
    <link rel='stylesheet' href='/javascripts/ng/text/textAngular.css'>

    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.4.5/angular.min.js"></script>

    <script src='/javascripts/ng/text/textAngular-rangy.min.js'></script>
    <script src='/javascripts/ng/text/textAngular-sanitize.min.js'></script>
    <script src='/javascripts/ng/text/textAngular.min.js'></script>

    <script src='/javascripts/ng/pagination/dirPagination.js'></script>

    <script src='/javascripts/ng/app.js'></script>
    <script src='/javascripts/ng/app.messages.js'></script>
@stop