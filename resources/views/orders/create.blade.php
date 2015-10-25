@extends('layouts.master')


@section('content')
    @include('shared.partial.header', ['headerText'=>'Requests', 'subHeaderText'=>'Create a request'])


<div class="row" ng-app="sisyphus" ng-controller="OrdersController">

    {{ $user->first_name }} {{ $user->last_name }}

    @foreach($user->courses as $course)
        <tr>
            <td>
                {{ $course->department }} {{ str_pad($course->course_number, 3, "0", STR_PAD_LEFT) }}-{{ $course->course_section }}
            </td>
            <td>{{ $course->course_name }}</td>

        </tr>
    @endforeach

    <i class="fa fa-spinner fa-spin fa-5x" style="margin-left: 48%" ng-show="stage == null"></i>

    <div class="col-lg-8 col-lg-offset-2" ng-cloak ng-show="stage == 1">
        <div class="btn-group btn-group-lg"  style="width: 100%">
            <button class="btn btn-primary btn-lg" ng-click="stage = 2" style="height: 100px; width: 100%">
                <i class="fa fa-2x fa-history fa-align-middle fa-fw"></i>&nbsp; Order a book I used before
            </button>
        </div>

        <br/>
        <br/>
        <br/>

        <div class="btn-group btn-group-lg"  style="width: 100%">
            <button class="btn btn-primary btn-lg" ng-click="stage = 3" style="height: 100px; width: 100%">
                <i class="fa fa-2x fa-book fa-align-middle fa-fw"></i>&nbsp; Order some other book
            </button>
        </div>

        <br/>
        <br/>
        <br/>

        <div class="btn-group btn-group-lg"  style="width: 100%">
            <button class="btn btn-primary btn-lg" ng-click="stage = 4" style="height: 100px; width: 100%">
                <i class="fa fa-2x fa-times fa-align-middle fa-fw"></i>&nbsp; I don't need a book
            </button>
        </div>
    </div>





    <div class="col-lg-12" ng-cloak ng-show="stage == 3">
        <form action="/orders" method="POST">
            {!! csrf_field() !!}

            <div class="form-group">
                <label for="bookTitle">Book Title</label>
                <input type="text" class="form-control" name="bookTitle" placeholder="Book Title">
            </div>

            <h1 class="panel panel-danger">TODO: allow multiple authors to be entered</h1>

            <div class="form-group">
                <label for="author1">Author</label>
                <input type="text" class="form-control" name="author1" placeholder="Author">
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


            <button type="submit" class="btn btn-success">
                <i class="fa fa-check"></i> Place Order
            </button>
        </form>
    </div>


    <div class="col-lg-12" ng-cloak ng-show="stage == 4">

        <form action="/orders" method="POST">
            {!! csrf_field() !!}

            <input type="hidden" name="noBook" value="true">

            <h4>You have selected that you do not want a book for course COURSE NAME HERE</h4>

            <br/>

            <span>Is this correct?</span>

            <br/>
            <br/>
            <br/>

            <button type="submit" class="btn btn-success">
                <i class="fa fa-check"></i> Confirm
            </button>
        </form>
    </div>
</div>
@stop

@section('scripts-head')
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.4.5/angular.min.js"></script>
    <script src="/javascripts/ng/app.js"></script>
    <script src="/javascripts/ng/app.orders.js"></script>
@stop