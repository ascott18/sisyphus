@extends('layouts.master', [
    'breadcrumbs' => [
        ['Tickets', '/tickets'],
        ['Create'],
    ]
])


@section('content')

    <div class="row col-lg-12" ng-cloak="" ng-controller="NewTicketController">
        <div class="row col-lg-12" ng-init="setTicket({{$ticket}})">
            <div class="col-md-8">
                <h3>New Ticket</h3>

                <form name="form">
                    <div class="form-group" >
                        <label>Subject:</label>
                        <input type="text" class="form-control" ng-model="ticket.title">
                    </div>

                    <label>Body:</label>
                    </br>

                    <div class="form-group" text-angular ng-model="ticket.body">
                    </div>

                    <br>

                    <a type="button" class="btn btn-success pull-right"
                       ng-click="submitTicket()">
                        <i class="fa fa-plus"></i> Submit Ticket
                    </a>
                </form>


            </div>

            <div class="col-md-4">
                </p>
            </div>



        </div>
        </div>

    </div>

@stop

@section('scripts-head')
    <link rel='stylesheet' href='/javascripts/ng/text/textAngular.css'>

    <script src='/javascripts/ng/text/textAngular-rangy.min.js'></script>
    <script src='/javascripts/ng/text/textAngular-sanitize.min.js'></script>
    <script src='/javascripts/ng/text/textAngular.min.js'></script>

    <script src='/javascripts/ng/pagination/dirPagination.js'></script>

    <script src="/javascripts/ng/app.tickets.js"></script>
@stop