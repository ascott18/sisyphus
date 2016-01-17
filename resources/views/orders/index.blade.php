@extends('layouts.master')

@section('area', 'Requests')
@if (isset($course))
    @section('page', 'Place Request')
@else
    @section('page', 'My Courses')
@endif
@section('content')


<div>
    <div ng-cloak class="row" ng-controller="OrdersController"
            ng-init="
                terms = {{$openTerms}};
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
            <div class="col-lg-offset-1 col-lg-10"
                 ng-show="courses.length == 0">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-university fa-fw"></i>
                            <span ng-if="terms.length == 0">No Terms Open</span>
                            <span ng-if="terms.length > 0">No Courses Available</span>
                        </h3>
                    </div>
                    <div class="panel-body">
                        <h3 ng-hide="courses.length" class="text-muted">

                            <span ng-if="terms.length == 0">
                                No terms are open for ordering.
                            </span>
                            <span ng-if="terms.length > 0">
                                No courses found for the following terms open for ordering:
                                <br>
                                <br>
                                <ul>
                                    <li ng-repeat="term in terms">[[term.term_name]] [[ term.year ]]</li>
                                </ul>
                            </span>
                        </h3>
                    </div>
                </div>
            </div>

            <div class="col-lg-offset-1 col-lg-10"
                 ng-show="courses.length > 0">
                <div class="panel panel-default"
                     ng-repeat="course in courses"
                     ng-init="term = (terms | filter:{term_id: course.term_id})[0]">
                    <div class="panel-heading">
                        <h3 class="panel-title clearfix">
                            [[course.department]] [[course.course_number | zpad:3]]-[[course.course_section | zpad:2]] [[course.course_name]]
                            <span class="text-muted pull-right">
                                [[ term.term_name ]] [[ term.year]]
                            </span>
                        </h3>
                    </div>
                    <div class="panel-body ">
                        <div class="pull-left">
                            <div style="margin-left: 0em">
                                <span ng-show="courseNeedsOrders(course)" >
                                    No response submitted. Please let us know what you need!
                                </span>
                                <span ng-show="course.no_book" class="text-muted">
                                    No books needed. Thank you for letting us know!
                                </span>
                            </div>

                            <ul style="list-style-type: none; padding-left: 0px">
                                <li ng-repeat="order in course.orders">
                                    <i class="fa fa-times text-danger cursor-pointer"
                                       title="Delete Order"
                                       ng-confirm-click="deleteOrder(course, order)"
                                       ng-confirm-click-message="Are you sure you want to delete the order for [[order.book.title]]?"></i>
                                    <span class="text-muted">[[order.book.isbn13 | isbnHyphenate]]</span>: [[order.book.title]]
                                </li>
                            </ul>
                        </div>
                        <div class="pull-right">
                            <a ng-click="placeRequestForCourse(course)"
                               class="btn "
                               ng-class="!courseNeedsOrders(course) ? 'btn-default' : 'btn-primary'">
                                Place a request <i class="fa fa-arrow-right fa-fw"></i>
                            </a>
                            <span >
                                <button
                                        ng-confirm-click="noBook(course)"
                                        ng-confirm-click-message="Are you sure you don't want a book? [[course.orders.length ? '\n\nAll orders on this course will be deleted!' : '']]"
                                        class="btn"
                                        ng-disabled="course.no_book"
                                        style="margin-left: 10px"
                                        ng-class="!courseNeedsOrders(course) ? 'btn-default' : 'btn-danger'">
                                    <i class="fa fa-times"></i> No book needed</button>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div ng-show="getStage() == STAGE_SELECT_BOOKS" ng-controller="NewBookController">

            <div class="col-md-6">

                <!-- Nav tabs -->
                <ul class="nav nav-tabs h3" role="tablist" style="font-size: 20px;">

                    <li role="presentation" class="active">
                        <a href="#newbook" aria-controls="newbook" role="tab" data-toggle="tab">
                            <i class="fa fa-star"></i> Enter a New Book
                        </a>
                    </li>
                    <li role="presentation">
                        <a href="#pastbooks" aria-controls="pastbooks" role="tab" data-toggle="tab">
                            <i class="fa fa-history"></i> Select a Past Book
                        </a>
                    </li>
                </ul>

                <div class="panel panel-default">
                    {{--<div class="panel-heading">--}}
                        {{--<h3 class="panel-title"><i class="fa fa-book fa-fw"></i> Select Books</h3>--}}
                    {{--</div>--}}
                    <div>


                        <!-- Tab panes -->
                        <div class="tab-content">
                            <div role="tabpanel" class="panel-body tab-pane active" id="newbook">

                                <div ng-controller="NewBookController">
                                    <book-editor></book-editor>
                                </div>
                            </div>

                            <div role="tabpanel" class="panel-body panel-list tab-pane" id="pastbooks">


                                <h3 class="text-muted"
                                        ng-show="!selectedCourse.pastBooks">
                                    Loading past books...
                                </h3>

                                <h3 class="text-muted"
                                        ng-show="selectedCourse.pastBooks.length == 0">
                                    There are no known past books for this course.
                                </h3>

                                    <div class="panel-list-item"
                                        ng-cloak
                                        ng-show="selectedCourse.pastBooks.length > 0"
                                        ng-repeat="bookData in selectedCourse.pastBooks">

                                        <div class="pull-right">
                                            <button class="btn btn-xs btn-primary"
                                                    ng-click="addBookToCart(bookData)">
                                                <i class="fa fa-fw fa-plus"></i>
                                            </button>
                                        </div>

                                        <book-details book="bookData.book">
                                            <span ng-repeat="termData in bookData.terms">
                                                <br>
                                                [[termData.term.term_name]] [[termData.term.year]]:
                                                <span ng-repeat="data in termData.orderData">
                                                    [[data.course.user.first_name]] [[data.course.user.last_name]]
                                                    ( <ng-pluralize count="data.numSections" when="{
                                                        'one': '{} Section',
                                                        'other': '{} Sections'}">
                                                    </ng-pluralize> )
                                                    [[$last ? '' : ($index==book.authors.length-2) ? ', and ' : ', ']]
                                                </span>
                                            </span>
                                        </book-details>
                                    </div>
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
                    <div class="panel-body panel-list"
                         ng-controller="NewBookController">

                        <h3 class="text-muted" ng-show="cartBooks.length == 0">
                            There are no books in the cart.
                        </h3>

                        <div class="panel-list-item"
                            ng-cloak
                            ng-repeat="bookData in cartBooks">

                            <div class="pull-right">
                                <button class="btn btn-xs btn-danger"
                                        ng-click="deleteBookFromCart(bookData)">
                                    <i class="fa fa-fw fa-times"></i>
                                </button>
                            </div>

                            <book-details book="bookData.book"></book-details>

                        </div>
                    </div>
                </div>

                <button class="btn btn-success pull-right"
                        ng-disabled="cartBooks.length == 0"
                        ng-click="setStage(3)">
                    Review Request <i class="fa fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <div ng-show="getStage() == STAGE_REVIEW_ORDERS">


            <div ng-class="(courses | filter:similarCourses).length>0 ? 'col-md-6' : 'col-md-12'">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-shopping-cart fa-fw"></i> Cart</h3>
                    </div>
                    <div class="panel-body panel-list">
                        <div class="panel-list-item"
                            ng-repeat="bookData in cartBooks">
                            <book-details book="bookData.book"></book-details>
                        </div>
                    </div>

                </div>
            </div>
            <div class="col-md-6"
                 ng-if="(courses | filter:similarCourses).length>0">
                <div class="panel panel-default">

                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-university fa-fw"></i> Similar Courses</h3>
                    </div>


                    <div class="panel-body panel-list">

                        <h5 class="text-muted" style="margin-top: 0; margin-bottom: 30px;">
                            Select any additional courses that you would like to place this request for.
                        </h5>

                        <div class="panel-list-item active">

                            [[selectedCourse.department]] [[selectedCourse.course_number | zpad:3]]-[[selectedCourse.course_section | zpad:2]]
                            <span style="left: 50%; position: absolute">[[selectedCourse.user.last_name]], [[selectedCourse.user.first_name]]</span>
                        </div>

                        <div class="panel-list-item cursor-pointer"
                             ng-class="{active: isAdditionalCourseSelected(course)}"
                             ng-click="toggleAdditionalCourseSelected(course)"
                             ng-repeat="course in courses | filter:similarCourses ">

                            [[course.department]] [[course.course_number | zpad:3]]-[[course.course_section | zpad:2]]
                            <span style="left: 50%; position: absolute">[[course.user.last_name]], [[course.user.first_name]]</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <button class="btn btn-success pull-right"
                        ng-click="submitOrders()">
                    <i class="fa fa-check"></i>
                    Submit
                    <ng-pluralize count="getNumAdditionalCoursesSelected() + 1"
                                  when="{0: 'Request',
                                         'one': 'Request',
                                         'other': '{} Requests'}">
                    </ng-pluralize>
                </button>

                <button class="btn btn-primary pull-right "
                        ng-click="setStage(2)"
                        style="margin-right: 15px;">
                    <i class="fa fa-arrow-left"></i> Make Revisions
                </button>
            </div>
        </div>

        <div class="col-lg-12" ng-show="getStage() == STAGE_ORDER_SUCCESS">

            <h1>Request successfully placed! Thank you!</h1>
        </div>



    </div>


</div>

@stop



@section('scripts-head')
    <script src="/javascripts/ng/helper.isbnHyphenate.js"></script>
    <script src="/javascripts/ng/app.orders.js"></script>
@stop