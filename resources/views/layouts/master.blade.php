<!DOCTYPE html><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>EWU CS Book Orders</title>
    <!-- Bootstrap Core CSS-->
    <link href="/stylesheets/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS-->
    <link href="/stylesheets/app.css" rel="stylesheet">
    <!-- Custom Fonts-->
    <link href="/stylesheets/font-awesome.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet" type="text/css">

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

                    <h2>Textbook Orders</h2>



                </div>
            </a>
        </div>
        <!-- Top Menu Items-->

        <div class="collapse navbar-collapse navbar-ex1-collapse">

            <ul class="nav navbar-right top-nav">
                <li >
                    <span><i class="fa fa-user"></i> Welcome, {{ session('net_id') }}! </span>
                </li>
            </ul>

            <!-- Sidebar Menu Items -->
            <ul class="nav navbar-nav side-nav">
                <li><a href="/"><i class="fa fa-fw fa-dashboard"></i> Dashboard</a></li>
                <li><a href="/books"><i class="fa fa-fw fa-book"></i> Books</a></li>
                <li><a href="/orders"><i class="fa fa-fw fa-shopping-cart"></i> Orders</a></li>
                <li><a href="/courses"><i class="fa fa-fw fa-pencil"></i> Courses</a></li>
                <li><a href="/messages"><i class="fa fa-fw fa-envelope"></i> Messages</a></li>

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



            @yield('content')

            <!-- /.row-->
            <hr>
            <footer>
                <p>&copy; 2015 - Eastern Washington University</p>
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


@yield('scripts')