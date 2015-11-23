@extends('layouts.master')

@section('area', 'Courses')
@section('page', $course->displayIdentifier())

@section('content')

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-pencil fa-fw"></i> Course Details</h3>
                </div>
                <div class="panel-body">

                    <dl class="col-md-6 dl-horizontal">
                        <dt>Title</dt>
                        <dd>
                            {{ $course->course_name }}
                        </dd>

                        <dt>Department</dt>
                        <dd>
                            {{ $course->department }}
                        </dd>

                        <dt>Number</dt>
                        <dd>
                            {{ $course->course_number }}
                        </dd>

                        <dt>Section</dt>
                        <dd>
                            {{ $course->course_section }}
                        </dd>

                        <dt>Term</dt>
                        <dd>
                            {{ $course->term->termName() }} {{ $course->term->year }}
                        </dd>
                    </dl>

                    <dl class="col-md-6 dl-horizontal">
                        <dt>Professor</dt>
                        <dd>
                            {{ $course->user->last_name }}, {{ $course->user->first_name }}
                        </dd>

                        <dt>Email</dt>
                        <dd>
                            {{ $course->user->email }}
                        </dd>
                    </dl>
                </div>
            </div>


            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-shopping-cart fa-fw"></i> Orders</h3>
                </div>
                <div class="panel-body">
                    @if ($course->no_book)
                        <h3 class="text-muted">It was reported that this class does not need a book.</h3>
                    @elseif (!count($course->orders))
                        <h3 class="text-muted">There are no orders placed for this course.</h3>
                    @else
                        <table class="table table-bordered table-hover table-striped">
                            <thead>
                            <tr>
                                <th>Title</th>
                                <th>ISBN</th>
                                <th>Publisher</th>
                            </tr>
                            </thead>
                            <tbody>

                            @foreach($course->orders as $order)
                                <tr>
                                    <td>
                                        {{ $order->book->title }}
                                    </td>
                                    <td>
                                        {{ $order->book->isbn13 }}
                                    </td>
                                    <td>
                                        {{ $order->book->publisher }}
                                    </td>
                                </tr>
                            @endforeach

                            </tbody>
                            <tfoot>
                            <tr>
                                <td class="text-center" st-pagination="" st-items-by-page="10" colspan="4">
                                </td>
                            </tr>
                            </tfoot>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>

@stop

@section('scripts-head')

    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.4.5/angular.min.js"></script>
    <script src="/javascripts/ng/smart-table/smart-table.min.js"></script>
    <script src="/javascripts/ng/app.js"></script>
    <script src="/javascripts/ng/app.books.js"></script>
@stop