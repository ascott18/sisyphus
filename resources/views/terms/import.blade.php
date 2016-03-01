@extends('layouts.master', [
    'breadcrumbs' => [
        ['Terms', '/terms'],
        [$term->display_name, '/terms/details/' . $term->term_id],
        ['Import Courses'],
    ]
])


@section('content')

    <div class="row" ng-controller="TermsController">
        <div class="col-lg-12">

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-calendar fa-fw"></i> Import Data</h3>
                </div>
                <div class="panel-body">

                    <form action="/terms/import/{{$term->term_id}}" method="POST" enctype="multipart/form-data">
                        {!! csrf_field() !!}

                        <div class="form-group">
                            <label>Select Course CSV
                                <input type="file" name="file">
                            </label>
                            <p class="help-block">Example block-level help text here.</p>
                        </div>

                        <div class="col-lg-12 ">
                            <button type="submit" class="btn btn-success pull-right" style="margin-top: 15px;">
                                Process and Review input <i class="fa arrow-right"></i>
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

@stop


@section('scripts-head')
    <script src="/javascripts/ng/app.terms.js"></script>
@stop
