/**
 * Created by Admin00 on 11/16/2015.
 */
var app = angular.module('sisyphus', ['sisyphus.helpers', 'sisyphus.helpers.isbnHyphenate', 'smart-table']);

app.directive('bookEditor', function() {
    return {
        restrict: 'E',
        templateUrl: '/javascripts/ng/templates/bookEditor.html'
    };
});

app.controller('BooksController', function($scope, $http) {
    var ctrl = this;

    this.displayed = [];

    this.callServer = function callServer(tableState) {
        ctrl.isLoading = true;

        var pagination = tableState.pagination;
        var start = pagination.start || 0;
        var end = pagination.number || 10;
        var page = (start/end)+1;

        var getRequestString = '/books/book-list?page=' + page;                                         // book list uri

        if(tableState.sort.predicate) {
            getRequestString += '&sort=' + encodeURIComponent(tableState.sort.predicate);               // build search
            if(tableState.sort.reverse)
                getRequestString += '&dir=desc';
        }

        if(tableState.search.predicateObject) {
            var predicateObject = tableState.search.predicateObject;
            if(predicateObject.title)
                getRequestString += '&title=' + encodeURIComponent(predicateObject.title);              // search title
            if(predicateObject.author)
                getRequestString += '&author=' + encodeURIComponent(predicateObject.author);            // search authors
            if(predicateObject.publisher)
                getRequestString += '&publisher=' + encodeURIComponent(predicateObject.publisher);      // search publisher
            if(predicateObject.isbn13)
                getRequestString += '&isbn13=' + encodeURIComponent(predicateObject.isbn13);            // search isbn
        }

        $http.get(getRequestString).then(
            function success(response) {
                tableState.pagination.numberOfPages = response.data.last_page;                          // update number of pages with what laravel gives back
                tableState.pagination.number = response.data.per_page;                                  // update how many per page based on laravel response
                ctrl.displayed = response.data.data;                                                    // get return data
                ctrl.isLoading=false;
            }
        );

    }
});

app.controller('EditBookController', function($scope, $http) {
    $scope.authors = [];
    $scope.book = {};
    $scope.submitted = false;

    $scope.addAuthor = function() {
        $scope.authors.push({name: ""});
    };

    $scope.removeAuthor = function(index) {
        if (index >= 0 && index < $scope.authors.length) {
            $scope.authors.splice(index, 1);
        }
    };

    $scope.setBook = function(book) {
        $scope.book = book;
    };

    $scope.addAuthors = function(authors) {
        $scope.authors = authors;
    };

    $scope.reset = function(form) {
        $scope.submitted = false;
        if (form) {
            form.$setPristine();
            form.$setUntouched();
        }
        $scope.book = angular.copy($scope.master);
    };


    $scope.submit = function(form, e){
        if (form.$valid)
            form.submit();
        else{
            form.$setSubmitted(true);
            e.preventDefault();
        }
    };

    $scope.reset();
});

app.controller('BookDetailsController', function($scope, $http) {
    var ctrl = this;

    $scope.book_id = book_id_init;
    $scope.book_isbn_13 = book_isbn_13_init;

    /* TODO: We need a missing thumbnail image */
    $scope.isCached = false;
    $scope.book_cover_img = "";


    $scope.getLaravelImage = function() {
        $http.get("/books/cover?isbn=" + $scope.book_isbn_13).then (
            function success(response){
                if(response.data.image != "") {
                    $scope.book_cover_img = "data:image/jpeg;base64," + response.data.image;
                    $scope.isCached = response.data.cached;
                } else {
                    $scope.book_cover_img = "/images/coverNotAvailable.jpg";
                }
            }
        )
    }

    $scope.getLaravelImage();


    this.displayed = [];

    this.callServer = function callServer(tableState) { // TODO: move this into other controller
        ctrl.isLoading = true;

        var pagination = tableState.pagination;
        var start = pagination.start || 0;
        var end = pagination.number || 10;
        var page = (start/end)+1;

        var getRequestString = '/books/book-detail-list?page=' + page;                                  // book list uri

        getRequestString += '&book_id=' + $scope.book_id;                                               // get book id

        if(tableState.sort.predicate) {
            getRequestString += '&sort=' + encodeURIComponent(tableState.sort.predicate);               // build sort
            if(tableState.sort.reverse)
                getRequestString += '&dir=desc';
        }

        if(tableState.search.predicateObject) {
            var predicateObject = tableState.search.predicateObject;
            if(predicateObject.section)
                getRequestString += '&section=' + encodeURIComponent(predicateObject.section);
            if(predicateObject.course_name)
                getRequestString += '&course_name=' + encodeURIComponent(predicateObject.course_name);
            if(predicateObject.ordered_by_name)
                getRequestString += '&ordered_by=' + encodeURIComponent(predicateObject.ordered_by_name);
        }

        $http.get(getRequestString).then(
            function success(response) {
                tableState.pagination.numberOfPages = response.data.last_page;
                tableState.pagination.number = response.data.per_page;
                ctrl.displayed = response.data.data;
                ctrl.isLoading=false;
            }
        );
    }
});