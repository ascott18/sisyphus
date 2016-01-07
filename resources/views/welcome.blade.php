@extends('layouts.master')

@section('area', 'Dashboard')
@section('page', 'Overview')


@section('content')

<div class="row">
    <div class="col-lg-3 col-xs-6">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3"><i class="fa fa-calendar fa-5x"></i></div>
                    <div class="col-xs-9 text-right">
                        <div class="huge">{{$openTermsCount}}</div>
                        <div>Open Terms!</div>
                    </div>
                </div>
            </div>
            <a href="#">
                <div class="panel-footer">
                    <span class="pull-left">View Details</span><span class="pull-right"><i
                                class="fa fa-arrow-circle-right"></i></span>

                    <div class="clearfix"></div>
                </div>
            </a>
        </div>
    </div>


    <div class="col-lg-3 col-xs-6">
        <div class="panel panel-green">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3"><i class="fa fa-shopping-cart fa-5x"></i></div>
                    <div class="col-xs-9 text-right">
                        <div class="huge">124</div>
                        <div>Completed Orders!</div>
                    </div>
                </div>
            </div>
            <a href="#">
                <div class="panel-footer">
                    <span class="pull-left">View Details</span><span class="pull-right"><i
                                class="fa fa-arrow-circle-right"></i></span>

                    <div class="clearfix"></div>
                </div>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-xs-6">
        <div class="panel panel-red">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3"><i class="fa fa-support fa-5x"></i></div>
                    <div class="col-xs-9 text-right">
                        <div class="huge">13</div>
                        <div>Support Tickets!</div>
                    </div>
                </div>
            </div>
            <a href="#">
                <div class="panel-footer">
                    <span class="pull-left">View Details</span><span class="pull-right"><i
                                class="fa fa-arrow-circle-right"></i></span>

                    <div class="clearfix"></div>
                </div>
            </a>
        </div>
    </div>
</div>
<!-- /.row--><!-- Morris Charts CSS-->

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
    <div class="col-lg-12">
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

        var data = {!! json_encode($responseStats) !!}
        Morris.Line({
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
        })
    });

    </script>

@stop
