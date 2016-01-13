@extends('layouts.master')

@section('area', 'Books')
@section('page', $book->title)

@section('content')

<div ng-controller="BookDetailsController as bdc"  class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-book fa-fw"></i> Book Details</h3>

            </div>
            <div class="panel-body">
                <div class="col-sm-6">
                    {{--<a href="/books/edit/{{ $book->book_id }}" class="btn btn-info " role="button">--}}
                        {{--<i class="fa fa-info-circle"></i> Edit--}}
                    {{--</a>--}}
                    <dl class="dl-horizontal">
                        <dt>Title</dt>
                    <dd>
                        {{ $book->title }}
                    </dd>

                    <dt>{{count($book->authors) == 1 ? "Author" : "Authors"}}</dt>
                    <dd>
                        @if (count($book->authors) == 0)
                            <span class="text-muted">No Authors</span>
                        @endif
                        <?php $index = 0; ?>
                        @foreach($book->authors as $author)
                            {{$author->name}}
                            @if ($index++ != count($book->authors)-1)
                                <br/>
                            @endif
                        @endforeach
                    </dd>
                    <dt>Publisher</dt>
                    <dd>
                        {{ $book->publisher }}
                    </dd>
                    <dt>ISBN 13</dt>
                    <dd>
                        [["{{ $book->isbn13 }}" | isbnHyphenate]]
                    </dd>
                    </dl>
                </div>
                <div class="col-sm-6">
                    <img ng-src="[[ book_cover_img ]]"/>
                </div>

            </div>
        </div>


        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-history fa-fw"></i> Past Orders</h3>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table st-pipe="bdc.callServer" st-table="bdc.displayed"
                           class="table table-hover"
                           empty-placeholder>
                        <thead>
                        <tr>
                            <th>Term</th>
                            <th st-sort="section">Course</th>
                            <th st-sort="course_name">Course Name</th>
                            <th>Required</th>
                            <th width="1%"></th>
                        </tr>
                        <tr>
                            <th></th>
                            <th><input type="text" class="form-control" placeholder="Search..." st-search="section"/></th>
                            <th><input type="text" class="form-control" placeholder="Search..." st-search="course_name"/></th>
                            <th></th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                            <tr ng-repeat="order in bdc.displayed">
                                <td>[[ order.course.term.term_name ]]</td>
                                <td>
                                    [[ order.department ]] [[ order.course_number | zpad:3 ]]-[[ order.course_section | zpad:2 ]]
                                </td>
                                <td>[[ order.course_name ]]</td>
                                <td>[[ order.required ? "Yes" : "No" ]]</td>

                                <td>
                                    <a ng-if="order.course.canView" class="btn btn-sm btn-info" href="/courses/details/[[order.course_id]]" role="button">
                                        Course Details <i class="fa fa-arrow-right"></i>
                                    </a>
                                </td>

                                {{--<td>[[ order.quantity_requested ]]</td>--}}
                            </tr>
                        </tbody>
                        <tfoot>
                        <tr>
                            <td class="text-center" st-pagination="" st-items-by-page="10" colspan="5">
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
    <script>
        book_id_init = new String('{{ $book->book_id }}');
        book_isbn_13_init = new String('{{ $book->isbn13 }}');
    </script>

    <script src="http://crypto-js.googlecode.com/svn/tags/3.0.2/build/rollups/hmac-sha256.js"></script>
    <script src="http://crypto-js.googlecode.com/svn/tags/3.0.2/build/components/enc-base64.js"></script> <!-- tmp? -->
    <script src="/javascripts/ng/helper.isbnHyphenate.js"></script>
    <script src="/javascripts/ng/app.books.js"></script>
@stop