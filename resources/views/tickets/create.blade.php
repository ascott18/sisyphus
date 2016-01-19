@extends('layouts.master')

@section('area', 'Tickets')
@section('page', 'Create Ticket')

@section('content')


    <div class="row col-lg-12" ng-controller="TicketController">



        <div class="col-md-8">
            <h3>New Ticket</h3>

            <form name="form">
                <div class="form-group" >
                    <label>Subject:</label>
                    <input type="text" class="form-control" ng-model="ticket.subject">
                </div>



                <label>Body:</label>
                </br>

                <div class="form-group" text-angular ng-model="ticket.body">
                </div>

                <br>

                <a type="button" class="btn btn-success pull-right"
                        ng-click="submitTicket()"
                        href="/tickets/index">
                    <i class="fa fa-plus"></i> Submit Ticket
                </a>
            </form>


        </div>

        <div class="col-md-4">
            </p>
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