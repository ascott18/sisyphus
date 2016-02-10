@extends('layouts.master', [
    'breadcrumbs' => [
        ['Books', '/books'],
        ['Edit'],
        [$book->title],
    ]
])


@section('content')

    <div class="row">
        <div class="col-md-6" ng-controller="EditBookController">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-book fa-fw"></i> Edit Book </h3>
                </div>
                <form class="panel-body" name="form" action="/books/edit" method="POST" novalidate ng-submit="submit(form, $event)">
                    {!! csrf_field() !!}

                    <div ng-init="setBook({{$book}})">

                        @include('input.generic', ['name' => 'book.book_id', 'type' => 'hidden', 'label' => '', 'attrs' => []])

                        <div class="form-group">
                            <label for="isbn13">ISBN 13</label>
                            <input type="text" class="form-control" ng-disabled="true" placeholder="ISBN 13" isbn13 ng-model="book.isbn13">
                        </div>


                        @include('input.generic', ['name' => 'book.title', 'label' => 'Book Title',
                            'attrs' => ['required' => 'true']])

                        <div class="form-group" ng-init="addAuthors({{$book->authors}})">
                            <label>Author</label>
                            <input type="text" class="form-control" name="authors[0]" placeholder="Author" required ng-model="authors[0].name">

                            <div ng-cloak ng-show="form.$submitted || form['authors[0]'].$touched">
                                <span class="text-danger" ng-show="form['authors[0]'].$error.required">Author is required.</span>
                            </div>

                            <div ng-cloak class="input-group" ng-repeat="author in authors" style="margin-top: 10px" ng-if="!$first">

                                <input type="text" class="form-control" name="authors[ [[$index]] ]" ng-model="author.name" required>

                                <span class="input-group-addon cursor-pointer" ng-click="removeAuthor($index)">
                                    <i class="fa fa-times"></i>
                                </span>
                            </div>

                            <div style="margin-top: 10px;margin-bottom: 10px">
                                <a class="btn btn-info" ng-click="addAuthor()">Add Author</a>
                            </div>
                        </div>

                        @include('input.generic', ['name' => 'book.publisher', 'label' => 'Publisher',
                            'attrs' => ['required' => 'true']])

                        @include('input.generic', ['name' => 'book.edition', 'label' => 'Edition',
                            'attrs' => []])

                        <div class="form-group" >
                            <button class="btn btn-success pull-right"
                                    type="submit">
                                <i class="fa fa-check"></i> Save
                            </button>

                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@section('scripts-head')
    <script src="/javascripts/ng/helper.isbnHyphenate.js"></script>
    <script src="/javascripts/ng/app.books.js"></script>
@stop