@extends('layouts.master', [
    'breadcrumbs' => [
        ['Requests', '/requests'],
        [isset($course) ? 'Place Request' : 'My Courses'],
    ]
])


@section('content')

<div>
    <div ng-cloak class="row" ng-controller="OrdersController"
            ng-init="
                terms = {{$openTerms}};
                current_user_id = {{$current_user_id}};
                courses = {{$courses}};
                @if (isset($course))
                    placeRequestForCourse((courses | filter:{'course_id': {{$course->course_id}} })[0]);
                @endif
                @if (isset($book))
                    addPassedBookToCart( {{ json_encode($book) }} );
                @endif


            ">

        <div class="col-lg-12" ng-show="getStage() == STAGE_SELECT_COURSE">
            <div class="col-lg-offset-1 col-lg-10"
                 ng-show="courses.length == 0">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-university fa-fw"></i>
                            <span ng-if="terms.length == 0">No Terms Open</span>
                            <span ng-if="terms.length > 0">No Courses Available</span>
                        </h3>
                    </div>
                    <div class="panel-body">
                        <h3 ng-hide="courses.length" class="text-muted">

                            <span ng-if="terms.length == 0">
                                No terms are open for ordering.
                            </span>
                            <span ng-if="terms.length > 0">
                                No courses found for the following terms open for ordering:
                                <br>
                                <br>
                                <ul>
                                    <li ng-repeat="term in terms">[[term.term_name]] [[ term.year ]]</li>
                                </ul>
                            </span>
                        </h3>
                    </div>
                </div>
            </div>

            <div class="col-lg-offset-1 col-lg-10"
                 ng-show="courses.length > 0">
                <div class="panel panel-default"
                     ng-repeat="course in courses"
                     ng-init="term = (terms | filter:{term_id: course.term_id})[0]">
                    <div class="panel-heading">
                        <h3 class="panel-title clearfix">
                            <span class="text-muted pull-right">
                                [[ term.term_name ]] [[ term.year]]
                            </span>

                            <course-with-listings course="course">
                                [[course.listings[0].name]]
                                <span class="text-muted" ng-if="course.user_id != current_user_id"> &mdash; [[course.user.last_name || 'TBA']]</span>
                            </course-with-listings>

                        </h3>
                    </div>
                    <div class="panel-body ">
                        <div class="pull-left">
                            <div style="margin-left: 0em">
                                <span ng-show="courseNeedsOrders(course)" >
                                    <span class="fa-stack fa-fw ">
                                        <i class="fa fa-question fa-2x text-primary"></i>
                                    </span>
                                    No response submitted. Please let us know what you need!
                                </span>
                                <span ng-show="course.no_book" class="text-muted">
                                    <span class="fa-stack" style="color: #d8d8d8">
                                        <i class="fa fa-book fa-stack-1x"></i>
                                        <i class="fa fa-ban fa-stack-2x "></i>
                                    </span>
                                    No books needed. Thank you for letting us know!
                                </span>
                            </div>

                            <ul style="list-style-type: none; padding-left: 0px">
                                <li ng-repeat="order in course.orders"
                                    style="display: flex"
                                    ng-style="{'padding-bottom': $last ? '0' : '10px'}">
                                    <div style="padding-right: 5px">
                                        <i class="fa fa-trash-o fa-fw fa-1-5x text-muted-hover-danger cursor-pointer"
                                           title="Delete Request"
                                           ng-confirm-click="deleteOrder(course, order)"
                                           ng-confirm-click-message="Are you sure you want to delete the request for [[order.book.title]]?"></i>
                                    </div>
                                    <div >
                                        <a href="/books/details/[[order.book.book_id]]" style="color: #000;">
                                            <strong>[[order.book.title]]</strong><span ng-show="order.book.edition.trim()">, [[order.book.edition]]</span>
                                        </a>
                                        <span class="text-muted" ng-show="order.book.isbn13"> &mdash; [[order.book.isbn13 | isbnHyphenate]] </span>
                                        <div class="text-muted">
                                            Required: [[order.required ? 'Yes' : 'No']] <span ng-if="order.notes"> &mdash; Notes: [[order.notes]]</span>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="pull-right">
                            <a ng-click="placeRequestForCourse(course)"
                               class="btn "
                               ng-class="!courseNeedsOrders(course) ? 'btn-default' : 'btn-primary'">
                                Place a request <i class="fa fa-arrow-right fa-fw"></i>
                            </a>
                            <span >
                                <button
                                        ng-confirm-click="noBook(course)"
                                        ng-confirm-click-message="Are you sure you don't want a book? [[course.orders.length ? '\n\nAll requests on this course will be deleted!' : '']]"
                                        class="btn"
                                        ng-disabled="course.no_book"
                                        style="margin-left: 10px"
                                        ng-class="!courseNeedsOrders(course) ? 'btn-default' : 'btn-danger'">
                                    <i class="fa fa-times"></i> No book needed</button>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div ng-show="getStage() == STAGE_SELECT_BOOKS" >

            <div class="col-md-6">

                <!-- Nav tabs -->
                <ul class="nav nav-tabs h3" role="tablist" style="font-size: 20px;">

                    <li role="presentation" class="active">
                        <a href="#newbook" aria-controls="newbook" role="tab" data-toggle="tab">
                            <i class="fa fa-star"></i> Enter a New Book
                        </a>
                    </li>
                    <li role="presentation">
                        <a href="#pastbooks" aria-controls="pastbooks" role="tab" data-toggle="tab">
                            <i class="fa fa-history"></i> Select a Past Book
                        </a>
                    </li>
                </ul>

                <div class="panel panel-default">
                    <!-- Tab panes -->
                    <div class="tab-content">
                        <div role="tabpanel" class="panel-body tab-pane active" id="newbook">

                            <form novalidate class="simple-form" name="form" ng-controller="NewBookController">
                                <div class="form-group" ng-class="{'has-error': (submitted || form.isbn13.$touched) && (form.isbn13.$error.isbn13 || form.isbn13.$error.required)}">
                                    <label for="isbn13">ISBN 13</label>
                                    <input
                                            ng-change="isbnChanged(book)"
                                            type="text"
                                            class="form-control"
                                            name="isbn13"
                                            placeholder="ISBN 13 (dashes optional)"
                                            ng-model="book.isbn13"
                                            required isbn13>

                                    <div ng-show="submitted || form.isbn13.$touched">
                                        {{--<div ng-show="form.isbn13.$error.required"><p class="text-danger">Required</p></div>--}}
                                        <div ng-show="form.isbn13.$error.isbn13"><p class="text-danger">Invalid ISBN</p></div>
                                    </div>
                                </div>

                                <div ng-show="!isAutoFilled">
                                    <div class="form-group" ng-class="{'has-error': (submitted || form.bookTitle.$touched) && form.bookTitle.$error.required}">
                                        <label for="title">Book Title</label>
                                        <input
                                                type="text"
                                                ng-disabled="isAutoFilled"
                                                class="form-control"
                                                name="bookTitle"
                                                placeholder="Book Title"
                                                ng-model="book.title"
                                                required="">

                                        <div ng-show="submitted || form.bookTitle.$touched">
                                            <div ng-show="form.bookTitle.$error.required"><p class="text-danger">Required</p></div>
                                        </div>
                                    </div>

                                    <div class="form-group clearfix" ng-class="{'has-error': (submitted || form.author1.$touched) && form.author1.$error.required}">
                                        <label for="author1">Author</label>
                                        <input
                                                type="text"
                                                ng-disabled="isAutoFilled"
                                                class="form-control"
                                                name="author1"
                                                placeholder="Author"
                                                ng-model="authors[0].name"
                                                required="">

                                        <div ng-show="submitted || form.author1.$touched">
                                            <div ng-show="form.author1.$error.required"><p class="text-danger">Required</p></div>
                                        </div>


                                        <div class="input-group" ng-repeat="author in authors" style="margin-top: 10px"  ng-show="!$first">
                                            <input
                                                    type="text"
                                                    ng-disabled="isAutoFilled"
                                                    class="form-control"
                                                    placeholder="Author"
                                                    ng-model="author.name">
                                            <span class="input-group-addon cursor-pointer" ng-click="removeAuthor($index)">
                                                <i class="fa fa-times"></i>
                                            </span>
                                        </div>

                                        <div class="pull-right" style="margin-top: 10px;margin-bottom: 10px">
                                            <button class="btn btn-info"
                                                    ng-click="addAuthor()"
                                                    ng-disabled="isAutoFilled"><i class="fa fa-plus"></i> Add Additional Author</button>
                                        </div>
                                    </div>

                                    <div class="form-group" ng-class="{'has-error': (submitted || form.publisher.$touched) && form.publisher.$error.required}">
                                        <label for="publisher">Publisher</label>
                                        <input
                                                type="text"
                                                ng-disabled="isAutoFilled"
                                                class="form-control"
                                                name="publisher"
                                                placeholder="Publisher"
                                                ng-model="book.publisher"
                                                required="">

                                        <div ng-show="submitted || form.publisher.$touched">
                                            <div ng-show="form.publisher.$error.required"><p class="text-danger">Required</p></div>
                                        </div>
                                    </div>



                                    <div class="form-group">
                                        <label for="edition">Edition</label>
                                        <input
                                                type="text"
                                                ng-disabled="isAutoFilled"
                                                class="form-control"
                                                name="edition"
                                                placeholder="Edition"
                                                ng-model="book.edition">
                                    </div>


                                </div>

                                <div ng-show="isAutoFilled">
                                    <h3 class="text-muted">We already have data for that ISBN:</h3>

                                    <book-details book="autofilledBook">
                                </div>



                                <button type="button" class="btn btn-primary pull-right"
                                        ng-click="addNewBookToCart(book,form)"
                                        ng-disabled="form.$invalid">
                                    <i class="fa fa-plus"></i> Add to Cart
                                </button>

                                <button type="button" class="btn btn-primary pull-right"
                                        style="margin-right: 10px"
                                        ng-click="addNewBookToCart(book, form, true)"
                                        ng-show="!form.isbn13.$viewValue && book.title && book.publisher && authors[0].name">
                                    <i class="fa fa-plus"></i> Add without ISBN
                                </button>

                            </form>
                        </div>

                        <div role="tabpanel" class="panel-body panel-list tab-pane" id="pastbooks">


                            <h3 class="text-muted"
                                    ng-show="!selectedCourse.pastBooks">
                                Loading past books...
                            </h3>

                            <h3 class="text-muted"
                                    ng-show="selectedCourse.pastBooks.length == 0">
                                There are no known past books for this course.
                            </h3>

                                <div class="panel-list-item"
                                    ng-cloak
                                    ng-show="selectedCourse.pastBooks.length > 0"
                                    ng-repeat="bookData in selectedCourse.pastBooks | orderBy:'-terms[0].term.term_id' track by bookData.book.book_id">
                                    <div class="pull-right">
                                        <button class="btn btn-xs btn-primary"
                                                title="Add to Cart"
                                                ng-click="addBookToCart(bookData)">
                                            <i class="fa fa-fw fa-plus"></i>
                                        </button>
                                    </div>

                                    <book-details book="bookData.book">
                                        <span ng-repeat="termData in bookData.terms">
                                            <br>
                                            [[termData.term.term_name]] [[termData.term.year]]:
                                            <span ng-repeat="data in termData.orderData"
                                                  ng-class="{'text-primary': data.course.user.user_id == selectedCourse.user_id}">

                                                [[data.course.user.last_name || 'TBA']]

                                                (<ng-pluralize count="data.numSections" when="{
                                                    'one': '{} Section',
                                                    'other': '{} Sections'}">
                                                </ng-pluralize>)
                                                [[$last ? '' : ($index==book.authors.length-2) ? ', and ' : ', ']]
                                            </span>
                                        </span>
                                    </book-details>
                                </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">

                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-shopping-cart fa-fw"></i> Cart</h3>
                    </div>
                    <div class="panel-body panel-list">

                        <h3 class="text-muted" ng-show="cartBooks.length == 0">
                            There are no books in the cart.
                        </h3>

                        <div class="panel-list-item"
                            ng-cloak
                            ng-repeat="bookData in cartBooks">

                            <div class="pull-right">
                                <button class="btn btn-xs btn-danger"
                                        ng-click="deleteBookFromCart(bookData)">
                                    <i class="fa fa-fw fa-times"></i>
                                </button>
                            </div>

                            <book-details book="bookData.book"></book-details>

                        </div>
                    </div>
                </div>

                <button class="btn btn-success pull-right"
                        ng-disabled="cartBooks.length == 0"
                        ng-click="setStage(3)">
                    Review Request <i class="fa fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <div ng-show="getStage() == STAGE_REVIEW_ORDERS">

            <form novalidate class="simple-form" name="form" >

                <div class="col-md-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title"><i class="fa fa-shopping-cart fa-fw"></i> Cart</h3>
                        </div>
                        <div class="panel-body panel-list">
                            <div class="panel-list-item clearfix"
                                 ng-repeat="bookData in cartBooks">
                                <div class="col-md-4">
                                    <book-details book="bookData.book"></book-details>
                                </div>

                                <div class="col-md-4 col-md-vspace">
                                    <label for="notes[[$index]]">Notes</label>
                                    <input type="text"
                                           ng-model="bookData.notes"
                                           id="notes[[$index]]"
                                           class="form-control"
                                           placeholder="e.g. expected enrollment: 23">
                                </div>
                                <div class="col-md-4 col-md-vspace">
                                    <div class="form-group" ng-class="{'has-error': submitted && form.req[[$index]].$error.required}">
                                        <label for="required">Required for Course?</label>
                                        <br>

                                        <label class="radio-inline"><input
                                                    type="radio"
                                                    ng-model="bookData.required"
                                                    name="req[[$index]]"
                                                    ng-value="true"
                                                    required=""/> Yes
                                        </label>


                                        <label class="radio-inline"><input
                                                    type="radio"
                                                    ng-model="bookData.required"
                                                    name="req[[$index]]"
                                                    ng-value="false"
                                                    required=""/> No
                                        </label>

                                        <div ng-show="submitted && form.req[[$index]].$error.required">
                                            <p class="text-danger">
                                                Please let us know if this book is required!
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="col-lg-offset-6 col-lg-6"
                     ng-if="(courses | filter:isCourseSimilarToSelected).length>0">
                    <div class="panel panel-default">

                        <div class="panel-heading">
                            <h3 class="panel-title"><i class="fa fa-university fa-fw"></i> Similar Courses</h3>
                        </div>


                        <div class="panel-body panel-list">

                            <h5 class="text-muted" style="margin-top: 0; margin-bottom: 30px;">
                                Select any additional courses that you would like to place this request for.
                            </h5>

                            <div class="panel-list-item active">
                                <span style="left: 50%; position: absolute">[[selectedCourse.user.last_first_name || 'TBA']]</span>

                                <course-with-listings course="selectedCourse"></course-with-listings>
                            </div>

                            <div class="panel-list-item cursor-pointer"
                                 ng-class="{active: isAdditionalCourseSelected(course)}"
                                 ng-click="toggleAdditionalCourseSelected(course)"
                                 ng-repeat="course in courses | filter:isCourseSimilarToSelected ">

                                <span style="left: 50%; position: absolute">[[course.user.last_first_name || 'TBA']]</span>

                                <course-with-listings course="course"></course-with-listings>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="pull-right">
                        <button class="btn btn-primary"
                                ng-click="setStage(2)"
                                style="margin-right: 15px;">
                            <i class="fa fa-arrow-left"></i> Make Revisions
                        </button>

                        <button class="btn btn-success"
                                ng-click="submitOrders(form)">
                            <i class="fa fa-check"></i>
                            Submit
                            <ng-pluralize count="getNumAdditionalCoursesSelected() + 1"
                                          when="{0: 'Request',
                                                 'one': 'Request',
                                                 'other': '{} Requests'}">
                            </ng-pluralize>
                        </button>

                        <div ng-show="submitted && !form.$valid">
                            <br>
                            <p class="text-danger">
                                Review the errors above before submitting.
                            </p>
                        </div>
                    </div>
                </div>

            </form>
        </div>

        <div class="col-lg-12" ng-show="getStage() == STAGE_ORDER_SUCCESS">
            <div class="panel panel-default">
                <div class="panel-body">
                    <div class="text-center">
                        <h1 ng-show="orderWasACompleteSuccess()">Request successfully placed. Thank you!</h1>
                        <div ng-show="!orderWasACompleteSuccess()">
                            <div ng-show="orderWasACompleteFailure()">
                                <h1 style="padding-top: 0">No requests were placed.</h1>
                                <h4>
                                    All courses already have different requests for these books.
                                </h4>
                            </div>
                            <div ng-show="!orderWasACompleteFailure()">
                                <h1 style="padding-top: 0">Request partially successful.</h1>
                                <h4>
                                    Some courses already have different requests for these books.
                                </h4>
                            </div>
                        </div>
                    </div>
                    <br>
                    <br>
                    <div class="panel-list panel-list-border-last">
                        <div class="row panel-list-item" ng-repeat="bookData in orderResults">
                            <div class="col-md-6">
                                <book-details book="bookData[0].order.book">
                                </book-details>
                            </div>
                            <div class="col-md-6 col-md-vspace">
                                <ul style="">
                                    <li ng-repeat="courseOrder in bookData | orderBy:'course.listings[0].section'">
                                        <course-with-listings course="courseOrder.course">
                                             -
                                            <span ng-if="!courseOrder.notPlaced">
                                                <strong class="text-success">Request placed!</strong>
                                            </span>
                                            <span ng-if="courseOrder.notPlaced">
                                                <strong class="text-muted"
                                                        ng-if="ordersAreEffectivelyEqual(courseOrder.order, courseOrder.newOrder)">
                                                    An identical request already exists.
                                                </strong>
                                                <strong class="text-danger"
                                                        ng-if="!ordersAreEffectivelyEqual(courseOrder.order, courseOrder.newOrder)">
                                                    <i class="fa fa-exclamation-triangle"></i> A different request already exists. New request ignored.
                                                </strong>
                                            </span>
                                        </course-with-listings>
                                        <span ng-if="courseOrder.notPlaced && !ordersAreEffectivelyEqual(courseOrder.order, courseOrder.newOrder)">
                                            <ul>
                                                <li ng-if="courseOrder.order.required != courseOrder.newOrder.required">
                                                    Existing request marked [[!courseOrder.order.required ? 'not':'']] required, new one is [[!courseOrder.newOrder.required ? 'not':'']] required.
                                                </li>
                                                <li ng-if="courseOrder.order.notes != courseOrder.newOrder.notes">
                                                    Existing notes: [[courseOrder.order.notes || '(no notes)']]
                                                    <br>
                                                    New notes: [[courseOrder.newOrder.notes || '(no notes)']]
                                                </li>
                                            </ul>
                                        </span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div ng-show="!orderWasACompleteSuccess()">
                        <br>
                        <h4 class="text-center">
                            If you would like to change a request that already exists, please delete the request and try again.
                        </h4>
                        <h5 class="text-center">
                            You can delete requests on the following page.
                        </h5>
                    </div>
                    <h1 class="text-center">
                        <a href="{{$continueUrl}}" class="btn btn-primary btn-lg">
                            Place another request <i class="fa fa-arrow-right"></i>
                        </a>
                    </h1>
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