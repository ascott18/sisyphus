@extends('layouts.master')

@section('area', 'Tickets')
@section('page', $ticket->title)

@section('content')

    <div class="row">
        <div class="col-lg-7">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-life-ring fa-fw"></i>
                        {{ $ticket->title }}
                    </h3>
                </div>
                <div class="panel-body">

                    {{ $ticket->body }}

                </div>
            </div>
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
                        <dt>Department</dt>
                        <dd>
                            {{ $ticket->department == null ? "None" : $ticket->department }}
                        </dd>

                        <dt>Linked To</dt>
                        <dd>
                            <a href="{{ $ticket->url }}">{{ $ticket->url }}</a>
                        </dd>


                        <dt>Created By</dt>
                        <dd>
                            {{ $ticket->user->last_first_name }}
                        </dd>

                        <dt>Created On</dt>
                        <dd>
                            {{ $ticket->created_at }}
                        </dd>

                        <dt>Email</dt>
                        <dd>
                            {{ $ticket->user->email }}
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