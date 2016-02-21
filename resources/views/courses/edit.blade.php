@extends('layouts.master', [
    'breadcrumbs' => [
        ['Courses', '/courses'],
        [isset($course) ? 'Edit' : 'Create'],
        isset($course) ? [$course->listings[0]->displayIdentifier()] : null,
    ]
])



@section('content')
    <div class="row"
         ng-controller="CoursesModifyController"
         ng-init="course = {{isset($course) ? $course : '{listings: [{}]}' }}">
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

                        <div class="form-group">
                            <label>
                                Title
                            </label>
                            <input class="form-control"
                                   name="course[listings][0][name]"
                                   placeholder="e.g. Introduction to Biology"
                                   ng-model="course.listings[0].name"
                                   required>

                            <div ng-cloak ng-show="form.$submitted || form['course[listings][0][name]'].$touched">
                                <span class="text-danger" ng-show="form['course[listings][0][name]'].$error.required">
                                    Title is required.
                                </span>
                            </div>
                        </div>


                        <div ng-repeat="listing in course.listings" class="row">
                            <div>
                                <div class="col-xs-4 form-group">
                                    <label ng-show="$first">
                                        Subject
                                    </label>
                                    <input class="form-control uppercase"
                                           {{-- We concat on the first bracket here because we cant have "[[[" since "[" is our angular character.--}}
                                           name="course[listings][['['+$index]]][department]"
                                           placeholder="e.g. BIOL"
                                           ng-model="listing.department"
                                           required
                                           pattern="[a-zA-Z]{2,10}" >

                                    <span ng-cloak ng-show="form.$submitted || form[makeFormKey($index, 'department')].$touched">
                                        <span class="text-danger" ng-show="form[makeFormKey($index, 'department')].$error.required">
                                            Subject is required.
                                        </span>
                                        <span class="text-danger" ng-show="form[makeFormKey($index, 'department')].$error.pattern">
                                            Subject must be 2-10 letters.
                                        </span>
                                    </span>
                                </div>


                                <div class="col-xs-4 form-group">
                                    <label ng-show="$first">
                                        Number
                                    </label>
                                    <input class="form-control"
                                            {{-- We concat on the first bracket here because we cant have "[[[" since "[" is our angular character.--}}
                                           name="course[listings][['['+$index]]][number]"
                                           placeholder="e.g. 100"
                                           ng-model="listing.number"
                                           required
                                           pattern="[0-9]{1,10}" >

                                    <span ng-cloak ng-show="form.$submitted || form[makeFormKey($index, 'number')].$touched">
                                        <span class="text-danger" ng-show="form[makeFormKey($index, 'number')].$error.required">
                                            Number is required.
                                        </span>
                                        <span class="text-danger" ng-show="form[makeFormKey($index, 'number')].$error.pattern">
                                            Number must be ... a number.
                                        </span>
                                    </span>
                                </div>


                                <div class="col-xs-4">
                                    <label ng-show="$first">
                                        Section
                                    </label>
                                    <div style="display: table;">

                                        <input class="form-control"
                                               style="display: table-cell;"
                                                {{-- We concat on the first bracket here because we cant have "[[[" since "[" is our angular character.--}}
                                               name="course[listings][['['+$index]]][section]"
                                               placeholder="e.g. 2"
                                               ng-model="listing.section"
                                               required
                                               pattern="[0-9]{1,10}" >

                                        <span ng-cloak style="display: table-cell;">
                                            <a class="btn btn-danger"
                                               style="margin-left: 10px"
                                               ng-show="!$first"
                                               ng-click="deleteListing(listing)">
                                                <i class="fa fa-times"></i>
                                            </a>
                                        </span>
                                    </div>

                                    <span ng-cloak ng-show="form.$submitted || form[makeFormKey($index, 'section')].$touched">
                                        <span class="text-danger" ng-show="form[makeFormKey($index, 'section')].$error.required">
                                            Section is required.
                                        </span>
                                        <span class="text-danger" ng-show="form[makeFormKey($index, 'section')].$error.pattern">
                                            Section must be a number.
                                        </span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <a class="btn btn-primary" ng-click="addListing()">
                            <i class="fa fa-plus"></i>
                            <span ng-cloak ng-show="course.listings.length > 1">Add Another Cross-Listing</span>
                            <span ng-show="course.listings.length == 1">Add a Cross-Listing</span>
                        </a>
                    </div>

                    <div class="col-md-6 col-md-vspace"
                         ng-init="users = {{$users}}">

                        <div class="row">
                            <div class="col-lg-6">
                                <label>Professor </label>
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Search for a professor" ng-model="userSearch" ng-blur="userSearchOnBlur(userSearch)">
                                    <span class="input-group-addon">
                                        <i class="fa fa-search"></i>
                                    </span>
                                </div>
                            </div>
                            <div ng-cloak class="col-lg-6 col-lg-vspace">

                                <p >
                                    <strong >Selected Professor:</strong>
                                    <a class="btn btn-xs btn-danger" style="margin-left: 15px;" ng-click="course.user_id = null" ng-show="getSelectedUser()">
                                        <i class="fa fa-times"></i> Clear Selection
                                    </a>
                                </p>

                                <h4 ng-cloak ng-if="getSelectedUser()"> [[users[getSelectedUser()].first_name]] [[users[getSelectedUser()].last_name]]</h4>
                                <h4 ng-cloak ng-if="!getSelectedUser()"> Nobody (Professor is TBA) </h4>


                            </div>
                        </div>

                        <input type="hidden"
                               name="course[user_id]"
                               ng-value="course.user_id">

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
                    </div>
                    <div class="col-md-offset-6 col-md-6 col-md-vspace ">
                        <button type="submit" class="btn btn-success">
                            <i class="fa fa-check"></i> Save Course
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