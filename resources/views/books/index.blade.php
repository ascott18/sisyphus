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


                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-striped">
                        <thead>
                        <tr>
                            <th>Title</th>
                            <th>Publisher</th>
                            <th>ISBN</th>
                            <th>Amazon</th>
                        </tr>
                        </thead>
                        <tbody>

                        @foreach ($books as $book)
                            <tr>
                                <td><div>
                                        {{ $book->title }}
                                    </div>
                                    <div class="text-muted">
                                        <?php $index = 0; ?>
                                        @foreach($book->authors as $author)
                                            {{$author->last_name}}, {{$author->first_name}}
                                            @if ($index++ != count($book->authors)-1)
                                                |
                                            @endif
                                        @endforeach
                                    </div>
                                </td>
                                <td>{{ $book->publisher }}</td>
                                <td>{{ $book->isbn13 }}</td>
                                <td><a href="http://www.amazon.com/dp/{{ $book->asin }}" class="price-link"><i
                                                class="fa fa-amazon"></i><span> Amazon! </span></a></td>
                            </tr>


                        @endforeach


                        </tbody>
                    </table>

                    {{-- Render pagination controls --}}
                    {!! $books->render() !!}
                </div>
            </div>
        </div>
    </div>
</div>

@stop
