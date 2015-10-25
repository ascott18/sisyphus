@extends('layouts.master')




@section('content')
    @include('shared.partial.header', ['headerText'=>'Requests', 'subHeaderText'=>'All Requests'])


    <div class="row">
        <div class="col-lg-12">
            <a href="/orders/create" class="btn btn-success btn-lg">
                Place a request <i class="fa fa-arrow-right fa-fw"></i>
            </a>
            <br/>
            <br/>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-shopping-cart fa-fw"></i>Orders</h3>
                </div>
                <div class="panel-body">


                    <div class="table-responsive">
                        <table class="table table-bordered">

                            <tbody>

                                @foreach ($courses as $course)

                                    <tr bgcolor="DFEDF2">
                                        <td><h4>{{ $course->course_name }}</h4></td>
                                    </tr>
                                    @foreach($course->orders as $order)


                                                        <?php $book = $order->book;
                                                        $author= $book->author;?>
                                                        <tr>
                                                            <td><span style="margin-left:2em"></span>{{$book->isbn13}} {{$book->title}}-{{$order->quantity_requested}} copies </td>

                                    @endforeach
                                @endforeach



                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@stop