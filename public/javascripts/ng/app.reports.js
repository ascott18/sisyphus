var app = angular.module('sisyphus', ['sisyphus.helpers', 'sisyphus.helpers.isbnHyphenate', 'ui.bootstrap' , 'smart-table', 'ngSanitize', 'ngCsv']);

app.controller('ReportsController', function($scope, $http, $filter) {
    $scope.STAGE_SELECT_FIELDS= 1;
    $scope.STAGE_VIEW_REPORT = 2;
    $scope.stage = $scope.STAGE_SELECT_FIELDS;

    $scope.options = [
        {
            name: 'Course Title',
            value: function(courseOrderObj){ return courseOrderObj.course.course_name }
        },
        {
            name: 'Course Number',
            value: function(courseOrderObj){ return courseOrderObj.course.course_number }
        },
        {
            name: 'Course Section',
            value: function(courseOrderObj){ return courseOrderObj.course.course_section }
        },
        {
            name: 'Course Subject',
            value: function(courseOrderObj){ return courseOrderObj.course.department }
        },
        {
            name: 'Instructor',
            value: function(courseOrderObj){ return courseOrderObj.course.user.last_first_name }
        },
        {
            name: 'Book Title',
            value: function(courseOrderObj){ return courseOrderObj.book.title }
        },
        {
            name: 'ISBN',
            value: function(courseOrderObj){
                return $filter('isbnHyphenate')(courseOrderObj.book.isbn13);
            }
        },
        {
            name: 'Author',
            value: function(courseOrderObj){
                var s = '';
                var authors = courseOrderObj.book.authors;
                for (var i = 0; i < authors.length; i++ )
                    s += authors[i].name + (i == authors.length - 1 ? '' : ', ');

                return s;
            }
        },
        {
            name: 'Edition',
            value: function(courseOrderObj){ return courseOrderObj.book.edition }
        },
        {
            name: 'Publisher',
            value: function(courseOrderObj){ return courseOrderObj.book.publisher }
        },
        {
            name: 'Required?',
            value: function(courseOrderObj){ return courseOrderObj.order.required ? 'Yes' : 'No'}
        },
        {
            name: 'Request Notes',
            value: function(courseOrderObj){ return courseOrderObj.order.notes }
        },
        {
            name: 'Course Has Requests?',
            value: function(courseOrderObj){ return (courseOrderObj.course.orders && courseOrderObj.course.orders.length) ? 'Yes' : 'No' }
        }
    ];

    $scope.ColumnsSelected = $scope.options.slice();

    $scope.include = {
        deleted: false,
        nondeleted: false,
        submitted: false,
        notSubmitted: false,
        noBook: false
    };

    $scope.init = function(terms, departments){
        $scope.terms = terms;
        $scope.departments = departments;
        $scope.DeptsSelected = departments.slice();
    };

    $scope.getStage = function(){
        return $scope.stage;
    };

    $scope.setStage = function(stage){
        $scope.stage = stage;
    };

    $scope.resetInclude = function(){
        $scope.include.deleted = false;
        $scope.include.nondeleted = false;
        $scope.include.submitted = false;
        $scope.include.notSubmitted = false;
        $scope.include.noBook = false;
    };

    $scope.onSelectTerm = function()
    {
        var term = $scope.TermSelected;
        $scope.reportDateStart = moment(term.order_start_date).toDate();
        $scope.reportDateEnd = moment(term.order_due_date).toDate();
    };

    $scope.isCheckboxChecked = function() {
        if ($scope.ReportType == 'orders')
        {
            return ($scope.include.deleted || $scope.include.nondeleted);
        }
        return ($scope.include.submitted || $scope.include.notSubmitted || $scope.include.noBook);
    };


    $scope.getReportHeaderRow = function(){
        var row = [];
        for (var i = 0; i < $scope.ColumnsSelected.length; i++){
            var optionProperties = $scope.ColumnsSelected[i];
            row.push(optionProperties.name);
        }
        return row;
    };

    $scope.getReportCsvFileName = function(){
        return "books-report_" + moment().format("YYYY-MM-DD-hh_mm-ss-a");
    };

    $scope.submit = function()
    {
        $scope.reportRows = null;
        $scope.setStage($scope.STAGE_VIEW_REPORT);

        $http.post('/reports/submit-report', {
                startDate: $scope.reportDateStart,
                endDate: $scope.reportDateEnd,
                columns: $scope.columns,
                include: $scope.include,
                term_id: $scope.TermSelected.term_id,
                departments: $scope.DeptsSelected,
        }).then(function(response) {
            var courses = Enumerable.From(response.data['courses']);

            var reportRows = courses
                // Select an object for each order that has been returned back to us.
                .SelectMany("course => course.orders", "course, order => {course: course, order: order, book:order.book}")
                // Also select an object for each course that doesn't have any orders at all.
                .Union(courses
                        .Where("course => !course.orders || !course.orders.length")
                        .Select("course => {course: course, order: null, book: null}")
                )
                .Select(function(courseOrderObject){
                    var row = [];
                    for (var i = 0; i < $scope.ColumnsSelected.length; i++){
                        var optionProperties = $scope.ColumnsSelected[i];

                        // wow super lame where did all my errors go idk they just disappeared
                        try{
                            var value = optionProperties.value(courseOrderObject);
                            row.push(value);
                        }
                        catch(something){
                            row.push(null);
                        }
                    }

                    return row;
                })
                .ToArray();


            $scope.reportRows = reportRows;

        })

    }
});