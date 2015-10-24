@extends('layouts.master')

@section('content')
    @include('shared.partial.header', ['headerText'=>'Messages', 'subHeaderText'=>'Create a message'])

    <div class="row" ng-app="sisyphus" ng-controller="MessagesController" xmlns="http://www.w3.org/1999/html">
        <div class="col-lg-12" ng-show="stage == STAGE_COMPOSE">

            <div class="col-md-4 ">
                <ul class="list-group">
                    <li class="list-group-item cursor-pointer list-group-item-success"
                        ng-click="newMessage()">

                        <h4 class="list-group-item-heading no-pad-bottom ">
                            <i class="fa fa-plus fa-align-middle text-success" style="font-size: 1.5em"></i>&nbsp;
                             Create New Message</h4>
                    </li>

                    <li class="list-group-item cursor-pointer"
                         ng-class="{active: isMessageSelected(message)}"
                         ng-click="selectMessage(message)"
                         ng-repeat="message in messages | orderBy : ((message.isNew?10e10:0) + message.lastSent):true">

                        <div class="pull-right">
                            <button class="btn btn-xs btn-danger">
                                <i class="fa fa-fw fa-trash"></i>
                            </button>
                            <button class="btn btn-xs btn-primary">
                                <i class="fa fa-fw fa-copy"></i>
                            </button>
                        </div>

                        <h4 class="list-group-item-heading no-pad-bottom">[[message.subject]]</h4>
                        <small >
                            <span class="text-muted" ng-show="message.lastSent">Sent On</span>
                            <span class="text-muted" ng-show="!message.lastSent">Never Sent</span>
                            <span ng-bind="message.lastSent | date:'MM/dd/yyyy'"></span>
                        </small>

                    </li>
                </ul>
            </div>

            <div class="col-md-8">
                <div class="form-group" style="display: table;">
                    <div style="display: table-cell; width: 100%">
                        <label>Subject:</label>
                        <input type="text" class="form-control" ng-model="selectedMessage.subject">
                    </div>

                    <div style="display: table-cell; vertical-align: bottom; padding-left: 15px;">
                        <button class="btn btn-success btn-md "
                                ng-click="stage = STAGE_SEND">
                            Choose Recipients <i class="fa fa-arrow-right fa-fw"></i>
                        </button>
                    </div>
                </div>


                <label>Body:</label>
                <div text-angular ng-model="selectedMessage.body">

                </div>
            </div>
        </div>


        <div class="col-lg-12" ng-cloak ng-show="stage == STAGE_SEND">
            <button class="btn btn-primary btn-md "
                    ng-click="stage = STAGE_COMPOSE">
                <i class="fa fa-arrow-left fa-fw"></i> Back
            </button>

            <br>
            <br>

            <div class="row">
                <div class="col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-body">
                            <span ng-bind-html="selectedMessage.body"></span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="list-group">
                        <div class="list-group-item cursor-pointer"
                             ng-class="{active: isRecipientSelected(recipient)}"
                             ng-click="toggleRecipient(recipient)"
                             ng-repeat="recipient in recipients | orderBy : recipient.name">

                             <span ng-bind="recipient.name"></span>

                        </div>
                    </div>
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

    <script src='/javascripts/ng/app.messages.js'></script>
@stop