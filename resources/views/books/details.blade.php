@extends('layouts.master')




@section('content')

    @include('shared.partial.header', ['headerText'=>'Books', 'subHeaderText'=> $book->isbn13])


<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-book fa-fw"></i> Book Details</h3>
                <a href="/books/edit/{{ $book->book_id }}" class="btn btn-info " role="button">
                    <i class="fa fa-info-circle"></i> Edit
                </a>
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
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-striped">
                        <thead>
                        <tr>
                            <th>Course</th>
                            <th>Course Name</th>
                            <th>Ordered By</th>
                            <th>Quantity Requested</th>
                        </tr>
                        </thead>
                        <tbody>

                        @foreach ($book->orders as $order)
                            <tr>
                                <td>
                                    {{ $order->course->department }} {{ str_pad($order->course->course_number, 3, "0", STR_PAD_LEFT) }}-{{ $order->course->course_section }}
                                </td>
                                <td>{{ $order->course->course_name }}</td>

                                <td>
                                    {{ $order->ordered_by_name }}
                                </td>

                                <td>{{ $order->quantity_requested }}</td>
                            </tr>


                        @endforeach


                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@stop
