@extends('layouts.master')




@section('content')
    @include('shared.partial.header', ['headerText'=>'Requests', 'subHeaderText'=>'All Requests'])

<div>
    <div class="row" ng-controller="OrdersController">
        <div class="col-lg-12" ng-show="getStage() == STAGE_SELECT_COURSE">

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-pencil fa-fw"></i>My Courses</h3>
                </div>
                <div class="panel-body">

                    <table class="list-group" style="width: 100%">
                        <thead>
                            <th style="width: 100%"></th>
                            <th ></th>
                        </thead>

                        <tbody>
                            <tr class="list-group-item" ng-repeat="course in courses">
                                <td style="width: 100%">
                                    <h4 class="list-group-item-heading">[[course.department]] [[course.course_number]]-[[course.course_section]] [[course.course_name]]</h4>

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
                                            <span class="text-muted">[[order.book.isbn13]]</span>: [[order.book.title]]
                                            </br>
                                        </span>
                                    </p>
                                </td>

                                <td style="vertical-align: top">
                                    <span ng-if="!course.no_book">
                                    <a ng-click="placeRequestForCourse(course)"
                                       class="btn btn-primary"
                                       style="width:100%; margin-bottom:5px">
                                        Place a request <i class="fa fa-arrow-right fa-fw"></i>
                                    </a>
                                        <span ng-if="course.orders.length == 0">
                                            <br>
                                            <button
                                                    ng-confirm-click="noBook(course)"
                                                    ng-confirm-click-message="Are you sure you don't want a book?"
                                                    class="btn btn-danger"
                                                    style="width:100%">
                                                <i class="fa fa-times"></i> No book needed</button>
                                        </span>
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


        <div class="col-lg-12" ng-show="getStage() == STAGE_SELECT_BOOKS" ng-controller="NewBookController">



            <div class="col-md-6">
                <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">


                    <div>


                        <!-- Nav tabs -->
                        <ul class="nav nav-tabs" role="tablist">
                            <li role="presentation" class="active"><a href="#newbook" aria-controls="newbook" role="tab" data-toggle="tab">New Book</a></li>
                            <li role="presentation"><a href="#pastbooks" aria-controls="pastbooks" role="tab" data-toggle="tab">Past Books</a></li>
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

                                <ul class="list-group">
                                    <li class="list-group-item cursor-pointer"
                                        ng-cloak
                                        ng-repeat="data in selectedCourse.pastBooks |orderBy: (book.mine?0:1):true">

                                        <div class="pull-right">
                                            <button class="btn btn-xs btn-primary"
                                                    ng-click="addBookToCart(data)">
                                                <i class="fa fa-fw fa-plus"></i>
                                            </button>
                                        </div>

                                        <h4 class="list-group-item-heading no-pad-bottom">[[data.book.title]]</h4>
                                        <small >
                                            <span class="text-muted" > Ordered by Stuart Glenn Steiner for Fall 2014</span>
                                        </small>

                                    </li>
                                </ul>

                            </div>
                        </div>

                    </div>

                </div>


            </div>

            <div class="col-md-6">

                <h3>Cart</h3>
                <div ng-controller="NewBookController">
                    <cart></cart>
                </div>


                <div class="row">
                    <button class="btn btn-success pull-right"
                            ng-click="setStage(3)"
                            style="margin: 20px;">
                        <i class="fa fa-arrow-right"></i> Review Order
                    </button>
                </div>
            </div>

        </div>

        <div class="col-lg-12" ng-show="getStage() == STAGE_REVIEW_ORDERS">

            <div class="row">
                <button class="btn btn-primary pull-left"
                        ng-click="setStage(2)"
                        style="margin: 20px;">
                    <i class="fa fa-arrow-left"></i> Back
                </button>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-book fa-fw"></i> Book Details</h3>
                </div>
                <div class="panel-body" ng-controller="NewBookController">
                    <ul class="list-group">


                                <li class="list-group-item cursor-pointer"
                                    ng-repeat="bookData in cartBooks">
                                    <book-details book="bookData.book"></book-details>
                                </li>

                    </ul>


                </div>
            </div>


            <div class="row">
                <button class="btn btn-success pull-right"
                        ng-click="submitOrders()"
                        style="margin: 20px;">
                    <i class="fa fa-check"></i> Submit
                </button>
            </div>
        </div>

        <div class="col-lg-12" ng-show="getStage() == STAGE_CONFIRMATION">

            <h1>OMG! You totes placed an order!</h1>
        </div>



    </div>


</div>

@stop



@section('scripts-head')
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.4.5/angular.min.js"></script>
    <script src="/javascripts/ng/app.js"></script>
    <script src="/javascripts/ng/app.orders.js"></script>
@stop