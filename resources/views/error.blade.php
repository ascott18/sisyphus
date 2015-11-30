@extends('layouts.master')

@section('page', $response['statusName'])

@section('content')
    <style>

        .error-page {
            text-align: center;
            vertical-align: middle
        }
        .error-page h2 {
            font-size: 9em
        }


        .broken {
            -webkit-transform: rotate(-11deg);
            -moz-transform:    rotate(-11deg);
            -ms-transform:     rotate(-11deg);
            -o-transform:      rotate(-11deg);
            transform:         rotate(-11deg);
            white-space: nowrap;
            position: absolute;
            left: 10%;
            top: 20px;
        }
        .broken h1 {
            font-size: 4em;
        }
        .how-dare-you-break-me {
            -webkit-transform: rotate(9deg);
            -moz-transform:    rotate(9deg);
            -ms-transform:     rotate(9deg);
            -o-transform:      rotate(9deg);
            transform:         rotate(9deg);
            white-space: nowrap;
            margin-left: 40%;
            margin-top: 40px;
            margin-bottom: 40px;
        }

        .teapot {
            margin-top: -15px;
            font-size: 0.7em;
            color: #c1c1c1;
        }

        .status {
            padding-top: 90px;
        }
    </style>
    <div class="row open-sans error-page">
        <div class="col-lg-12">
            <div class="text-muted broken">
                <h1>i'm broken :-(</h1>
                <p class="teapot text-muted">418? no, definitely not... wait, i know it!</p>
            </div>
            <h2 class="status" >{{$response['status']}}</h2>
            <h3>(that means <strong>{{strtolower($response['statusName'])}}</strong>)</h3>
            <br/>
            @if ($response['message'])
                <p class="text-muted">i guess what i'm really trying to say here is this:</p>
                <h3 style="font-size: 2.7em;">{!! trim(nl2br(e($response['message'])), '.') !!}</h3>
            @endif
            <br/>
            <div class="how-dare-you-break-me">
                <p class="text-muted">was it you? i bet it was you!</p>
                <p class="text-muted">how could there be an error with me? i'm flawless!</p>
            </div>
            <br/>
        </div>
    </div>
@stop
