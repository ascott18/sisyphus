@extends('layouts.master', [
    'breadcrumbs' => [
        ['Courses', '/courses'],
        [isset($course) ? 'Edit' : 'Create'],
        isset($course) ? [$course->displayIdentifier()] : null,
    ]
])


@section('content')

    <div class="row"
         ng-controller="CoursesModifyController"
         ng-init="course = {{isset($course) ? $course : '{}' }}">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-university fa-fw"></i> {{$panelTitle}}</h3>
                </div>
                <form name="form" action="" class="panel-body" method="POST" novalidate ng-submit="submit(form, $event)">
                    {!! csrf_field() !!}

                    <div class="col-md-6">
                        @if(isset($term_id))
                            <input type="hidden" name="course[term_id]" value="{{$term_id}}">
                            <label>Term</label>
                            <h4>{{$term_name}}</h4>
                        @endif


                        @include('input.generic', ['name' => 'course.course_name', 'label' => 'Title',
                            'attrs' => ['required' => 'true']])
                        @include('input.generic', ['name' => 'course.department', 'label' => 'Department',
                            'attrs' => ['required' => 'true', 'pattern' => '[A-Z]{2,10}'], 'pattern' => 'must be 2-10 uppercase characters'])
                        @include('input.generic', ['name' => 'course.course_number', 'label' => 'Number',
                            'attrs' => ['required' => 'true', 'pattern' => '\d+'], 'pattern' => 'must be a number'])
                        @include('input.generic', ['name' => 'course.course_section', 'label' => 'Section',
                            'attrs' => ['required' => 'true', 'pattern' => '\d+'], 'pattern' => 'must be a number'])
                    </div>

                    <div class="col-md-6"
                         ng-init="users = {{$users}}">
                        <label>Professor </label>
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Search" ng-model="userSearch" ng-blur="userSearchOnBlur(userSearch)">
                            <span class="input-group-addon">
                                <i class="fa fa-search"></i>
                            </span>
                        </div>

                        <div class="row">
                            <br>
                            <div class="col-md-6">
                                <strong>Currently Selected:</strong>
                                <span ng-cloak ng-if="getSelectedUser()"> [[users[getSelectedUser()].first_name]] [[users[getSelectedUser()].last_name]]</span>
                                <span ng-cloak ng-if="!getSelectedUser()"> Nobody (Professor is TBA) </span>
                            </div>
                            <div ng-cloak class="col-md-6">
                                <a class="btn btn-xs btn-danger" ng-click="course.user_id = null" ng-show="getSelectedUser()">
                                    <i class="fa fa-times"></i> Clear Selection
                                </a>
                            </div>
                        </div>

                        @include('input.generic', ['type' => 'hidden', 'name' => 'course.user_id', 'label' => 'Professor', 'attrs' => []])

                        <div ng-cloak ng-show="userSearch">
                            <div class="list-group">
                                <div class="list-group-item cursor-pointer"
                                     ng-class="{active: course.user_id == user.user_id}"
                                     ng-click="course.user_id = user.user_id"
                                     dir-paginate="user in users | filterSplit: userSearch | orderBy: 'last_name' | itemsPerPage:10">

                                    <span>[[user.last_name]], [[user.first_name]]</span>
                                </div>
                            </div>
                            <dir-pagination-controls></dir-pagination-controls>
                        </div>
                        <h4 class="text-muted" ng-show="!userSearch">Start typing above to search for a professor.</h4>
                    </div>
                    <div class="col-md-offset-6 col-md-6 ">
                        <button type="submit" class="btn btn-success">
                            <i class="fa fa-check"></i> Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@stop

@section('scripts-head')

    <script src="/javascripts/ng/helper.isbnHyphenate.js"></script>
    <script src="/javascripts/ng/pagination/dirPagination.js"></script>
    <script src="/javascripts/ng/app.courses.js"></script>
@stop