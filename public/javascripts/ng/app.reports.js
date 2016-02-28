var app = angular.module('sisyphus', ['sisyphus.helpers', 'sisyphus.helpers.isbnHyphenate', 'ui.bootstrap' , 'smart-table', 'ngSanitize', 'ngCsv']);



app.directive('superFastTable', function($http) {
    return {
        restrict: 'A',
        scope: {
            superFastTable: '='
        },
        link: function(scope, element, attrs) {
            scope.$watch('superFastTable', function(rows, oldValue) {
                if (rows)
                {
                    var tbody = document.createElement("tbody");
                    for (var r = 0; r < rows.length; r++){
                        var tr = document.createElement("tr");
                        var row = rows[r];
                        for (var c = 0; c < row.length; c++){
                            var td = document.createElement("td");
                            td.appendChild(document.createTextNode(row[c] || ""));
                            tr.appendChild(td);
                        }
                        tbody.appendChild(tr);
                    }

                    $(element).find("tbody").replaceWith(tbody);
                }
                else
                {
                    $(element).find("tbody").html("");
                }
            }, true);
        }
    }
});


app.controller('ReportsController', function($scope, $http, $filter, $q) {
    $scope.STAGE_SELECT_FIELDS= 1;
    $scope.STAGE_VIEW_REPORT = 2;
    $scope.stage = $scope.STAGE_SELECT_FIELDS;


    var dateGrouper = function(values){
        var count = values.Count('');
        if (count == 0)
            return '';
        else if (count == 1)
            return values.First();
        else{
            var first = values.MinBy(function(time){ return moment(time).unix()});
            var last = values.MaxBy(function(time){ return moment(time).unix()});
            if (first == last)
                return first;
            return first + ' - ' + last;
        }
    };

    $scope.options = [
        {
            name: 'Course Title',
            value: function(courseOrderObj){ return courseOrderObj.course.listings[0].name }
        },
        {
            name: 'Course',
            value: function(courseOrderObj){
                var listings = courseOrderObj.course.listings;
                return listings[0].department + ' ' +
                    $filter('zpad')(listings[0].number, 3)
            },
            sort: true,
            width: "7em"
        },
        {
            name: 'Section',
            value: function(courseOrderObj){ return $filter('zpad')(courseOrderObj.course.listings[0].section, 2) },
            group: function(values){
                return values.OrderBy().ToString(", ");
            },
            sort: true
        },
        {
            name: 'Instructor',
            value: function(courseOrderObj){
                return courseOrderObj.course.user ? courseOrderObj.course.user.last_name : 'TBA';
                //return courseOrderObj.course.user ? $scope.groupBySection ? courseOrderObj.course.user.last_name : courseOrderObj.course.user.last_first_name : 'TBA'
            },
            group: function(values){
                return values.OrderBy().Distinct().ToString("; ");
            }
        },
        {
            name: 'Book Title',
            value: function(courseOrderObj){ return courseOrderObj.book.title },
            sort: true
        },
        {
            name: 'ISBN',
            value: function(courseOrderObj){
                return $filter('isbnHyphenate')(courseOrderObj.book.isbn13);
            },
            width: "10.5em"
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
            name: 'Req?',
            value: function(courseOrderObj){ return courseOrderObj.order.required ? 'Yes' : 'No'}
        },
        {
            name: 'Request Notes',
            value: function(courseOrderObj){ return courseOrderObj.order.notes }
        },
        {
            name: 'Date Placed',
            value: function(courseOrderObj){ return moment(courseOrderObj.order.created_at).format('l') },
            group: dateGrouper
        },
        //{
        //    name: 'Course Has Requests?',
        //    value: function(courseOrderObj){ return (courseOrderObj.course.orders && courseOrderObj.course.orders.length) ? 'Yes' : 'No' },
        //    shouldShow: function(){
        //        return !$scope.ReportType || $scope.ReportType == 'courses'
        //    }
        //},
        {
            name: 'Request Deleted?',
            value: function(courseOrderObj){ return (courseOrderObj.order.deleted_at) ? 'Yes' : 'No' },
            doesAutoEnforce: true,
            autoEnforce: function(){
                return $scope.include.deleted && $scope.include.nondeleted
            }
        },
        {
            name: 'Date Deleted',
            value: function(courseOrderObj){ return courseOrderObj.order.deleted_at ? moment(courseOrderObj.order.deleted_at).format('l') : '' },
            shouldShow: function(){
                return !$scope.ReportType || $scope.ReportType == 'orders'
            },
            groupStyle: 'dateRange'
        },
        {
            name: 'Selected No Book?',
            value: function(courseOrderObj){ return courseOrderObj.course.no_book ? 'Yes' : 'No'},
            doesAutoEnforce: true,
            autoEnforce: function(){
                return $scope.include.noBook
            }
        },
        {
            name: 'Cross Listings',
            value: function(courseOrderObj){
                var result = '';
                var listings = courseOrderObj.course.listings;
                for(var i = 1; i < listings.length; i++)
                {
                    result +=
                        listings[i].department + ' ' +
                        $filter('zpad')(listings[i].number, 3) + '-' +
                        $filter('zpad')(listings[i].section, 2) + (i == courses - 1 ? '' : ', ');
                }
                return result;
            }
        },
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

        if (stage == $scope.STAGE_SELECT_FIELDS && $scope.canceler)
            $scope.canceler.resolve();
    };

    $scope.shouldOptionShow = function(optionProperties){
        return !optionProperties.shouldShow || optionProperties.shouldShow()
    };

    $scope.resetInclude = function(){
        $scope.include.deleted = false;
        $scope.include.nondeleted = false;
        $scope.include.submitted = false;
        $scope.include.notSubmitted = false;
        $scope.include.noBook = false;
        $scope.ColumnsSelected = $scope.options.slice();
    };

    $scope.onSelectTerm = function()
    {
        var term = $scope.TermSelected;
        $scope.reportDateStart = moment(term.order_start_date).subtract(1, 'months').toDate();
        $scope.reportDateEnd = moment(term.order_due_date).add(1, 'months').toDate();
    };

    $scope.selectAllDepts = function()
    {
        $scope.DeptsSelected = $scope.departments.slice();
    };

    $scope.selectAllCols = function()
    {
        $scope.ColumnsSelected = $scope.options.slice();
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
        return $scope.TermSelected.display_name + " report " + moment().format("YYYY-MM-DD_hh-mm-ss-a") + ".csv";
    };

    $scope.submit = function()
    {
        $scope.reportRows = null;
        $scope.setStage($scope.STAGE_VIEW_REPORT);

        for(var i = 0; i < $scope.options.length; i++){
            var indexOf = $scope.ColumnsSelected.indexOf($scope.options[i]);
            if ($scope.options[i].doesAutoEnforce){
                if ($scope.options[i].autoEnforce())
                {
                    if (indexOf < 0 )
                        $scope.ColumnsSelected.splice(i, 0, $scope.options[i]);
                } else if (indexOf >= 0 ) {
                    // The auto column shouldn't be included. Take it out.
                    $scope.ColumnsSelected.splice(indexOf, 1);
                }
            }
        }

        $scope.canceler = $q.defer();
        $http.post('/reports/submit-report', {
                startDate: $scope.reportDateStart,
                endDate: $scope.reportDateEnd,
                columns: $scope.columns,
                include: $scope.include,
                term_id: $scope.TermSelected.term_id,
                departments: $scope.DeptsSelected,
        }, {timeout: $scope.canceler.promise}).then(function(response) {
            var courses = Enumerable.From(response.data['courses']);

            var reportRows = courses

                // Select an object for each order that has been returned back to us.
                .SelectMany("course => course.orders", "course, order => {course: course, order: order, book:order.book}")
                // Also select an object for each course that doesn't have any orders at all.
                .Union(courses
                        .Where("course => !course.orders || !course.orders.length")
                        .Select("course => {course: course, order: null, book: null}")
                )
                // Select each object into a row of the report.
                // Some of these rows will get filtered out after we do any grouping.
                .Select(function(courseOrderObject){
                    var row = [];
                    for (var i = 0; i < $scope.options.length; i++){
                        var optionProperties = $scope.options[i];

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
                });

            // Group by sections if the user so desires.
            if ($scope.groupBySection){
                var groupColumns = [];
                for (var i = 0; i < $scope.ColumnsSelected.length; i++) {
                    var optionProperties = $scope.ColumnsSelected[i];
                    if (optionProperties.group) {
                        groupColumns.push(i);
                    }
                }

                if (groupColumns.length){
                    // Group by every property of the row except the properties that we are grouping together.
                    reportRows = reportRows.GroupBy("", "", function(key, similarRows){
                        for (var i in groupColumns) {
                            var index = groupColumns[i];
                            var optionProperties = $scope.ColumnsSelected[index];

                            var values = similarRows.Select(function(r){ return r[index]});
                            key[index] = optionProperties.group(values);
                        }
                        return key;
                    }, function(row){
                        // This is our comparison function. We use a jsonified version of the row with the section
                        // omitted to group by everything except the section and dates.
                        var selector = row.slice();
                        for (var i in groupColumns)
                            selector[groupColumns[i]] = null;
                        return JSON.stringify(selector);
                    });
                }
            }

            // Filter out any columns that the user did not select.
            var columnIsSelectedMap = [];
            for (var i = 0; i < $scope.options.length; i++){
                columnIsSelectedMap[i] = $scope.ColumnsSelected.indexOf($scope.options[i]) >= 0;
            }
            reportRows = reportRows.Select(function(row){
                    var filteredRow = [];
                    for (var i = 0; i < columnIsSelectedMap.length; i++){
                        if (columnIsSelectedMap[i])
                            filteredRow.push(row[i]);
                    }

                    return filteredRow;
                });


            var hasSortedOnce = false;

            for (var i = 0; i < $scope.ColumnsSelected.length; i++){
                var optionProperties = $scope.ColumnsSelected[i];
                if (!optionProperties.sort) continue;
                if (!hasSortedOnce){
                    hasSortedOnce = true;
                    reportRows = reportRows.OrderBy('$[' + i + ']');
                }
                else{
                    reportRows = reportRows.ThenBy('$[' + i + ']');
                }
            }

            reportRows = reportRows.ToArray();

            $scope.reportRows = reportRows;
        });

    }
});