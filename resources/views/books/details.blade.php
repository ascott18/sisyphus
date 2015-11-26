@extends('layouts.master')

@section('area', 'Books')
@section('page', $book->isbn13)

@section('content')

<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-book fa-fw"></i> Book Details</h3>
            </div>
            <div class="panel-body">

                <dl class="dl-horizontal">
                    <dt>Title</dt>
                    <dd>
                        {{ $book->title }}
                    </dd>

                    <dt>Authors</dt>
                    <dd>
                        <?php $index = 0; ?>
                        @foreach($book->authors as $author)
                            {{$author->last_name}}, {{$author->first_name}}
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
                        {{ $book->isbn13 }}
                    </dd>
                </dl>
            </div>
        </div>


        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-history fa-fw"></i> Past Orders</h3>
            </div>
            <div class="panel-body">
                <div ng-controller="BookDetailsController as bdc" class="table-responsive">
                    <table st-pipe="bdc.callServer" st-table="bdc.displayed" class="table table-bordered table-hover table-striped">
                        <thead>
                        <tr>
                            <th st-sort="section">Course</th>
                            <th st-sort="course_name">Course Name</th>
                            <th st-sort="ordered_by_name">Ordered By</th>
                            <th st-sort="quantity_requested">Quantity Requested</th>
                        </tr>
                        <tr>
                            <th><input type="text" class="form-control" placeholder="Search..." st-search="section"/></th>
                            <th><input type="text" class="form-control" placeholder="Search..." st-search="course_name"/></th>
                            <th><input type="text" class="form-control" placeholder="Search..." st-search="ordered_by_name"/></th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                            <tr ng-repeat="order in bdc.displayed">
                                <td>
                                    [[ order.department ]] [[ order.course_number | zpad:3 ]]-[[ order.course_section | zpad:2 ]]
                                </td>
                                <td>[[ order.course_name ]]</td>

                                <td>
                                    [[ order.order_by_name ]]
                                </td>

                                <td>[[ order.quantity_requested ]]</td>
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
    <script>
        book_id_init = new String('{{ $book->book_id }}');
    </script>

    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.4.5/angular.min.js"></script>
    <script src="/javascripts/ng/smart-table/smart-table.min.js"></script>
    <script src="/javascripts/ng/app.js"></script>
    <script src="/javascripts/ng/helper.isbnHyphenate.js"></script>
    <script src="/javascripts/ng/app.books.js"></script>
@stop