@extends('layouts.master', [
    'breadcrumbs' => [
        ['Dashboard'],
    ]
])


@section('content')

{{--<div class="row">--}}
    {{--<div class="col-lg-3 col-xs-6">--}}
        {{--<div class="panel panel-primary">--}}
            {{--<div class="panel-heading">--}}
                {{--<div class="row">--}}
                    {{--<div class="col-xs-3"><i class="fa fa-calendar fa-5x"></i></div>--}}
                    {{--<div class="col-xs-9 text-right">--}}
                        {{--<div class="huge">{{$openTermsCount}}</div>--}}
                        {{--<div>Open Terms!</div>--}}
                    {{--</div>--}}
                {{--</div>--}}
            {{--</div>--}}
            {{--<a href="#">--}}
                {{--<div class="panel-footer">--}}
                    {{--<span class="pull-left">View Details</span><span class="pull-right"><i--}}
                                {{--class="fa fa-arrow-circle-right"></i></span>--}}

                    {{--<div class="clearfix"></div>--}}
                {{--</div>--}}
            {{--</a>--}}
        {{--</div>--}}
    {{--</div>--}}


    {{--@can('edit-books')--}}
    {{--<div class="col-lg-3 col-xs-6">--}}
        {{--<div class="panel panel-green">--}}
            {{--<div class="panel-heading">--}}
                {{--<div class="row">--}}
                    {{--<div class="col-xs-3"><i class="fa fa-book fa-5x"></i></div>--}}
                    {{--<div class="col-xs-9 text-right">--}}
                        {{--<div class="huge">{{$newBookCount}}</div>--}}
                        {{--<div>New Books (30 days)</div>--}}
                    {{--</div>--}}
                {{--</div>--}}
            {{--</div>--}}
            {{--<a href="#">--}}
                {{--<div class="panel-footer">--}}
                    {{--<span class="pull-left">View Details</span><span class="pull-right"><i--}}
                                {{--class="fa fa-arrow-circle-right"></i></span>--}}

                    {{--<div class="clearfix"></div>--}}
                {{--</div>--}}
            {{--</a>--}}
        {{--</div>--}}
    {{--</div>--}}
    {{--@endcan--}}

    {{--<div class="col-lg-3 col-xs-6">--}}
        {{--<div class="panel panel-red">--}}
            {{--<div class="panel-heading">--}}
                {{--<div class="row">--}}
                    {{--<div class="col-xs-3"><i class="fa fa-support fa-5x"></i></div>--}}
                    {{--<div class="col-xs-9 text-right">--}}
                        {{--<div class="huge">13</div>--}}
                        {{--<div>Support Tickets!</div>--}}
                    {{--</div>--}}
                {{--</div>--}}
            {{--</div>--}}
            {{--<a href="#">--}}
                {{--<div class="panel-footer">--}}
                    {{--<span class="pull-left">View Details</span><span class="pull-right"><i--}}
                                {{--class="fa fa-arrow-circle-right"></i></span>--}}

                    {{--<div class="clearfix"></div>--}}
                {{--</div>--}}
            {{--</a>--}}
        {{--</div>--}}
    {{--</div>--}}
{{--</div>--}}
<!-- /.row--><!-- Morris Charts CSS-->

    <!--[if IE 9]>
        <div ng-init="appErrors = [{title: 'You are using an outdated browser!',
        messages: ['This page is not well-optimized for Internet Explorer 9. It is strongly recommended that you switch to a modern browser like Chrome or Firefox.']}]"></div>
    <![endif]-->

<div class="row">
    @foreach($chartData as $term)
    <div class="col-lg-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="fa fa-bar-chart-o fa-fw"></i> {{$term['name']}}
                </h3>
                <small>{{$term['status']}} - {{$term['current_count']}} of {{$term['course_count']}} courses responded</small>
            </div>
            <div class="panel-body">
                <div id="order-chart-{{$term['term_id']}}"></div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="row">
    @if(count($responseStats))
    <div class="col-lg-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="fa fa-line-chart fa-fw"></i> Response Rates
                </h3>
            </div>
            <div class="panel-body">
                <div id="response-chart"></div>
            </div>
        </div>
    </div>
    @endif

    <div class="col-lg-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="fa fa-line-chart fa-fw"></i> Course Activity
                </h3>
            </div>
            <div class="panel-body">
                <div id="activity-chart"></div>
            </div>
        </div>

    </div>
</div>

<link href="/stylesheets/plugins/morris.css" rel="stylesheet">

<small class="text-muted">Charts may update every {{$cacheMins}} minutes.</small>

@stop


@section('scripts')

    <!-- Morris Charts JavaScript-->
    <script src="javascripts/plugins/morris/raphael.min.js"></script>
    <script src="javascripts/plugins/morris/morris.min.js"></script>
    {{--<script src="javascripts/plugins/morris/morris-data.js"></script>--}}

    <script>

    var app = angular.module('sisyphus', ['sisyphus.helpers']);

    $(function() {
        @foreach($chartData as $term)
            var data = {!! json_encode($term['data']) !!}

            // Area Chart
            Morris.Area({
                element: 'order-chart-{{$term['term_id']}}',
                data: data,
                xkey: 'date',
                ykeys: ['orders', 'nobook'],
                labels: ['Courses Ordered', 'Courses with No Book'],
                ymax: 'auto {{$term['course_count'] + (4 - $term['course_count']%4)}}',
                goals: [{{$term['course_count']}}],
                yLabelFormat: function(y){return y != Math.round(y)?'':y;}, // Hide decimal labels
                pointSize: 0,
                hideHover: 'auto',
                continuousLine: true,
                smooth: false,
                lineWidth: 0,
                resize: true
            });
        @endforeach

        @if(count($responseStats))
            var data = {!! json_encode($responseStats) !!}
            Morris.Bar({
                element: 'response-chart',
                data: data,
                xkey: 'name',
                parseTime:false,
                ykeys: ['percent'],
                labels: ['Responses Received'],
                ymax: 100,
                hideHover: 'auto',
                postUnits: '%',
                resize: true,
                hoverCallback: function (index, options, content, row) {
                    return content + "<br>" +
                        row.total + " Courses<br>" +
                        row.responded + " Responses<br>";
                }
            });
        @endif

        var data = {!! json_encode($activityStats) !!}
        Morris.Line({
            element: 'activity-chart',
            data: data,
            xkey: 'date',
            ykeys: ['count'],
            labels: ['Courses with Activity'],
            yLabelFormat: function(y){return y != Math.round(y)?'':y;}, // Hide decimal labels
            hideHover: 'auto',
            resize: true,
            dateFormat: function (x) { return moment(x).format('dddd, MMMM Do') },
            xLabelFormat: function (x) { return moment(x).format('ddd MMMM Do') }
        });
    });

    </script>

@stop
