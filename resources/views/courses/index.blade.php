@extends('layouts.master')




@section('content')

    @include('shared.partial.header', ['headerText'=>'Courses', 'subHeaderText'=>'All Courses'])


    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-pencil fa-fw"></i> All Courses</h3>
                </div>
                <div class="panel-body">


                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-striped">
                            <thead>
                            <tr>
                                <th>Section</th>
                                <th>Name</th>
                            </tr>
                            </thead>
                            <tbody>

                            @foreach ($courses as $course)
                                <tr>
                                    <td>
                                        {{ $course->department }} {{ str_pad($course->course_number, 3, "0", STR_PAD_LEFT) }}-{{ $course->course_section }}
                                    </td>
                                    <td>{{ $course->course_name }}</td>

                                </tr>


                            @endforeach


                            </tbody>
                        </table>

                        {{-- Render pagination controls --}}
                        {!! $courses->render() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>

@stop
