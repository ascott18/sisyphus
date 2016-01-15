@extends('layouts.master')

@section('area', 'Orders')
@section('page', 'All Requests')

@section('content')

    {{--<script>requested_user_id = {{$targetUser->user_id}}</script>--}}

<div>
    <div ng-cloak class="row" ng-controller="OrdersController"
            ng-init="
                courses = {{$courses}};
                @if (isset($course))
                    placeRequestForCourse((courses | filter:{'course_id': {{$course->course_id}} })[0]);
                @endif
            ">

        <div class="col-lg-12" ng-if="selectedCourse">
            <h3 class="text-muted" style="margin-top: 0px; margin-bottom: 20px;">
                [[selectedCourse.department]] [[selectedCourse.course_number | zpad:3]]-[[selectedCourse.course_section | zpad:2]] [[selectedCourse.course_name]]
            </h3>
        </div>

        <div class="col-lg-12" ng-show="getStage() == STAGE_SELECT_COURSE">

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-university fa-fw"></i>
                        My Courses
                    </h3>
                </div>
                <div class="panel-body">
                    <h3 ng-hide="courses.length" class="text-muted">
                        @if (count($openTerms) == 0)
                            No terms are open for ordering.
                        @else
                            No courses found for the terms currently open for ordering:
                        <ul>
                            @foreach($openTerms as $term)
                                <li>{{$term->termName()}} {{ $term->year }}</li>
                            @endforeach
                        </ul>
                        @endif
                    </h3>
                    <table ng-show="courses.length" class="list-group" style="width: 100%">
                        <thead>
                            <th style="width: 100%"></th>
                            <th ></th>
                        </thead>

                        <tbody>
                            <tr class="list-group-item" ng-repeat="course in courses">
                                <td style="width: 100%">
                                    <h4 class="list-group-item-heading">[[course.department]] [[course.course_number | zpad:3]]-[[course.course_section | zpad:2]] [[course.course_name]]</h4>

                                    <p style="margin-left: 3em">
                                        <div ng-show="courseNeedsOrders(course)">
                                            </br>
                                            <span class="well well-sm" style="background-color: #fcf8e3; border-color: #fbeed5">
                                                No requests submitted - please let us know what you need!
                                            </span>
                                        </div>
                                        <div ng-show="course.no_book">
                                            </br>
                                            You selected that you didn't need a book
                                        </div>

                                        <span ng-repeat="order in course.orders">
                                            <span class="text-muted">[[order.book.isbn13 | isbnHyphenate]]</span>: [[order.book.title]]
                                            </br>
                                        </span>
                                    </p>
                                </td>

                                <td style="vertical-align: top">
                                    <a ng-click="placeRequestForCourse(course)"
                                       class="btn btn-primary"
                                       style="width:100%; margin-bottom:5px">
                                        Place a request <i class="fa fa-arrow-right fa-fw"></i>
                                    </a>
                                    <span ng-if="!course.no_book">
                                        <br>
                                        <button
                                                ng-confirm-click="noBook(course)"
                                                ng-confirm-click-message="Are you sure you don't want a book? [[course.orders.length ? '\n\nAll orders on this course will be deleted!' : '']]"
                                                class="btn btn-danger"
                                                style="width:100%">
                                            <i class="fa fa-times"></i> No book needed</button>
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


        <div ng-show="getStage() == STAGE_SELECT_BOOKS" ng-controller="NewBookController">

            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-book fa-fw"></i> Select Books</h3>
                    </div>
                    <div class="panel-body">

                        <!-- Nav tabs -->
                        <ul class="nav nav-tabs" role="tablist">
                            <li role="presentation" class="active"><a href="#newbook" aria-controls="newbook" role="tab" data-toggle="tab">
                                    <i class="fa fa-star"></i> Enter a New Book
                                </a></li>
                            <li role="presentation"><a href="#pastbooks" aria-controls="pastbooks" role="tab" data-toggle="tab">
                                    <i class="fa fa-history"></i> Select a Past Book
                                </a></li>
                        </ul>

                        <!-- Tab panes -->
                        <div class="tab-content">
                            <div role="tabpanel" class="tab-pane active" id="newbook">

                                </br>


                                <div ng-controller="NewBookController">
                                    <book-editor></book-editor>
                                </div>
                            </div>

                            <div role="tabpanel" class="tab-pane" id="pastbooks">

                                </br>

                                <h3 class="text-muted"
                                        ng-show="selectedCourse.pastBooks.length == 0">
                                    There are no known past books for this course.
                                </h3>

                                <ul class="list-group"
                                        ng-show="selectedCourse.pastBooks.length > 0">
                                    <li class="list-group-item"
                                        ng-cloak
                                        ng-repeat="data in selectedCourse.pastBooks | orderBy: (book.mine?0:1):true">

                                        <div class="pull-right">
                                            <button class="btn btn-xs btn-primary"
                                                    ng-click="addBookToCart(data)">
                                                <i class="fa fa-fw fa-plus"></i>
                                            </button>
                                        </div>

                                        <h4 class="list-group-item-heading no-pad-bottom">[[data.book.title]]</h4>
                                        <small >
                                            <span class="text-muted" > Ordered by [[data.order.placed_by.first_name]] [[data.order.placed_by.last_name]] for
                                                [[data.course.term.term_name]] [[data.course.term.year]]</span>
                                        </small>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">

                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-shopping-cart fa-fw"></i> Cart</h3>
                    </div>
                    <div class="panel-body">
                        <div ng-controller="NewBookController">
                            <cart></cart>
                        </div>

                        <h3 class="text-muted" ng-show="cartBooks.length == 0">There are no books in the cart.</h3>
                        <div class="row" ng-show="cartBooks.length > 0">
                            <button class="btn btn-success pull-right"
                                    ng-click="setStage(3)"
                                    style="margin: 20px;">
                                <i class="fa fa-arrow-right"></i> Review Order
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div ng-show="getStage() == STAGE_REVIEW_ORDERS">
            <div class="row">
                <button class="btn btn-primary pull-left"
                        ng-click="setStage(2)"
                        style="margin: 20px;">
                    <i class="fa fa-arrow-left"></i> Back
                </button>
            </div>

            <div ng-class="(courses | filter:similarCourses).length>0 ? 'col-md-6' : 'col-md-12'">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-book fa-fw"></i> Book Details</h3>
                    </div>
                    <div class="panel-body">
                        <ul class="list-group">
                            <li class="list-group-item"
                                ng-repeat="bookData in cartBooks">
                                <book-details book="bookData.book"></book-details>
                            </li>
                        </ul>
                    </div>

                </div>
            </div>
            <div class="col-md-6"
                 ng-if="(courses | filter:similarCourses).length>0">
                <div class="panel panel-default">

                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-university fa-fw"></i> Similar Courses</h3>
                    </div>


                    <div class="panel-body">

                        <h5 class="text-muted">
                            Select any additional courses that you would like to place this order for.
                        </h5>

                        <div class="list-group">
                            <div class="list-group-item active">

                                [[selectedCourse.department]] [[selectedCourse.course_number]]-[[selectedCourse.course_section]]
                                <span style="left: 50%; position: absolute">[[selectedCourse.user.last_name]], [[selectedCourse.user.first_name]]</span>
                            </div>

                            <div class="list-group-item cursor-pointer"
                                 ng-class="{active: isAdditionalCourseSelected(course)}"
                                 ng-click="toggleAdditionalCourseSelected(course)"
                                 ng-repeat="course in courses | filter:similarCourses ">

                                [[course.department]] [[course.course_number]]-[[course.course_section]]
                                <span style="left: 50%; position: absolute">[[course.user.last_name]], [[course.user.first_name]]</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <button class="btn btn-success pull-right"
                        ng-click="submitOrders()"
                        style="margin: 20px;">
                    <i class="fa fa-check"></i> Submit [[(courses | filter:similarCourses).length>0 ? '' + (getNumAdditionalCoursesSelected() + 1) + ' Order(s)' : '']]
                </button>
            </div>
        </div>

        <div class="col-lg-12" ng-show="getStage() == STAGE_ORDER_SUCCESS">

            <h1>Order successfully placed! Thank you!</h1>
        </div>



    </div>


</div>

@stop



@section('scripts-head')
    <script src="/javascripts/ng/helper.isbnHyphenate.js"></script>
    <script src="/javascripts/ng/app.orders.js"></script>
@stop