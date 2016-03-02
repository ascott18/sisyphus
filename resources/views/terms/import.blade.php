@extends('layouts.master', [
    'breadcrumbs' => [
        ['Terms', '/terms'],
        [$term->display_name, '/terms/details/' . $term->term_id],
        ['Import Courses'],
    ]
])


@section('content')

    <div class="row" ng-controller="TermsImportController"
            ng-init="term_id = {{$term->term_id}}">
        <div class="col-lg-12" ng-show="!browserTooOld">

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-calendar fa-fw"></i> Import Data</h3>
                </div>
                <div class="panel-body">

                    <div class="form-group">
                        <label>Select Course CSV
                            <input type="file" file-model="file">
                        </label>
                        <p class="help-block">Example block-level help text here.</p>
                    </div>

                    <div class="col-lg-12 ">
                        <button class="btn btn-success" ng-click="submitForPreview()" >
                            Process and Review input <i class="fa arrow-right"></i>
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>

@stop


@section('scripts-head')
    <script src="/javascripts/ui-bootstrap-tpls-0.14.3.min.js"></script>
    <script src="/javascripts/ng/app.terms.js"></script>
@stop
