@extends('layouts.master')


@section('content')

    @include('shared.partial.header', ['headerText'=>'Terms', 'subHeaderText'=> $term->termName() . ' ' . $term->year])


    <div class="row" ng-controller="TermsController">
        <div class="col-lg-12">

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-calendar fa-fw"></i>Term Settings</h3>
                </div>
                <div class="panel-body">

                    <div class="col-lg-4">
                        <dl class="dl-horizontal">
                            <dt>Term</dt>
                            <dd>{{ $term->termName() }}</dd>

                            <dt>Year</dt>
                            <dd>{{ $term->year }}</dd>

                            <dt>Order Start Date</dt>
                            <dd>{{ $term->order_start_date->toFormattedDateString() }}</dd>

                            <dt>Order Due Date</dt>
                            <dd>{{ $term->order_due_date->toFormattedDateString() }}</dd>

                        </dl>
                    </div>

                    <form action="/terms/details/{{$term->term_id}}" method="POST">
                        {!! csrf_field() !!}


                        <div class="col-lg-4">
                            <h3>Order Start Date</h3>
                            <input type="hidden" name="order_start_date" ng-value="order_start_date | date:'yyyy-MM-dd'">

                            <uib-datepicker ng-model="order_start_date"
                                            max-date="order_due_date"
                                            show-weeks="false"></uib-datepicker>
                        </div>

                        <div class="col-lg-4">
                            <h3>Order Due Date</h3>
                            <input type="hidden" name="order_due_date" ng-value="order_due_date  | date:'yyyy-MM-dd'">
                            <uib-datepicker ng-model="order_due_date"
                                            min-date="order_start_date"
                                            show-weeks="false"></uib-datepicker>
                        </div>

                        <button type="submit" class="btn btn-success pull-right">
                            Save <i class="fa fa-arrow-right"></i>
                        </button>
                    </form>

                </div>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-pencil fa-fw"></i> Term Courses</h3>
                </div>
                <div class="panel-body">
                    <?php  $courses = $term->courses()->paginate(10); ?>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-striped">
                            <thead>
                            <tr>
                                <th>Section</th>
                                <th>Name</th>
                            </tr>
                            </thead>
                            <tbody>

                            @foreach ($courses as $course)
                                <tr>
                                    <td>
                                        {{ $course->department }} {{ str_pad($course->course_number, 3, "0", STR_PAD_LEFT) }}-{{ $course->course_section }}
                                    </td>
                                    <td>{{ $course->course_name }}</td>

                                </tr>
                            @endforeach


                            </tbody>
                        </table>

                        <a href="/terms/check/{{$term->term_id}}" class="btn btn-primary">Check Sheet</a>

                        {{-- Render pagination controls --}}
                        {!! $courses->render() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>

@stop


@section('scripts-head')
    <script>
        order_start_date_init = new Date('{{$term->order_start_date->toFormattedDateString()}}');
        order_due_date_init = new Date('{{$term->order_due_date->toFormattedDateString()}}');
    </script>

    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.4.5/angular.min.js"></script>
    <script src="/javascripts/ui-bootstrap-tpls-0.14.3.min.js"></script>
    <script src="/javascripts/ng/app.js"></script>
    <script src="/javascripts/ng/app.terms.js"></script>
@stop
