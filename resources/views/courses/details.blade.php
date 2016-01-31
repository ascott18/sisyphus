@extends('layouts.master', [
    'breadcrumbs' => [
        ['Courses', '/courses'],
        [$course->displayIdentifier()],
    ]
])


@section('content')

    <div class="row" ng-controller="CoursesDetailsController">
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

                    <dl class="col-lg-4 col-md-6 dl-horizontal">
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

                    <dl class="col-lg-4 col-md-6 dl-horizontal">
                        <dt>Term</dt>
                        <dd>
                            @can('view-terms')
                                <a href="/terms/details/{{ $course->term->term_id }}">{{ $course->term->displayName() }}</a>
                            @else
                                {{ $course->term->displayName() }}
                            @endcan
                        </dd>

                        <dt>Request Period</dt>
                        <dd>
                            {{ $course->term->status }}
                        </dd>

                        @if ($course->term->areOrdersInProgress())
                        <dt>Request Deadline</dt>
                        <dd>
                            {{ $course->term->order_due_date->toFormattedDateString() }}
                        </dd>
                        @endif
                    </dl>

                    <dl class="col-lg-4 col-md-6 dl-horizontal">
                        @if ($course->user != null)
                            <dt>Professor</dt>
                            <dd>
                                {{ $course->user->last_first_name }}
                            </dd>

                            <dt>Email</dt>
                            <dd>
                                {{ $course->user->email }}
                            </dd>
                        @else
                            <dt>Professor</dt>
                            <dd>
                                TBA
                            </dd>
                        @endif
                    </dl>
                </div>
            </div>


            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-shopping-cart fa-fw"></i> Requests</h3>
                </div>
                <div class="panel-body">
                    @can('place-order-for-course', $course)
                        <a href="/requests/create/{{$course->course_id}}" class="btn btn-primary" role="button">
                            Place a request <i class="fa fa-arrow-right"></i>
                        </a>
                        @if (!$course->no_book)
                            &nbsp;&nbsp;
                            <button type="button" class="btn btn-danger" role="button"
                                    ng-confirm-click="noBook({{$course->course_id}})"
                                    ng-confirm-click-message="Are you sure you don't want a book?
                                    [[{{count($course->orders)}} ? '\n\nAll requests on this course will be deleted!' : '']]"
                                    >
                                <i class="fa fa-times"></i> No Book Needed
                            </button>
                        @endif
                    @endcan

                    {{-- being no_book and having orders are no mutually exclusive. A course might be no_book and also have deleted orders, which are listed here.--}}
                    @if ($course->no_book)
                        <h3 class="text-muted">It was reported that this class does not need a book.</h3>
                    @elseif (!count($course->orders))
                        <h3 class="text-muted">There are no requests placed for this course.</h3>
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
                                <th>Notes</th>
                                @can('place-order-for-course', $course)
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
                                                <span class="text-danger">Deleted</span> by
                                                {{$order->deletedBy->first_name}} {{$order->deletedBy->last_name}} on {{$order->deleted_at->toDateString()}}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span ng-bind=":: '{{ $order->book->isbn13 }}' | isbnHyphenate">{{ $order->book->isbn13 }}</span>
                                    </td>
                                    <td>
                                        {{ $order->book->publisher }}
                                    </td>
                                    <td>
                                        {{ $order->required ? "Yes" : "No" }}
                                    </td>
                                    <td>
                                        {{ $order->notes }}
                                    </td>

                                    @can('place-order-for-course', $course)
                                    <td>
                                        @if ($order->deleted_at == null)
                                            <form action="/requests/delete/{{$order->order_id}}" method="POST" name="form">
                                                {!! csrf_field() !!}
                                                <button type="button" class="btn btn-sm btn-danger" role="button" ng-confirm-click="submit" >
                                                    <i class="fa fa-trash-o"></i> Delete Request
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