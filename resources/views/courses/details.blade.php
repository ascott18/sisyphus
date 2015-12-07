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

                    <dl class="col-md-4 dl-horizontal">
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
                    </dl>

                    <dl class="col-md-4 dl-horizontal">
                        <dt>Term</dt>
                        <dd>
                            {{ $course->term->termName() }} {{ $course->term->year }}
                        </dd>

                        <dt>Order Period</dt>
                        <dd>
                            {{ $course->term->getStatusDisplayString() }}
                        </dd>

                        @if ($course->term->areOrdersInProgress())
                        <dt>Order Deadline</dt>
                        <dd>
                            {{ $course->term->order_due_date->toFormattedDateString() }}
                        </dd>
                        @endif
                    </dl>

                    <dl class="col-md-4 dl-horizontal">
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
                    @if ($course->canPlaceOrder())
                        <a href="/orders?user_id={{$course->user->user_id}}" class="btn btn-primary" role="button">
                            Place an order <i class="fa fa-arrow-right"></i>
                        </a>
                        <br>
                        <br>
                    @endif

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
                                <th width="140px"></th>
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

                                    <td><a class="btn btn-sm btn-info" href="/books/show/{{ $order->book->book_id }}" role="button">
                                            Book Details <i class="fa fa-arrow-right"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach

                            </tbody>
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