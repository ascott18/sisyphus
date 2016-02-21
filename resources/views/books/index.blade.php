@extends('layouts.master', [
    'breadcrumbs' => [
        ['Books', '/books'],
        ['All Books'],
    ]
])

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-book fa-fw"></i> All Books</h3>
                </div>
                <div class="panel-body">


                    <div ng-controller="BooksController" class="table-responsive">
                        <table st-pipe="callServer"
                               st-table="displayed"
                               class="table table-hover"
                               empty-placeholder>
                            <thead>
                            <tr>
                                <th st-sort="title">Title</th>
                                <th st-sort="author">Author</th>
                                <th st-sort="publisher">Publisher</th>
                                <th st-sort="isbn13">ISBN</th>
                                <th width="1%"></th>
                            </tr>
                            <tr>
                                <th><input type="text" class="form-control" placeholder="Search..." st-search="title"/></th>
                                <th><input type="text" class="form-control" placeholder="Search..." st-search="author"/></th>
                                <th><input type="text" class="form-control" placeholder="Search..." st-search="publisher"/></th>
                                <th><input type="text" class="form-control" placeholder="Search..." st-search="isbn13"/></th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>

                            <tr ng-cloak ng-repeat="book in displayed">
                                <td>[[ book.title + ' ' + book.edition ]]</td>
                                <td>
                                    <span ng-repeat="author in book.authors">
                                        [[ author.name]] [[ $last ? '' : '|']]
                                    </span>
                                </td>
                                <td>[[ book.publisher ]]</td>
                                <td>[[ book.isbn13 | isbnHyphenate]]</td>
                                <td>
                                    <a href="/books/details/[[ book.book_id ]]" class="btn btn-sm btn-primary" role="button">
                                        Details <i class="fa fa-arrow-right"></i>
                                    </a>
                                </td>
                            </tr>


                            </tbody>

                            <tfoot>
                                <td class="text-center" st-pagination="" st-items-by-page="10" colspan="5"></td>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@stop

@section('scripts-head')
    <script src="/javascripts/ng/helper.isbnHyphenate.js"></script>
    <script src="/javascripts/ng/app.books.js"></script>
@stop