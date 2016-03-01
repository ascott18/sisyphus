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
                    <h3 class="panel-title"><i class="fa fa-calendar fa-fw"></i> Review Courses to Import</h3>
                </div>
                <div class="panel-body">

                    WE FOUND 23423 COURESE THAT WERE IDENTICAL AND WILL NOT BE CHANGED.

                    WE FOUND 23423 COURSES THAT WILL BE CREATED.

                    WE FOUND 234 COURSES THAT WILL BE UPDATED.

                    WE FOUND 23 COURSES THAT WILL BE DELETED.

                    WE FOUND 12 COURSES THAT CANNOT BE DELETED BECAUSE THEY HAVE SUBMITTED REQUESTS. WE MARKED THEM AS NO BOOK.

                    <form action="/terms/import/{{$term->term_id}}" method="POST">
                        {!! csrf_field() !!}

                        <div class="col-lg-12 ">
                            <button type="submit" class="btn btn-success pull-right" style="margin-top: 15px;">
                                Import these courses <i class="fa arrow-right"></i>
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
