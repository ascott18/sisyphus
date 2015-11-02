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
                        @foreach ($courses as $course)
                            <tr class="list-group-item" >
                                <td style="width: 100%">
                                    <h4 class="list-group-item-heading">{{ $course->displayIdentifier() }}: {{$course->course_name}}</h4>

                                    <p style="margin-left: 3em">
                                        @if(count($course->orders) == 0)
                                        <br>
                                        <span class="well well-sm" style="background-color: #fcf8e3; border-color: #fbeed5">
                                            No requests submitted - please let us know what you need!
                                        </span>

                                        @endif
                                        @foreach($course->orders as $order)
                                            <?php $book = $order->book;?>
                                            <span class="text-muted">{{$book->isbn13}}</span>: {{$book->title}}<br>
                                        @endforeach
                                    </p>
                                </td>

                                <td style="vertical-align: top">
                                    <a href="/orders/create/{{$course->course_id}}"
                                       class="btn btn-primary"
                                       style="width:100%; margin-bottom:5px">
                                        Place a request <i class="fa fa-arrow-right fa-fw"></i>
                                    </a>

                                    @if(count($course->orders) == 0)
                                        <br>
                                        <button class="btn btn-danger" style="width:100%"><i class="fa fa-times"></i> No book needed</button>
                                    @endif
                                </td>

                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


        <div class="col-lg-12" ng-show="getStage() == STAGE_SELECT_BOOKS">

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

                <hr>

                <h3>Past Books</h3>
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

            <div class="col-md-6" ng-controller="NewBookController">

                <h3>New Book</h3>
                    <div class="form-group">
                        <label for="bookTitle">Book Title</label>
                        <input type="text" class="form-control" name="bookTitle" placeholder="Book Title" required>
                    </div>

                    <div class="form-group">
                        <label for="author1">Author</label>
                        <input type="text" class="form-control" name="author1" placeholder="Author">

                        <div class="input-group" ng-repeat="author in authors" style="margin-top: 10px">
                            <input type="text" class="form-control"  placeholder="Author" >
                        <span class="input-group-addon" ng-click="removeAuthor($index)">
                            <i class="fa fa-times"></i>
                        </span>
                        </div>

                        <div style="margin-top: 10px;">
                            <button class="btn btn-info" ng-click="addAuthor()">Add Author</button>
                        </div>

                    </div>


                    <div class="form-group">
                        <label for="publisher">Publisher</label>
                        <input type="text" class="form-control" name="publisher" placeholder="Publisher">
                    </div>

                    <div class="form-group">
                        <label for="isbn13">ISBN 13</label>
                        <input type="text" class="form-control" name="isbn13" placeholder="ISBN 13">
                    </div>

                    <div class="form-group">
                        <label for="edition">Edition</label>
                        <input type="text" class="form-control" name="edition" placeholder="Edition">
                    </div>


                    <button class="btn btn-primary"
                            ng-click="submitNewBook()">
                        <i class="fa fa-plus"></i> Add to Cart
                    </button>

            </div>

        </div>



    </div>

    <div class="row">
        <button class="btn btn-success pull-right"
                ng-click="addInputBookToCart()"
                style="margin: 20px;">
            <i class="fa fa-arrow-right"></i> Review Order
        </button>
    </div>


</div>

@stop



@section('scripts-head')
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.4.5/angular.min.js"></script>
    <script src="/javascripts/ng/app.js"></script>
    <script src="/javascripts/ng/app.orders.js"></script>
@stop