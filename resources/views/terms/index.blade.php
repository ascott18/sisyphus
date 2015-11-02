@extends('layouts.master')


@section('content')

    @include('shared.partial.header', ['headerText'=>'Terms', 'subHeaderText'=>'All Terms'])


    <div class="row">
        <div class="col-lg-12">

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-pencil fa-fw"></i> Manage Terms</h3>
                </div>
                <div class="panel-body">

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-striped">
                            <thead>
                            <tr>
                                <th>Term</th>
                                <th>Status</th>
                                <th>Order Start Date</th>
                                <th>Order Due Date</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>

                            @foreach ($terms as $term)
                                <tr>
                                    <td> {{ $term->termName() }} {{ $term->year }} </td>

                                    <td> {{ $term->getStatusDisplayString() }}</td>

                                    <td>{{ $term->order_start_date->toFormattedDateString() }}</td>
                                    <td>{{ $term->order_due_date->toFormattedDateString() }}</td>
                                    <td style="width: 1%"> <a href="/terms/details/{{$term->term_id}}" class="btn btn-sm btn-primary">Details&nbsp; <i class="fa fa-arrow-right"></i></a> </td>

                                </tr>
                            @endforeach

                            </tbody>
                        </table>

                        {{-- Render pagination controls --}}
                        {!! $terms->render() !!}

                    </div>
                </div>
            </div>
        </div>
    </div>

@stop


@section('scripts-head')
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.4.5/angular.min.js"></script>
    <script src="/javascripts/ui-bootstrap-tpls-0.14.3.min.js"></script>
    <script src="/javascripts/ng/app.js"></script>
    <script src="/javascripts/ng/app.terms.js"></script>
@stop
