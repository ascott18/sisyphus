@extends('layouts.master', [
    'breadcrumbs' => [
        ['Messages', '/messages'],
        ['Send Messages'],
    ]
])


@section('content')

    <div class="row" ng-controller="MessagesController" xmlns="http://www.w3.org/1999/html">
        <div ng-show="stage == STAGE_COMPOSE">

            <div class="col-md-4 ">
                <div class="panel panel-default">

                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <i class="fa fa-fw fa-envelope"></i>
                            Messages
                        </h3>
                    </div>
                    <div class="panel-body panel-list">
                            <div class="panel-list-item cursor-pointer "
                                ng-click="newMessage()">

                                <h4 class="list-group-item-heading no-pad-bottom ">
                                    <i class="fa fa-fw fa-plus fa-align-middle text-success" style="font-size: 1.5em"></i>
                                     Create New Message</h4>
                            </div>

                            <div class="panel-list-item cursor-pointer"
                                 ng-cloak
                                 ng-controller="MessageSaver"
                                 ng-class="{active: isMessageSelected(message)}"
                                 ng-click="selectMessage(message)"
                                 ng-repeat="message in messages | orderBy : ['isNew', 'last_sent == null', '-last_sent']">
                                <div class="pull-right">
                                    <button class="btn btn-xs btn-danger"
                                            title="Delete Message"
                                            ng-confirm-click="deleteMessage(message)"
                                            ng-confirm-click-message="Are you sure you want to delete message [[message.subject]]?">
                                        <i class="fa fa-fw fa-trash"></i>
                                    </button>
                                    <button class="btn btn-xs btn-primary"
                                            title="Duplicate Message"
                                            ng-click="newMessage(message)">
                                        <i class="fa fa-fw fa-copy"></i>
                                    </button>
                                </div>

                                <h4 class="list-group-item-heading no-pad-bottom">[[message.subject]]</h4>
                                <small >
                                    <span ng-show="moment(message['last_sent']).unix()">
                                        <span class="text-muted">Sent On</span>
                                        <span ng-bind="message.last_sent | date:'MMMM dd, yyyy'"></span>
                                    </span>
                                    <span class="text-muted" ng-show="!moment(message['last_sent']).unix()">Never Sent</span>
                                </small>

                            </div>
                    </div>
                </div>
            </div>

            <!--  There is a reason why we do an ng-repeat here, I promise.
              --  1) Angular will create an empty object for our selected message before the user selects anything,
              --      causing issues with undo and redo and requiring extra logic to see if there is a REAL message selected.
              --  2) Even without the creation of that empty message, undo/redo still don't work properly because
              --      all messages will have a shared undo/redo state due to them using the same DOM element as their editor.
              --  So, to get around this, we do an ng-repeat on all messages so that every message will get its own editor,
              --      but we use an ng-if to prevent all but the selected message from ever creating a DOM element.
              --      The result is that angular will automatically dispose/create an editor for us each time the
              --      selected message changes. Thanks for being a cool guy, angular!  -->
            <div class="col-md-8"
                 ng-cloak
                 ng-if="isMessageSelected(message)"
                 ng-repeat="message in messages">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <i class="fa fa-fw fa-pencil"></i>
                            Compose
                        </h3>
                    </div>
                    <div class="panel-body">
                        <div class="clearfix">
                            <button class="btn btn-success btn-md pull-right"
                                    ng-click="setStage(STAGE_SEND)">
                                Choose Recipients <i class="fa fa-arrow-right fa-fw"></i>
                            </button>
                        </div>

                        <div class="form-group" >
                            <label>Subject:</label>
                            <input type="text" class="form-control" ng-model="message.subject">
                        </div>


                        <label>Body:</label>
                        <div text-angular ng-model="message.body">

                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8" ng-show="!anyMessageSelected()">
                <h2 class="text-muted" style="width: 100%; display: block; text-align:center;">Select a message to edit.</h2>
            </div>
        </div>

        <div class="col-lg-12" ng-cloak ng-show="stage == STAGE_SEND">
            <button class="btn btn-primary btn-md "
                    ng-click="setStage(STAGE_COMPOSE)">
                <i class="fa fa-arrow-left fa-fw"></i> Back
            </button>

            <br>
            <br>

            <div class="row">
                <div class="col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title" ng-bind-html="selectedMessage.subject"></h3>
                        </div>
                        <div class="panel-body">
                            <span ng-bind-html="selectedMessage.body"></span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title">
                                <i class="fa fa-fw fa-group"></i>
                                Recipients
                            </h3>
                        </div>
                        <div class="panel-body">
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
                                        Missing Responses
                                    </button>
                                </div>
                            </div>

                            <div>
                                <h4 ng-if="recipients.length == 0" class="text-muted">
                                    Nobody was found that teaches during these open terms:
                                    <ul>
                                        <li ng-repeat="term in terms">[[term.display_name]]</li>
                                    </ul>
                                </h4>
                            </div>
                            <div class="list-group">
                                <div class="list-group-item cursor-pointer"
                                     ng-class="{active: isRecipientSelected(recipient)}"
                                     ng-click="toggleRecipient(recipient)"
                                     dir-paginate="recipient in recipients | filterSplit: recipientSearch | orderBy: 'last_name' | itemsPerPage:10">

                                    <span>[[recipient.last_name]], [[recipient.first_name]]</span>
                                    <span class="pull-right">[[recipient.coursesResponded]] / [[recipient.courseCount]] courses responded</span>
                                </div>
                            </div>
                            <dir-pagination-controls></dir-pagination-controls>

                            <button class="btn btn-success btn-md pull-right"
                                    ng-click="sendMessages()"
                                    ng-disabled="(recipients | filter: {selected: true}).length == 0"
                                    ng-show="!sendingMessages">
                                Send
                                <ng-pluralize count="(recipients | filter: {selected: true}).length"
                                              when="{'one': '{} Message',
                                                    'other': '{} Messages'}">
                                </ng-pluralize>
                                <i class="fa fa-arrow-right fa-fw"></i>
                            </button>

                            <button class="btn btn-success btn-md pull-right"
                                    ng-show="sendingMessages">
                                Sending
                                <ng-pluralize count="numSendingMessages"
                                              when="{'one': '{} Message',
                                             'other': '{} Messages'}">
                                </ng-pluralize>
                                <i class="fa fa-spinner fa-spin fa-fw"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-12" ng-cloak ng-show="stage == STAGE_SENT">
            <button class="btn btn-primary btn-md "
                    ng-click="setStage(STAGE_COMPOSE)">
                <i class="fa fa-arrow-left fa-fw"></i> Send Another
            </button>

            <h2 class="text-center" style="width: 100%; display: block;">Message sent  to [[messagesSent]] of
                <ng-pluralize count="messagesRequested"
                              when="{'one': '{} user',
                                     'other': '{} users'}">
                </ng-pluralize>!
            </h2>
            <h4 class="text-center">
                Some of the users might not have known email addresses.
            </h4>

        </div>
    </div>

@stop




@section('scripts-head')
    <link rel='stylesheet' href='/javascripts/ng/text/textAngular.css'>

    <script src='/javascripts/ng/text/textAngular-rangy.min.js'></script>
    <script src='/javascripts/ng/text/textAngular-sanitize.min.js'></script>
    <script src='/javascripts/ng/text/textAngular.min.js'></script>

    <script src='/javascripts/ng/pagination/dirPagination.js'></script>

    <script src='/javascripts/ng/app.messages.js'></script>
@stop