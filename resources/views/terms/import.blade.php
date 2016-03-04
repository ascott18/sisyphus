@extends('layouts.master', [
    'breadcrumbs' => [
        ['Terms', '/terms'],
        [$term->display_name, '/terms/details/' . $term->term_id],
        ['Import Courses'],
    ]
])


@section('content')

    <div class="row" ng-controller="TermsImportController"
            ng-init="term_id = {{$term->term_id}}">
        <div class="col-lg-12" ng-show="!browserTooOld">

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-calendar fa-fw"></i> Import Data</h3>
                </div>
                <div class="panel-body">
                    <div ng-show="!submittingPreview && !havePreviewResponse && !submittingImport">
                        <h2>
                            Please select a course report from reporting.eastern.ewu.edu to import.
                        </h2>
                        <h4>We won't make any changes right away - you will have a chance to review what will happen before you confirm the import.</h4>
                        <br>
                        <br>

                        <div class="form-group">
                            <label>Select Course Report (.csv, .xls, or .xlsx)
                                <input type="file" file-model="file">
                            </label>
                        </div>
                        <br>
                    </div>

                    <div ng-show="submittingPreview || submittingImport">
                        <h2>
                            The file you submitted is being processed. This can take a minute...
                        </h2>
                    </div>

                    <div ng-show="havePreviewResponse && !submittedImport && !submittingImport">
                        <h2>
                            Here is a <strong>preview</strong> of what will happen if you proceed with this import.
                        </h2>
                        <h4>Please be aware that <strong>nothing has actually been imported</strong> - this is just a preview.</h4>
                        <h5>If you would like to continue with this import after you review this information, scroll to the bottom and click Import.</h5>
                        <br>
                        <br>
                    </div>

                    <div ng-show="submittedImport">
                        <h2>
                            The following actions were successfully performed!
                        </h2>
                    </div>

                    <div class="row" ng-show="actions">
                        <div class="col-md-4" ng-show="actions.newCourse.length">
                            <h4>These [[actions.newCourse.length]] courses [[!submittedImport ? 'will be' : 'were']] newly created.</h4>
                            <ul>
                                <li ng-repeat="course in actions.newCourse | limitTo:newCourseLimit">
                                    <course-with-listings course="course">
                                        &mdash; [[course.listings[0].name]]
                                    </course-with-listings>
                                </li>
                            </ul>

                            <a
                                    class="cursor-pointer"
                                    style="margin-left: 40px;"
                                    ng-if="actions.newCourse.length > newCourseLimit"
                                    ng-click="showAllNewCourses();">
                                Click to show all...
                            </a>
                        </div>

                        <div class="col-md-4" ng-show="actions.newListing.length">
                            <h4>These [[actions.newListing.length]] listings [[!submittedImport ? 'will be' : 'were']] newly created on existing courses.</h4>
                            <ul>
                                <li ng-repeat="courseListingPair in actions.newListing">
                                <span class="text-muted">
                                    <course-with-listings course="courseListingPair[0]">
                                        &mdash; created
                                        <span style="color: black">
                                            [[courseListingPair[1].department]] [[courseListingPair[1].number | zpad:3]]-[[courseListingPair[1].section | zpad:2]] [[courseListingPair[1].name]]
                                        </span>
                                    </course-with-listings>
                                </span>
                                </li>
                            </ul>
                        </div>

                        <div class="col-md-4" ng-show="actions.updatedListing.length">
                            <h4>These [[actions.updatedListing.length]] listings [[!submittedImport ? 'will be' : 'were']] updated.</h4>
                            <ul>
                                <li ng-repeat="listingPair in actions.updatedListing">
                                    [[listingPair[0].department]] [[listingPair[0].number | zpad:3]]-[[listingPair[0].section | zpad:2]] [[listingPair[0].name]]
                                    <i class="fa fa-arrow-right"></i>
                                    [[listingPair[1].department]] [[listingPair[1].number | zpad:3]]-[[listingPair[1].section | zpad:2]] [[listingPair[1].name]]
                                </li>
                            </ul>
                        </div>

                        <div class="col-md-4" ng-show="actions.noChangeListing.length">
                            <h4>These [[actions.noChangeListing.length]] listings didn't change.</h4>
                            <ul>
                                <li ng-repeat="listing in actions.noChangeListing | limitTo:noChangeListingLimit">
                                    [[listing.department]] [[listing.number | zpad:3]]-[[listing.section | zpad:2]] [[listing.name]]
                                </li>
                            </ul>

                            <a
                                    class="cursor-pointer"
                                    style="margin-left: 40px;"
                                    ng-if="actions.noChangeListing.length > noChangeListingLimit"
                                    ng-click="showAllNoChangeListings()">
                                Click to show all...
                            </a>
                        </div>

                        <div class="col-md-4" ng-show="actions.deletedListing.length">
                            <h4>These [[actions.deletedListing.length]] listings [[!submittedImport ? 'will be' : 'were']] deleted.</h4>
                            <ul>
                                <li ng-repeat="listing in actions.deletedListing">
                                    [[listing.department]] [[listing.number | zpad:3]]-[[listing.section | zpad:2]] [[listing.name]]
                                </li>
                            </ul>
                        </div>

                        <div class="col-md-4" ng-show="actions.deletedCourseWithoutOrders.length">
                            <h4>These [[actions.deletedCourseWithoutOrders.length]] courses [[!submittedImport ? 'will be' : 'were']] deleted.</h4>
                            <ul>
                                <li ng-repeat="course in actions.deletedCourseWithoutOrders">
                                    <course-with-listings course="course">
                                        &mdash; [[course.listings[0].name]]
                                    </course-with-listings>
                                </li>
                            </ul>
                        </div>

                        <div class="col-md-4" ng-show="actions.deletedCourseWithOrders.length">
                            <h4>These [[actions.deletedCourseWithOrders.length]] courses needed to be deleted, but had requests.
                                <small>They [[!submittedImport ? 'will be' : 'were']] marked as no book, and their requests [[!submittedImport ? 'will be' : 'were']] deleted.</small>
                            </h4>
                            <ul>
                                <li ng-repeat="course in actions.deletedCourseWithOrders">
                                    <course-with-listings course="course">
                                        &mdash; [[course.listings[0].name]]
                                    </course-with-listings>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <br>
                    <br>

                    <div class="row">
                        <div class="col-lg-12 ">
                            <button class="btn btn-success" ng-click="submitForPreview()" ng-show="!submittingPreview && !havePreviewResponse">
                                Process and Review Input <i class="fa arrow-right"></i>
                            </button>

                            <button class="btn btn-success" ng-click="submitForImport()" ng-show="havePreviewResponse && !submittingImport && !submittedImport" >
                                Import These Courses <i class="fa arrow-right"></i>
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

@stop


@section('scripts-head')
    <script src="/javascripts/ui-bootstrap-tpls-0.14.3.min.js"></script>
    <script src="/javascripts/ng/app.terms.js"></script>
@stop
