<!DOCTYPE html><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>
        @if (isset($breadcrumbs))
            @if (!isset($breadcrumbs[1]))
                {{$breadcrumbs[0][0]}}
            @else
                {{$breadcrumbs[1][0]}} {{isset($breadcrumbs[2]) ? $breadcrumbs[2][0] : ''}}
            @endif
        @endif
         - EWU Textbook Requests
    </title>

    <!-- Bootstrap Core CSS-->
    <link href="/stylesheets/bootstrap.css" rel="stylesheet">
    <!-- Custom CSS-->
    <link href="/stylesheets/app.css" rel="stylesheet">
    <!-- Custom Fonts-->
    <link href="/stylesheets/font-awesome.min.css" rel="stylesheet" type="text/css">

    <script src="/javascripts/angular.min.js"></script>
    <script src="/javascripts/ng/smart-table/smart-table.js"></script>
    <script src="/javascripts/ng/app.js"></script>

    @yield('scripts-head')
</head>
<body ng-app="sisyphus">
<div id="wrapper">
    <i ng-spinner ng-cloak class="fa fa-spinner fa-spin "></i>

    <!-- Navigation-->
    <nav role="navigation" class="navbar navbar-inverse navbar-fixed-top">
        <!-- Brand and toggle get grouped for better mobile display-->
        <div class="navbar-header">
            <button type="button" data-toggle="collapse" data-target=".navbar-ex1-collapse" class="navbar-toggle">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>


            <a href="/" class="navbar-brand">
                <div >
                    <img src="/images/logoWhite.svg" class="pull-left" >

                    <h2>Textbook Requests
                        @inject('auth', 'App\Providers\AuthServiceProvider')
                        @if ($auth->getIsDebuggingUnauthorizedAction())
                            <p style="position: absolute; font-size: 0.7em; width: 100%">
                                <i class="fa fa-bug fa-spin"></i> (debugging unauthorized action) <i class="fa fa-bug fa-spin"></i>
                            </p>
                        @endif
                    </h2>
                </div>
            </a>
        </div>
        <!-- Top Menu Items-->

        <div class="collapse navbar-collapse navbar-ex1-collapse">

            <ul class="nav navbar-right top-nav">
                @if (Auth::user())
                    <li >
                        <span id="userName"><i class="fa fa-user"></i> Welcome, {{ Auth::user()->net_id }}! </span>
                    </li>
                    <li class="pull-right">
                        <span style="margin-left: 25px"><a href="/logout">Logout</a></span>
                    </li>
                @endif
            </ul>

            <!-- Sidebar Menu Items -->
            <ul class="nav navbar-nav side-nav">
                @can('view-dashboard')
                <li><a href="/"><i class="fa fa-fw fa-dashboard"></i> Dashboard</a></li>
                @endcan
                <li><a href="/requests"><i class="fa fa-fw fa-shopping-cart"></i> Requests</a></li>
                <li><a href="/books"><i class="fa fa-fw fa-book"></i> Books</a></li>
                @can('view-course-list')
                    <li><a href="/courses"><i class="fa fa-fw fa-university"></i> Courses</a></li>
                @endcan
                @can('send-messages')
                    <li><a href="/messages"><i class="fa fa-fw fa-envelope"></i> Messages</a></li>
                @endcan
                @can('view-terms')
                    <li><a href="/terms"><i class="fa fa-fw fa-calendar"></i> Terms</a></li>
                @endcan
                @can('manage-users')
                    <li><a href="/users"><i class="fa fa-fw fa-group"></i> Users</a></li>
                @endcan
                <li><a href="/tickets"><i class="fa fa-fw fa-life-ring"></i> Tickets</a></li>
                @can('make-reports')
                <li><a href="/reports"><i class="fa fa-fw fa-bar-chart"></i> Reports</a></li>
                @endcan

                {{--<li><a href="tables.html"><i class="fa fa-fw fa-table"></i> Tables</a></li>--}}
                {{--<li><a href="forms.html"><i class="fa fa-fw fa-edit"></i> Forms</a></li>--}}
                {{--<li><a href="bootstrap-elements.html"><i class="fa fa-fw fa-desktop"></i> Bootstrap Elements</a></li>--}}
                {{--<li><a href="bootstrap-grid.html"><i class="fa fa-fw fa-wrench"></i> Bootstrap Grid</a></li>--}}
                {{--<li>--}}
                    {{--<a href="javascript:;" data-toggle="collapse" data-target="#demo"><i--}}
                                {{--class="fa fa-fw fa-arrows-v"></i> Dropdown <i class="fa fa-fw fa-caret-down"></i></a>--}}
                    {{--<ul id="demo" class="collapse">--}}
                        {{--<li><a href="#">Dropdown Item</a></li>--}}
                        {{--<li><a href="#">Dropdown Item</a></li>--}}
                    {{--</ul>--}}
                {{--</li>--}}
                {{--<li><a href="blank-page.html"><i class="fa fa-fw fa-file"></i> Blank Page</a></li>--}}
                {{--<li><a href="index-rtl.html"><i class="fa fa-fw fa-dashboard"></i> RTL Dashboard</a></li>--}}
            </ul>
        </div>
    </nav>
    <div id="page-wrapper">
        <div class="container-fluid">

            <!-- Page Heading-->


            <div class="row">
                <div class="col-lg-12">
                    <h4 class="page-header text-muted">
                        @if (isset($breadcrumbs))
                        <ul class="slash-list">
                            @foreach($breadcrumbs as $breadcrumb)
                                @if(isset($breadcrumb[1]))
                                    <li><a href="{{$breadcrumb[1]}}">{{$breadcrumb[0]}}</a></li>
                                @elseif ($breadcrumb != null)
                                    <li>{{$breadcrumb[0]}}</li>
                                @endif
                            @endforeach
                            <li ng-cloak ng-repeat="breadcrumb in breadcrumbAppends">[[breadcrumb]]</li>
                        </ul>
                        @endif
                    </h4>
                </div>
            </div>


            @if (isset($errors) && count($errors) > 0)
                <div class="alert alert-danger alert-dismissible" role="alert">
                    <button type="button" class="close" aria-label="Close" ng-click="appErrors.splice(appErrors.indexOf(error), 1)"><span aria-hidden="true">&times;</span></button>
                    <ul>
                    @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div ng-cloak ng-repeat="error in appErrors"
                    class="alert alert-danger alert-dismissible" role="alert">
                <button type="button" class="close" aria-label="Close" ng-click="appErrors.splice(appErrors.indexOf(error), 1)"><span aria-hidden="true">&times;</span></button>
                <strong>[[error.title || "Error!"]]</strong> <span ng-repeat="message in error.messages"><br>[[message]]</span>
            </div>

            @yield('content')

            <hr>
            <footer>
                <p>&copy; {{date("Y")}} - Eastern Washington University</p>
                <p class="text-muted">made with <i class="fa fa-heart" style="color: #8b001d;"></i> by EWU Computer Science students</p>
            </footer>
        </div>
    </div>
</div>
</body>
</html>



<script src="/javascripts/jquery-1.10.2.js"></script>

<script type="text/javascript">
    var url = window.location.href;

    var matched;
    jQuery.fn.reverse = [].reverse;
    $(".nav.side-nav a").reverse().each(function() {
        // iterate in reverse order, otherwise we would always match the dashboard.
        if(!matched && url.indexOf(this.href) >= 0) {
            $(this).closest("li").addClass("active");
            matched = true;
        }
    });
</script>

<script src="/javascripts/bootstrap.js"></script>
<script src="/javascripts/moment.min.js"></script>
<script src="/javascripts/linq.min.js"></script>


@yield('scripts')