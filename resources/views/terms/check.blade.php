<link href="/stylesheets/bootstrap.min.css" rel="stylesheet">
<div class="pull-right"><?php echo date("m/d/Y, h:i:s a");?></div>
<h1>Book Request Check Sheet</h1>

<style>
    td, th{font-family: sans-serif;font-size:10pt;}
     table {
         border-collapse: collapse;
     }
    tr {
        border: solid;
        border-width: 1px 0;
    }
</style>
<style media="print">
    td,th{font-family: sans-serif;font-size:8pt;}
</style>
<div class="row">
    <div class="col-lg-12">

        <div class="text-center"><h2>{{ $term->display_name }}</h2></div>
        <?php  $courses = $term->courses;?>
        <table width="100%" cellpadding="8">

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

                <?php $user = $course->user;?>
                @if(!$course->no_book)
                    @foreach($course->orders as $order)
                        <?php $book = $order->book?>
                        <tr>
                            <td>{{ $course->department }}
                                {{ str_pad($course->course_number, 3, "0", STR_PAD_LEFT) }}-{{ $course->course_section }}
                                {{$course->course_name}}</td>
                            <td>{{$user->last_name}}, {{$user->first_name}}</td>
                            <td>{{$book->title}}</td>
                            <td>
                                <?php $index = 0; ?>
                                @foreach($book->authors as $author)
                                    {{$author->name}}
                                    @if ($index++ != count($book->authors)-1)
                                        |
                                    @endif
                                @endforeach
                            </td>
                            <td></td>
                            <td>{{$book->isbn13}}</td>
                            <td>{{$book->publisher}}</td>
                            <td></td>
                            <td></td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td>{{ $course->department }}
                            {{ str_pad($course->course_number, 3, "0", STR_PAD_LEFT) }}-{{ $course->course_section }}
                            {{$course->course_name}}</td>
                        <td>{{$user->last_name}}, {{$user->first_name}}</td>
                        <td>No Book</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>

                @endif



            @endforeach

            </tbody>
        </table>


    </div>
</div>



