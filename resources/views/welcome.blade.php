@extends('layouts.master')


@section('content')

        <!-- Page Heading-->
<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header">Dashboard
            <small>Statistics Overview</small>
        </h1>
        <ol class="breadcrumb">
            <li class="active"><i class="fa fa-dashboard"></i> Dashboard</li>
        </ol>
    </div>
</div>
<!-- /.row--><!--.row.col-lg-12
						.alert.alert-info.alert-dismissable
						    button.close(type='button', data-dismiss='alert', aria-hidden='true') ×
						    i.fa.fa-info-circle
						    strong Like SB Admin?
						    |  Try out
						    a.alert-link(href='http://startbootstrap.com/template-overviews/sb-admin-2') SB Admin 2
						    |  for additional features!--><!-- /.row-->
<div class="row">
    <div class="col-lg-3 col-md-6">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3"><i class="fa fa-comments fa-5x"></i></div>
                    <div class="col-xs-9 text-right">
                        <div class="huge">7</div>
                        <div>New Orders!</div>
                    </div>
                </div>
            </div>
            <a href="#">
                <div class="panel-footer">
                    <span class="pull-left">View Details</span><span class="pull-right"><i
                                class="fa fa-arrow-circle-right"></i></span>

                    <div class="clearfix"></div>
                </div>
            </a>
        </div>
    </div>


    <div class="col-lg-3 col-md-6">
        <div class="panel panel-yellow">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3"><i class="fa fa-tasks fa-5x"></i></div>
                    <div class="col-xs-9 text-right">
                        <div class="huge">12</div>
                        <div>Pending Orders!</div>
                    </div>
                </div>
            </div>
            <a href="#">
                <div class="panel-footer">
                    <span class="pull-left">View Details</span><span class="pull-right"><i
                                class="fa fa-arrow-circle-right"></i></span>

                    <div class="clearfix"></div>
                </div>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="panel panel-green">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3"><i class="fa fa-shopping-cart fa-5x"></i></div>
                    <div class="col-xs-9 text-right">
                        <div class="huge">124</div>
                        <div>Completed Orders!</div>
                    </div>
                </div>
            </div>
            <a href="#">
                <div class="panel-footer">
                    <span class="pull-left">View Details</span><span class="pull-right"><i
                                class="fa fa-arrow-circle-right"></i></span>

                    <div class="clearfix"></div>
                </div>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="panel panel-red">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3"><i class="fa fa-support fa-5x"></i></div>
                    <div class="col-xs-9 text-right">
                        <div class="huge">13</div>
                        <div>Support Tickets!</div>
                    </div>
                </div>
            </div>
            <a href="#">
                <div class="panel-footer">
                    <span class="pull-left">View Details</span><span class="pull-right"><i
                                class="fa fa-arrow-circle-right"></i></span>

                    <div class="clearfix"></div>
                </div>
            </a>
        </div>
    </div>
</div>
<!-- /.row--><!-- Morris Charts CSS-->
<link href="/stylesheets/plugins/morris.css" rel="stylesheet">
<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-money fa-fw"></i> Current Orders</h3>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-striped">
                        <thead>
                        <tr>
                            <th>Requester</th>
                            <th>Order Date</th>
                            <th>Book</th>
                            <th>Quantitiy</th>
                            <th>Price</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>Tom Capaul</td>
                            <td>9/15/2015 4:29 PM</td>
                            <td>Head First Design Patterns, 1st edition</td>
                            <td>28</td>
                            <td><a href="http://www.amazon.com/dp/0596007124" class="price-link"><i
                                            class="fa fa-amazon"></i><span> - $0.00</span></a></td>
                        </tr>
                        <tr>
                            <td>Stu Steiner</td>
                            <td>9/15/2015 4:29 PM</td>
                            <td>Operating System Concepts, 8th edition</td>
                            <td>34</td>
                            <td><a href="http://www.amazon.com/dp/0470128720" class="price-link"><i
                                            class="fa fa-amazon"></i><span> - $0.00</span></a></td>
                        </tr>
                        <tr>
                            <td>Chris Peters</td>
                            <td>9/15/2015 4:29 PM</td>
                            <td>Java Foundations: Introduction to Program Design and Data Structures, 2nd
                                edition
                            </td>
                            <td>31</td>
                            <td><a href="http://www.amazon.com/dp/0132128810" class="price-link"><i
                                            class="fa fa-amazon"></i><span> - $0.00</span></a></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <div class="text-right"><a href="#">View All Orders <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-clock-o fa-fw"></i> Tasks Panel</h3>
            </div>
            <div class="panel-body">
                <div class="list-group"><a href="#" class="list-group-item"><span
                                class="badge">just now</span><i class="fa fa-fw fa-calendar"></i> Calendar
                        updated</a><a href="#" class="list-group-item"><span
                                class="badge">4 minutes ago</span><i class="fa fa-fw fa-comment"></i>
                        Commented on a post</a><a href="#" class="list-group-item"><span
                                class="badge">23 minutes ago</span><i
                                class="fa fa-fw fa-truck"></i> Order 392 shipped</a><a href="#"
                                                                                       class="list-group-item"><span
                                class="badge">46 minutes ago</span><i class="fa fa-fw fa-money"></i> Invoice
                        653 has been paid</a><a href="#" class="list-group-item"><span class="badge">1 hour ago</span><i
                                class="fa fa-fw fa-user"></i> A new user has been added</a><a href="#"
                                                                                              class="list-group-item"><span
                                class="badge">2 hours ago</span><i class="fa fa-fw fa-check"></i> Completed
                        task: "pick up dry cleaning"</a><a href="#" class="list-group-item"><span
                                class="badge">yesterday</span><i class="fa fa-fw fa-globe"></i> Saved the
                        world</a><a href="#" class="list-group-item"><span class="badge">two days ago</span><i
                                class="fa fa-fw fa-check"></i> Completed task: "fix error on sales page"</a>
                </div>
                <div class="text-right"><a href="#">View All Activity <i
                                class="fa fa-arrow-circle-right"></i></a></div>
            </div>
        </div>
    </div>
</div>

@stop
