@extends('layouts.master')




@section('content')

    @include('shared.partial.header', ['headerText'=>'Books', 'subHeaderText'=>'All Books'])

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-book fa-fw"></i> All Books</h3>
                </div>
                <div class="panel-body">


                    <div ng-controller="BooksController as mc" class="table-responsive">
                        <table st-pipe="mc.callServer" st-table="mc.displayed" class="table table-bordered table-hover table-striped">
                            <thead>
                            <tr>
                                <th st-sort="title">Title</th>
                                <th st-sort="publisher">Publisher</th>
                                <th st-sort="isbn13">ISBN</th>
                                <th>Details</th>
                            </tr>
                            <tr>
                                <th><input type="text" class="form-control" placeholder="Search..." st-search="title"/></th>
                                <th><input type="text" class="form-control" placeholder="Search..." st-search="publisher"/></th>
                                <th><input type="text" class="form-control" placeholder="Search..." st-search="isbn13"/></th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>

                            <tr ng-repeat="book in mc.displayed">
                                <td>
                                    <div>
                                        [[ book.title ]]
                                    </div>
                                    <div class="text-muted">
                                        <span ng-repeat="author in book.authors">
                                        [[ author.last_name ]], [[ author.first_name ]] |
                                        </span>
                                    </div>
                                </td>
                                <td>[[ book.publisher ]]</td>
                                <td>[[ book.isbn13 ]]</td>
                                <td>
                                    <a href="/books/show/[[ book.book_id ]]" class="btn btn-info" role="button">
                                        <i class="fa fa-info-circle"></i> Details
                                    </a>
                                </td>
                            </tr>


                            </tbody>
                            <tfoot>
                            <tr>
                                <td class="text-center" st-pagination="" st-items-by-page="10" colspan="4">
                                </td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
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