@extends('layouts.master', [
    'breadcrumbs' => [
        ['Requests', '/requests'],
        ['All Requests'],
    ]
])


@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-book fa-fw"></i> Orders</h3>
                </div>
                <div class="panel-body">


                    <div ng-controller="OrdersListController as mc" class="table-responsive">
                        <table st-pipe="mc.callServer" st-table="mc.displayed"
                               class="table table-hover"
                               empty-placeholder>
                            <thead>
                            <tr>
                                <th st-sort="section">Section</th>
                                <th st-sort="title">Title</th>
                                <th>Term</th>
                                <th st-sort="created_at">Order Date</th>
                                <th width="1%"></th>
                            </tr>
                            <tr>
                                <th><input type="text" class="form-control" placeholder="Search..." st-search="section"/></th>
                                <th><input type="text" class="form-control" placeholder="Search..." st-search="title"/></th>
                                <th>
                                    <select class="form-control" ng-init="TermSelected = ''" ng-model="TermSelected" ng-change="updateTerm()">
                                        <option value="">All Terms</option>
                                        @foreach($terms as $term)
                                            <option value="{{$term->term_id}}">{{$term->display_name}} </option>
                                        @endforeach
                                    </select>
                                </th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>

                            <tr ng-cloak ng-repeat="order in mc.displayed">
                                <td>[[ order.department ]] [[ order.course_number | zpad:3 ]]-[[ order.course_section | zpad:2 ]]</td>
                                <td>[[ order.title ]]</td>
                                <td>[[ order.term.display_name ]]</td>
                                <td>[[ order.created_at | moment:'ll' ]]</td>
                                <td><a class="btn btn-sm btn-info" href="/courses/details/[[ order.course_id ]]" role="button">
                                        Details <i class="fa fa-arrow-right"></i>
                                    </a>
                                </td>
                            </tr>


                            </tbody>
                            <tfoot>
                            <tr>
                                <td class="text-center" st-pagination="" st-items-by-page="10" colspan="4">
                                </td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@stop

@section('scripts-head')
    <script src="/javascripts/ng/helper.isbnHyphenate.js"></script>
    <script src="/javascripts/ng/app.orders.js"></script>
@stop