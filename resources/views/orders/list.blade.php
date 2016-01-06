@extends('layouts.master')

@section('area', 'Requests')
@section('page', 'All Requests')

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
                                <th st-sort="title">Title</th>
                                <th>Term</th>
                                <th st-sort="section">Section</th>
                                <th st-sort="created_at">Order Date</th>
                                <th width="1%"></th>
                            </tr>
                            <tr>
                                <th><input type="text" class="form-control" placeholder="Search..." st-search="title"/></th>
                                <th>
                                    <select class="form-control" ng-init="TermSelected = '{{$currentTermId}}'" ng-model="TermSelected" ng-change="updateTerm()">
                                        <option value="">All Terms</option>
                                        @foreach($terms as $term)
                                            <option value="{{$term->term_id}}">{{$term->termName()}} {{$term->year}}</option>
                                        @endforeach
                                    </select>
                                </th>
                                <th><input type="text" class="form-control" placeholder="Search..." st-search="section"/></th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>

                            <tr ng-cloak ng-repeat="order in mc.displayed">
                                <td>[[ order.title ]]</td>
                                <td>[[ order.term_name ]]</td>
                                <td>[[ order.department ]] [[ order.course_number | zpad:3 ]]-[[ order.course_section | zpad:2 ]]</td>
                                <td>[[ order.created_at ]]</td>
                                <td>
                                    <form action="[[ '/orders/undelete/' + order.order_id ]]" method="POST" name="form" ng-show="order.deleted_at != null">
                                        {!! csrf_field() !!}
                                        <button type="button" class="btn btn-sm btn-default" role="button" ng-confirm-click="submit" >
                                            <i class="fa fa-history"></i> Restore Order
                                        </button>
                                    </form>
                                    <form action="[[ '/orders/delete/' + order.order_id ]]" method="POST" name="form" ng-show="order.deleted_at == null">
                                        {!! csrf_field() !!}
                                        <button type="button" class="btn btn-sm btn-danger" role="button" ng-confirm-click="submit" >
                                            <i class="fa fa-times"></i> Delete Order
                                        </button>
                                    </form>
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