@extends('layouts.master')




@section('content')
    @include('shared.partial.header', ['headerText'=>'Books', 'subHeaderText'=> 'Edit '.$book->isbn13])


    <div class="col-md-6" ng-controller="EditBookController">

        <div class="panel-group" aria-multiselectable="true" ng-init="setBook({{$book}})">

            <div class="form-group">
                <label for="bookTitle">Book Title</label>
                <input type="text" class="form-control" name="bookTitle" ng-model="book.title">
            </div>

            <div class="form-group" ng-init="addAuthors({{$book->authors}})">
                <label for="author1">Author</label>
                <input type="text" class="form-control" name="author1" ng-model="authors[0].first_name">


                <div class="input-group" ng-repeat="author in authors" style="margin-top: 10px" ng-show="!$first">
                    <input type="text" class="form-control" ng-model="author.first_name">

                <span class="input-group-addon" ng-click="removeAuthor($index)">
                    <i class="fa fa-times"></i>
                </span>
                </div>


                <div style="margin-top: 10px;margin-bottom: 10px">
                    <button class="btn btn-info" ng-click="addAuthor()">Add Author</button>
                </div>

                <div class="form-group">
                    <label for="publisher">Publisher</label>
                    <input type="text" class="form-control" name="publisher" ng-model="book.publisher">
                </div>

                <div class="form-group">
                    <label for="isbn13">ISBN 13</label>
                    <input type="text" class="form-control" name="isbn13" ng-model="book.isbn13">
                </div>

                <div class="form-group">
                    <label for="edition">Edition</label>
                    <input type="text" class="form-control" name="edition" ng-model="book.edition">
                </div>

                <button class="btn btn-success"
                        ng-click="addNewBookToCart(book)">
                    <i class="fa fa-plus"></i> Save
                </button>

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