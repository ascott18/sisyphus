@extends('layouts.master')

@section('area', 'Courses')
@section('page', $course->displayIdentifier())

@section('content')

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-university fa-fw"></i> Course Details</h3>
                </div>
                <div class="panel-body">

                    @can('edit-course', $course)
                    <div class="col-md-12">
                        <a class="btn btn-primary" href="/courses/edit/{{ $course->course_id }}" role="button">
                            <i class="fa fa-pencil"></i> Edit
                        </a>
                    </div>
                    @endcan

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
                            <a href="/terms/details/{{ $course->term->term_id }}">{{ $course->term->displayName() }}</a>
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
                        <a href="/orders/create/{{$course->course_id}}" class="btn btn-primary" role="button">
                            Place an order <i class="fa fa-arrow-right"></i>
                        </a>
                    @endif

                    {{-- being no_book and having orders are no mutually exclusive. A course might be no_book and also have deleted orders, which are listed here.--}}
                    @if ($course->no_book)
                        <h3 class="text-muted">It was reported that this class does not need a book.</h3>
                    @elseif (!count($course->orders))
                        <h3 class="text-muted">There are no orders placed for this course.</h3>
                    @endif
                    @if (count($course->orders))
                        <br>
                        <br>
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th>Title</th>
                                <th>ISBN</th>
                                <th>Publisher</th>
                                <th>Required</th>
                                @can('edit-order', $course->orders->first())
                                    <th width="1%"></th>
                                @endcan
                                <th width="1%"></th>
                            </tr>
                            </thead>
                            <tbody>

                            @foreach($course->orders as $order)
                                <tr class=" {{ $order->deleted_at != null ? "danger" : "" }}">
                                    <td>
                                        {{ $order->book->title }}
                                        <br><span class="text-muted">
                                            Placed by {{$order->placedBy->first_name}} {{$order->placedBy->last_name}} on {{$order->created_at->toDateString()}}
                                        </span>

                                        @if ($order->deleted_at != null)
                                            <br><span class="text-muted">
                                                <span class="text-danger">Deleted</span> by {{$order->deletedBy->first_name}} {{$order->deletedBy->last_name}} on {{$order->deleted_at->toDateString()}}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        [["{{ $order->book->isbn13 }}" | isbnHyphenate ]]
                                    </td>
                                    <td>
                                        {{ $order->book->publisher }}
                                    </td>
                                    <td>
                                        {{ $order->required ? "Yes" : "No" }}
                                    </td>

                                    @can('edit-order', $order)
                                    <td>
                                        @if ($order->deleted_at == null)
                                            <form action="/orders/delete/{{$order->order_id}}" method="POST" name="form">
                                                {!! csrf_field() !!}
                                                <button type="button" class="btn btn-sm btn-danger" role="button" ng-confirm-click="submit" >
                                                    <i class="fa fa-times"></i> Delete Order
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                    @endcan

                                    <td>
                                        <a class="btn btn-sm btn-primary" href="/books/details/{{ $order->book->book_id }}" role="button">
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
    <script src="/javascripts/ng/helper.isbnHyphenate.js"></script>
    <script src="/javascripts/ng/pagination/dirPagination.js"></script>
    <script src="/javascripts/ng/app.courses.js"></script>
@stop