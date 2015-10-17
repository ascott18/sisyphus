
@extends('layouts.master')


@section('content')
    <div class="row open-sans error-page">
        <div class="col-lg-12">
            <h1 class="page-header text-muted">i'm broken :-(</h1>
            <h2>{{$response['status']}}</h2>
            <h3>(that means <strong>{{strtolower($response['statusName'])}}</strong>)</h3>
            <br/>
            <p>was it you? i bet it was you!</p>
            <p>how could there be an error with me? i'm flawless!</p>
            <p></p>
            <br/>
            @if ($response['message'])
                <hr>
                <br/>
                <p>{{$response['message']}}</p>
            @endif
        </div>
    </div>
@stop
