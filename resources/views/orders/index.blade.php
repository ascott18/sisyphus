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
                                    <a ng-click="setStage(2)"
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

                                <form novalidate class="simple-form" name="form">
                                <div class="form-group" ng-class="{'has-error': (submitted || form.isbn13.$touched) && form.isbn13.$error.required}">
                                    <label for="isbn13">ISBN 13</label>
                                    <input type="text" class="form-control" name="isbn13" placeholder="ISBN 13" ng-model="book.isbn" required="">
                                    <div ng-show="submitted || form.isbn13.$touched">
                                        <div ng-show="form.isbn13.$error.required">Required</div>
                                    </div>
                                </div>

                                <div class="form-group" ng-class="{'has-error': (submitted || form.bookTitle.$touched) && form.bookTitle.$error.required}">
                                    <label for="bookTitle">Book Title</label>
                                    <input type="text" class="form-control" name="bookTitle" placeholder="Book Title" ng-model="book.title" required="">
                                    <div ng-show="submitted || form.bookTitle.$touched">
                                        <div ng-show="form.bookTitle.$error.required">Required</div>
                                    </div>
                                </div>

                                <div class="form-group" ng-class="{'has-error': (submitted || form.author1.$touched) && form.author1.$error.required}">
                                    <label for="author1">Author</label>
                                    <input type="text" class="form-control" name="author1" placeholder="Author" ng-model="authors[0].name" required="">
                                    <div ng-show="submitted || form.author1.$touched">
                                        <div ng-show="form.author1.$error.required">Required</div>
                                    </div>

                                    <div class="input-group" ng-repeat="author in authors" style="margin-top: 10px"  ng-show="!$first">
                                        <input type="text" class="form-control"  placeholder="Author" ng-model="author.name">
                                        <span class="input-group-addon" ng-click="removeAuthor($index)">
                                            <i class="fa fa-times"></i>
                                        </span>
                                    </div>
                                </div>

                                    <div style="margin-top: 10px;margin-bottom: 10px">
                                        <button class="btn btn-info" ng-click="addAuthor()">Add Author</button>
                                    </div>

                                    <div class="form-group" ng-class="{'has-error': (submitted || form.publisher.$touched) && form.publisher.$error.required}">
                                        <label for="publisher">Publisher</label>
                                        <input type="text" class="form-control" name="publisher" placeholder="Publisher" ng-model="book.publisher" required="">
                                        <div ng-show="submitted || form.publisher.$touched">
                                            <div ng-show="form.publisher.$error.required">Required</div>
                                        </div>
                                    </div>



                                    <div class="form-group" ng-class="{'has-error': (submitted || form.edition.$touched) && form.edition.$error.required}">
                                        <label for="edition">Edition</label>
                                        <input type="text" class="form-control" name="edition" placeholder="Edition" ng-model="book.edition" required="">
                                        <div ng-show="submitted || form.edition.$touched">
                                            <div ng-show="form.edition.$error.required">Required</div>
                                        </div>
                                    </div>

                                    <button type="button" class="btn btn-primary"
                                            ng-click="addNewBookToCart(book,form)"
                                            ng-disabled="form.$invalid">
                                        <i class="fa fa-plus"></i> Add to Cart
                                    </button>

                                </form>





                            </div>

                            <div role="tabpanel" class="tab-pane" id="pastbooks">

                                </br>

                                <ul class="list-group">
                                    <li class="list-group-item cursor-pointer"
                                        ng-cloak
                                        ng-repeat="book in pastBooks |orderBy: (book.mine?0:1):true">

                                        <div class="pull-right">
                                            <button class="btn btn-xs btn-primary"
                                                    ng-click="addBookToCart(book)">
                                                <i class="fa fa-fw fa-plus"></i>
                                            </button>
                                        </div>

                                        <h4 class="list-group-item-heading no-pad-bottom">[[book.title]]</h4>
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
                <ul class="list-group">
                    <li class="list-group-item cursor-pointer"
                        ng-cloak
                        ng-repeat="book in cartBooks">

                        <div class="pull-right">
                            <button class="btn btn-xs btn-danger"
                                    ng-click="deleteBookFromCart(book)">
                                <i class="fa fa-fw fa-times"></i>
                            </button>
                        </div>

                        <h4 class="list-group-item-heading no-pad-bottom">[[book.title]]</h4>
                        <small >
                            <span class="text-muted" >1234567891234</span>
                            <br>
                            <span class="text-muted" > Author Name, Author2 Name</span>
                        </small>

                    </li>
                </ul>


                <div class="row">
                    <button class="btn btn-success pull-right"
                            ng-click="addInputBookToCart()"
                            style="margin: 20px;">
                        <i class="fa fa-arrow-right"></i> Review Order
                    </button>
                </div>
            </div>

        </div>



    </div>


</div>

@stop



@section('scripts-head')
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.4.5/angular.min.js"></script>
    <script src="/javascripts/ng/app.js"></script>
    <script src="/javascripts/ng/app.orders.js"></script>
@stop