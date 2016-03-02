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

                    <div class="form-group">
                        <label>Select Course CSV
                            <input type="file" file-model="file">
                        </label>
                        <p class="help-block">Example block-level help text here.</p>
                    </div>

                    <div class="col-lg-12 ">
                        <button class="btn btn-success" ng-click="submitForPreview()" >
                            Process and Review input <i class="fa arrow-right"></i>
                        </button>
                    </div>


                    <div>
                        <h4>These courses were newly created.</h4>
                        <ul>
                            <li ng-repeat="course in actions.newCourse">
                                <course-with-listings course="course"></course-with-listings>
                            </li>
                        </ul>

                        <h4>These listings were newly created on existing courses.</h4>
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

                        <h4>These listings were updated.</h4>
                        <ul>
                            <li ng-repeat="listingPair in actions.updatedListing">
                                [[listingPair[0].department]] [[listingPair[0].number | zpad:3]]-[[listingPair[0].section | zpad:2]] [[listingPair[0].name]]
                                <i class="fa fa-arrow-right"></i>
                                [[listingPair[1].department]] [[listingPair[1].number | zpad:3]]-[[listingPair[1].section | zpad:2]] [[listingPair[1].name]]
                            </li>
                        </ul>

                        <h4>These listings didn't change.</h4>
                        <ul>
                            <li ng-repeat="listing in actions.noChangeListing">
                                [[listing.department]] [[listing.number | zpad:3]]-[[listing.section | zpad:2]] [[listing.name]]
                            </li>
                        </ul>

                        <h4>These listings were deleted.</h4>
                        <ul>
                            <li ng-repeat="listing in actions.deletedListing">
                                [[listing.department]] [[listing.number | zpad:3]]-[[listing.section | zpad:2]] [[listing.name]]
                            </li>
                        </ul>
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
