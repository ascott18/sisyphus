@extends('layouts.master')


@section('content')

    @include('shared.partial.header', ['headerText'=>'Users', 'subHeaderText'=>'Management'])


    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-book fa-fw"></i> All Books</h3>
                </div>
                <div class="panel-body">


                    {{-- Render pagination controls --}}
                    {!! $users->render() !!}

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-striped">
                            <thead>
                            <tr>
                                <th>Last Name</th>
                                <th>First Name</th>
                                <th>NetID</th>
                                <th>Email</th>
                                <th>Departments</th>
                            </tr>
                            </thead>
                            <tbody>

                            @foreach ($users as $user)
                                <tr>
                                    <td>
                                        {{ $user->last_name }}
                                    </td>
                                    <td>
                                        {{ $user->first_name }}
                                    </td>
                                    <td>
                                        {{ $user->net_id }}
                                    </td>
                                    <td>
                                        {{ $user->email }}
                                    </td>
                                    <td>
                                        <button class="btn btn-xs btn-primary pull-right">
                                            <i class="fa fa-plus"></i> Add
                                        </button>

                                        @foreach($user->sortedDepartments as $department)
                                            <i class="fa fa-times text-danger cursor-pointer"></i>
                                            {{$department['department']}} <br>
                                        @endforeach
                                    </td>
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
