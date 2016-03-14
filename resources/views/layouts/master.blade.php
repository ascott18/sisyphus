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

    <!--[if lte IE 9]>
        <script src="/javascripts/es5-shim.min.js"></script>
        <script src="/javascripts/es5-sham.min.js"></script>
    <![endif]-->
    <!--[if lt IE 9]>
        <script src="//html5shiv.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->

    <script src="/javascripts/angular.min.js"></script>
    <script src="/javascripts/ng/smart-table/smart-table.js"></script>
    <script src="/javascripts/ng/app.js"></script>

    <script src='/javascripts/ng/text/textAngular-rangy.min.js'></script>
    <script src='/javascripts/ng/text/textAngular-sanitize.min.js'></script>
    <script src='/javascripts/ng/text/textAngular.min.js'></script>

    @yield('scripts-head')
</head>
<body ng-app="sisyphus" ng-controller="HelpModalController">
<div id="wrapper">

    {{--Uncomment this. I dare you.--}}

    {{--<div style="height: 0;">--}}
        {{--<button class="btn btn-lg btn-primary"--}}
                {{--style="position: relative; top: -35px; left: 60%; z-index: 100000"--}}
                {{--ng-click="espanol()">--}}
            {{--en espa�ol--}}
        {{--</button>--}}
    {{--</div>--}}

    <i ng-spinner ng-cloak class="fa fa-spinner fa-spin" ng-show="spinnerActive"></i>

    {{--Uncomment this directive for the help modal -- also uncomment the button below to get it to open--}}

    {{--<modal title="Help" visible="showModal">--}}
    {{--</modal>--}}

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
                    <img src="/images/logoWhite.svg" class="pull-left" title="test">

                    <h2>Textbook Requests
                        @inject('auth', 'App\Providers\AuthServiceProvider')
                        @if ($auth->getIsDebuggingUnauthorizedAction())
                            <p style="position: absolute; font-size: 0.6em; width: 100%">
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
                        <span id="userName">
                            <i class="fa fa-user"></i>
                            <a href="/users/edit/{{Auth::user()->user_id}}">
                                Welcome, {{ Auth::user()->net_id }}!
                            </a>
                        </span>
                    </li>
                @endif

                <li class="pull-right">
                    <span style="margin-left: 25px"><a href="/logout">Logout</a></span>
                </li>
            </ul>

            <!-- Sidebar Menu Items -->
            <div class="nav navbar-nav side-nav">
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
                {{--The tab so current user can view tickets created by them or for them--}}

                {{--<li><a href="/tickets"><i class="fa fa-fw fa-life-ring"></i> Tickets</a></li>--}}
                @can('make-reports')
                <li><a href="/reports"><i class="fa fa-fw fa-bar-chart"></i> Reports</a></li>
                @endcan


                {{--Here is the button to get the help modal to pop up--}}

                {{--<div class="icon-wrapper" ng-click="toggleModal()">--}}
                    {{--<i class="fa fa-question custom-icon"></i>--}}
                {{--</div>--}}
            </div>
		</div>
    </nav>


    <div id="page-wrapper">

        <!--[if lt IE 9]>
        <div id="oldIE" class="text-center" style="width: 100%; height: 300px; margin-top: 150px;" >
            <h3>This site does not work on Internet Explorer 8 or older.
                <i class="fa fa-internet-explorer"></i>
                <i class="fa fa-frown-o"></i>
            </h3>
            <br>
            <h2>Please switch to a modern browser like
                <i class="fa fa-firefox"></i> <a href="https://www.firefox.com">Firefox</a>
                or <i class="fa fa-chrome"></i> <a href="https://www.chrome.com">Chrome</a>.</h2>
        </div>
        <style>
            .container-fluid {
                display: none;
            }
        </style>
        <![endif]-->

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
                <strong>[[error.title || "Error!"]]</strong> <span ng-repeat="message in error.messages track by $index"><br>[[message]]</span>
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