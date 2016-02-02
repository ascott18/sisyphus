var app = angular.module('sisyphus', ['sisyphus.helpers', 'ui.bootstrap' , 'smart-table']);

app.controller('ReportsController', function($scope, $http) {
    $scope.columns=[];

    $scope.include = {
        deleted: true,
        nondeleted: true,
        submitted: true,
        notSubmitted: true,
        noBook: true
    };
    //
    //$scope.reportDateStart = new Date();
    //$scope.reportDateEnd = new Date();

    $scope.onSelectTerm = function()
    {
        var term = $scope.TermSelected;
        $scope.reportDateStart = moment(term.order_start_date).toDate();
        $scope.reportDateEnd = moment(term.order_due_date).toDate();
    };

    $scope.toggleColumn = function(columnIndex)
    {
        if($scope.isColumnSelected(columnIndex))
        {
            var index = $scope.columns.indexOf(columnIndex);
            $scope.columns.splice(index,1);
        }
        else
        {
            $scope.columns.push(columnIndex);
        }

    };

    $scope.isColumnSelected = function(columnIndex)
    {
        for(var i=0;i<$scope.columns.length;i++)
        {
            if($scope.columns[i]==columnIndex)
            {
                return true;
            }
        }
    };


    $scope.submit=function()
    {
            $http.post('/reports/submit-report', {
                    startDate: $scope.reportDateStart,
                    endDate: $scope.reportDateEnd,
                    columns: $scope.columns,
                    include: $scope.include,
                    term_id: $scope.TermSelected.term_id,
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