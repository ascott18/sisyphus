var app = angular.module('sisyphus', ['sisyphus.helpers', 'ui.bootstrap' , 'smart-table']);


app.config([ '$compileProvider',
    function($compileProvider) {
        $compileProvider.aHrefSanitizationWhitelist(/^s*(https?|ftp|blob|mailto|chrome-extension):/);
        // pre-Angularv1.2 use urlSanizationWhitelist()
    }
]);


app.controller('ReportsController', function($scope, $http) {
    var data=[];
    $scope.json = JSON.stringify(data);
    $scope.downloadLink = window.URL.createObjectURL(new Blob([$scope.json], {type: "application/json"}));


    $scope.STAGE_SELECT_FIELDS= 1;
    $scope.STAGE_CREATE_REPORT = 2;
    $scope.stage = $scope.STAGE_SELECT_FIELDS;
    $scope.options ={   course_title: 'Course Title',
                        course_number:'Course Number',
                        course_section: 'Course Section',
                        course_department: 'Course Department',
                        course_instructor:'Instructor',
                        book_title: 'Book Title',
                        book_isbn: 'ISBN',
                        book_authors: 'Author',
                        book_edition: 'Edition',
                        book_publisher: 'Publisher',
                        order_required: 'Required',
                        order_notes: 'Notes'
    };

    $scope.columns=[];
    $scope.include = {
        deleted: false,
        nondeleted: false,
        submitted: false,
        notSubmitted: false,
        noBook: false
    };

    $scope.init = function(terms,departments){
        $scope.terms=terms;
        $scope.departments=departments;
    };

    $scope.getStage = function(){
        return $scope.stage;
    };

    $scope.setStage = function(stage){
        $scope.stage = stage;
    };

    $scope.setDeleted = function(){
        $scope.resetInclude();
        $scope.include.deleted = true;
    };

    $scope.setNonDeleted = function(){
        $scope.resetInclude();
        $scope.include.nondeleted = true;
    };

    $scope.resetInclude=function(){
        $scope.include.deleted=false;
        $scope.include.nondeleted=false;
        $scope.include.submitted=false;
        $scope.include.notSubmitted=false;
        $scope.include.noBook=false;
    };

    $scope.onSelectTerm = function()
    {
        var term = $scope.TermSelected;
        $scope.reportDateStart = moment(term.order_start_date).toDate();
        $scope.reportDateEnd = moment(term.order_due_date).toDate();
    };

    $scope.toggleColumn = function(column)
    {
        if($scope.isColumnSelected(column))
        {
            var index = $scope.columns.indexOf(column);
            $scope.columns.splice(index,1);
        }
        else
        {
            $scope.columns.push(column);
        }

    };

    $scope.isColumnSelected = function(column)
    {
        for(var i=0;i<$scope.columns.length;i++)
        {
            if($scope.columns[i]==column)
            {
                return true;
            }
        }
    };

    $scope.isCheckboxChecked = function() {
        if($scope.include.deleted||$scope.include.nondeleted)
        {
            return true;
        }
        return ($scope.include.submitted || $scope.include.notSubmitted || $scope.include.noBook);
    }





    $scope.submit=function()
    {
            $scope.stage=$scope.STAGE_CREATE_REPORT;
            $scope.ReportType=null;
            $http.post('/reports/submit-report', {
                    startDate: $scope.reportDateStart,
                    endDate: $scope.reportDateEnd,
                    columns: $scope.columns,
                    include: $scope.include,
                    term_id: $scope.TermSelected.term_id,
                    dept: $scope.DeptSelected,
            }).then(function(response) {
                    var courses = response.data['courses'];
                // TODO: linqjs-ify this.
                    var flattenedCourses = [];
                    for (var i = 0; i < courses.length; i++){
                        var course = courses[i];
                        if (!course.orders || course.orders.length == 0){
                            flattenedCourses.push({course: course, order: null});
                        }
                        else{
                            for (var j = 0; j < course.orders.length; j++){
                                flattenedCourses.push({course: course, order: course.orders[j]});
                            }
                        }

                    }
                    $scope.reportData = flattenedCourses;
                for(var key in $scope.reportData)
                {
                    if($scope.reportData.hasOwnProperty(key))
                    {
                        var obj=$scope.reportData[key];
                        for(var prop in obj)
                        {
                            if(obj.hasOwnProperty(prop))
                            {
                                console.log(prop + "=" +obj[prop]);
                            }
                        }
                    }
                    console.log();
                }
                $scope.json = JSON.stringify(data);




                //    var newWindow=window.open('report', 'Report');
                //
                //    var html ="<link href='/stylesheets/bootstrap.min.css' rel='stylesheet'>"+
                //    "<div class='pull-right'><?php echo date('m/d/Y, h:i:s a');?></div>"+
                //"<h1>Book Request Check Sheet</h1>"+
                //"<style>"+
                //"td, th{font-family: sans-serif;font-size:10pt;}"+
                //"table {"+
                //    "border-collapse: collapse;}"+
                //"tr {"+
                //    "border: solid;"+
                //    "border-width: 1px 0;}"+
                //"</style>"+
                //"<style media='print'>"+
                //    "td,th{font-family: sans-serif;font-size:8pt;}"+
                //"</style>"+
                //"<div class='row'>"+
                //    "<div class='col-lg-12'>"+
                //    "<table width='100%' cellpadding='8'>"+
                //
                //    "<thead>"+
                //    "<tr>";
                //    if($scope.isColumnSelected(3)) {
                //        html+="<th>Course Name</th>" ;
                //    }
                //    if($scope.isColumnSelected(0)) {
                //        html+="<th>Course Number</th>" ;
                //    }
                //    if($scope.isColumnSelected(1)) {
                //        html+="<th>Course Section</th>" ;
                //    }
                //    if($scope.isColumnSelected(2)) {
                //        html+="<th>Instructor</th>" ;
                //    }
                //    if($scope.isColumnSelected(4)) {
                //        html+="<th>Book</th>" ;
                //    }
                //    if($scope.isColumnSelected(5)) {
                //        html+="<th>ISBN</th>" ;
                //    }
                //    if($scope.isColumnSelected(6)) {
                //        html+="<th>Author</th>" ;
                //    }
                //
                //    if($scope.isColumnSelected(7)) {
                //        html+="<th>Ed</th>" ;
                //    }
                //    if($scope.isColumnSelected(8)) {
                //        html+="<th>Publisher</th>" ;
                //    }
                //    if($scope.isColumnSelected(9)) {
                //        html+="<th>Req</th>" ;
                //    }
                //    if($scope.isColumnSelected(10)) {
                //        html+="<th>Notes</th>" ;
                //    }
                //    html+= "</tr>"+
                //"</thead>"+
                //"<tbody>";
                //
                //for(order in $scope.data)
                //{
                //    html+="<tr>";
                //    if($scope.isColumnSelected(3))
                //    {
                //        html+="<td>"+$scope.data[order].course_name+"</td>"
                //    }
                //    if($scope.isColumnSelected(0)) {
                //    html+="<td>"+$scope.data[order].course_number+"</td>"
                //    }
                //    if($scope.isColumnSelected(1)) {
                //        html+="<td>"+$scope.data[order].course_section+"</td>"
                //    }
                //    if($scope.isColumnSelected(2)) {
                //        html+="<td>"+$scope.data[order].first_name+" "+$scope.data[order].last_name+"</td>"
                //    }
                //    if($scope.isColumnSelected(4)) {
                //        html+="<td>"+$scope.data[order].title+"</td>"
                //    }
                //    if($scope.isColumnSelected(5)) {
                //        html+="<td>"+$scope.data[order].isbn13+"</td>"
                //    }
                //    if($scope.isColumnSelected(6)) {
                //    }
                //
                //    if($scope.isColumnSelected(7)) {
                //        html+="<td>"+$scope.data[order].edition+"</td>"
                //    }
                //    if($scope.isColumnSelected(8)) {
                //        html+="<td>"+$scope.data[order].publisher+"</td>"
                //    }
                //    if($scope.isColumnSelected(9)) {
                //        html+="<td>"+$scope.data[order].required+"</td>"
                //    }
                //    html+="</tr>";
                //}
                //console.log(response.data['start']);
                //
                //
                //
                //html+="</tbody>"+
                //"</table>"+
                //
                //
                //"</div>"+
                //"</div>";
                //
                //
                //    newWindow .document.open()
                //    newWindow .document.write(html)
                //    newWindow .document.close()
                //
                //
                })

    }
});