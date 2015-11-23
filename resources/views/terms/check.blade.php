<link href="/stylesheets/font-awesome.min.css" rel="stylesheet" type="text/css">
<link href="/stylesheets/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet" type="text/css">

<h2>Book Order Check Sheet</h2>
<?php echo date("d/m/Y, h:i:s a" );?>
<div class="row">
    <div class="col-lg-12">

        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-pencil fa-fw"></i>{{ $term->termName() }} {{ $term->year }}</h3>
            </div>
            <div class="panel-body">
                <?php  $courses = $term->courses;?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-striped">
                        <thead>
                        <tr>
                            <th>Course</th>
                            <th>Instructor</th>
                            <th>Book</th>
                            <th>Author</th>
                            <th>Ed</th>
                            <th>ISBN</th>
                            <th>Publisher</th>
                            <th>Req</th>
                            <th>Amt Ord</th>
                        </tr>
                        </thead>
                        <tbody>

                        @foreach ($courses as $course)

                                <?php $user=$course->user;?>
                                @if(!$course->no_book)
                                    @foreach($course->orders as $order)
                                        <?php $book=$order->book?>
                                        <tr>
                                            <td>{{ $course->department }} {{ str_pad($course->course_number, 3, "0", STR_PAD_LEFT) }}-{{ $course->course_section }} {{$course->course_name}}</td>
                                            <td>{{$user->last_name}}, {{$user->first_name}}</td>
                                            <td>{{$book->title}}</td>
                                            <td>
                                            <?php $index = 0; ?>
                                            @foreach($book->authors as $author)
                                                {{$author->last_name}}, {{$author->first_name}}
                                                @if ($index++ != count($book->authors)-1)
                                                    |
                                                @endif
                                            @endforeach
                                            </td>
                                            <td></td>
                                            <td>{{$book->isbn13}}</td>
                                            <td>{{$book->publisher}}</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td>{{ $course->department }} {{ str_pad($course->course_number, 3, "0", STR_PAD_LEFT) }}-{{ $course->course_section }} {{$course->course_name}}</td>

                                    </tr>

                                @endif



                        @endforeach

                        </tbody>
                    </table>


            </div>
        </div>
    </div>
</div>



