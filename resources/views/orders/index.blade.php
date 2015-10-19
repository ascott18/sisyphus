@extends('layouts.master')




@section('content')
    @include('shared.partial.header', ['headerText'=>'Orders', 'subHeaderText'=>'All Orders'])


<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-book fa-fw"></i> All Books</h3>
            </div>
            <div class="panel-body">


                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-striped">
                        <thead>
                        <tr>
                            <th>Ordered By</th>
                            <th>Title</th>
                            <th>Quantity</th>
                        </tr>
                        </thead>
                        <tbody>

                        @foreach ($orders as $order)
                            <tr>
                                <td>{{ $order->ordered_by_name }} </td>
                                <td>
                                    <div>
                                        {{ $order->book->title }}
                                    </div>
                                    <div class="text-muted">
                                        <?php $index = 0; ?>
                                        @foreach($order->book->authors as $author)
                                            {{$author->first_name}} {{$author->last_name}}
                                            @if ($index++ != count($order->book->authors)-1)
                                                |
                                            @endif
                                        @endforeach
                                    </div>
                                </td>
                                <td>{{ $order->quantity_requested }} </td>
                            </tr>
                        @endforeach


                        </tbody>
                    </table>

                    {{-- Render pagination controls --}}
                    {!! $orders->render() !!}
                </div>
            </div>
        </div>
    </div>
</div>

@stop
