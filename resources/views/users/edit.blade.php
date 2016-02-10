@extends('layouts.master', [
    'breadcrumbs' => [
        ['Users', '/users'],
        [isset($user) ? 'Edit' : 'Create'],
        isset($user) ? [$user->first_last_name] : null,
    ]
])


@section('content')

    <div class="row"
         ng-controller="UsersModifyController"
         ng-init="user = {{isset($user) ? $user : '{}' }}">
        <div class="col-lg-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-university fa-fw"></i> {{$panelTitle}}</h3>
                </div>
                <form name="form" action="" class="panel-body" method="POST" novalidate ng-submit="submit(form, $event)">
                    {!! csrf_field() !!}

                    @if(isset($user))
                        @include('input.generic', ['name' => 'user.user_id', 'label' => 'User Id',
                            'type' => 'hidden', 'attrs' => ['required' => 'true']])
                        <label for="">NetID</label>
                        <h4>{{$user->net_id ? $user->net_id : "No NetID"}}</h4>
                        <br>
                    @else
                        @include('input.generic', ['name' => 'user.net_id', 'label' => 'NetID',
                            'attrs' => ['required' => 'true']])
                    @endif

                    @include('input.generic', ['name' => 'user.first_name', 'label' => 'First Name',
                        'attrs' => ['required' => 'true']])

                    @include('input.generic', ['name' => 'user.last_name', 'label' => 'Last Name',
                        'attrs' => ['required' => 'true']])

                    @include('input.generic', ['name' => 'user.email', 'label' => 'Email', 'type' => 'email',
                        'attrs' => ['required' => 'true']])

                    <button type="submit" class="btn btn-success pull-right">
                        <i class="fa fa-check"></i> Save
                    </button>
                </form>
            </div>
        </div>
    </div>

@stop

@section('scripts-head')
    <script src="/javascripts/ng/app.users.js"></script>
@stop