
var app = angular.module('sisyphus', ['sisyphus.helpers', 'angularUtils.directives.dirPagination', 'textAngular']);


app.config(function($provide) {
    $provide.decorator('taOptions', ['taRegisterTool', '$delegate', function (taRegisterTool, taOptions) {
        taOptions.toolbar = [
            ['h1', 'h2', 'h3', 'pre', 'p'],
            ['bold', 'italics', 'underline', 'strikeThrough', 'clear'],
            ['ul', 'ol'],
            ['undo', 'redo'],
            ['justifyLeft', 'justifyCenter', 'justifyRight', 'indent', 'outdent'],
            ['insertLink']

            // All available options.
            //['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'pre', 'quote'],
            //['bold', 'italics', 'underline', 'strikeThrough', 'ul', 'ol', 'redo', 'undo', 'clear'],
            //['justifyLeft', 'justifyCenter', 'justifyRight', 'indent', 'outdent'],
            //['html', 'insertImage', 'insertLink', 'insertVideo', 'wordcount', 'charcount']
        ];

        return taOptions;
    }]);
});

app.controller('TicketController', function($scope, $http) {

    $scope.ticket = {};

    var unloadListener = function (e) {
        var confirmationMessage = 'If you leave before submitting, your changes will be lost.';

        (e || window.event).returnValue = confirmationMessage; //Gecko + IE
        return confirmationMessage; //Gecko + Webkit, Safari, Chrome etc.
    };

    window.addEventListener("beforeunload", unloadListener);





    $scope.createTicket = function(ticket){
        $http.post('/tickets/create', ticket).then(
            function success(response){
                var ticket = response.data;

                $scope.ticket = ticket;
            });
    };

    $scope.submitTicket = function(){
        window.removeEventListener("beforeunload", unloadListener);
    };
});


app.controller('TicketsIndexController', function($scope, $http) {
    var ctrl1 = this;
    $scope.stCtrl=null;
    $scope.stTableRef=null;

    this.displayed = [];

    this.callServer = function callServer(tableState, ctrl) {

        ctrl1.isLoading = true;
        if(!$scope.stCtrl&&ctrl)
        {
            $scope.stCtrl=ctrl;
        }

        if(!$scope.stTableRef&&tableState)
        {
            $scope.stTableRef=tableState;
        }

        if(!tableState&&$scope.stCtrl){
            $scope.stCtrl.pipe();
            return;
        }

        // TODO: nathan do this

        //var pagination = tableState.pagination;
        //var start = pagination.start || 0;
        //var end = pagination.number || 10;
        //var page = (start/end)+1;
        //
        //var getRequestString = '/tickets/ticket-list?page=' + page;                                 // set course list uri
        //
        //if(tableState.sort.predicate) {
        //    getRequestString += '&sort=' + tableState.sort.predicate;                               // build sort string
        //    if(tableState.sort.reverse)
        //        getRequestString += '&dir=desc';
        //}
        //
        //if(tableState.search.predicateObject) {
        //    var predicateObject = tableState.search.predicateObject;
        //    if(predicateObject.section)
        //        getRequestString += '&section=' + encodeURIComponent(predicateObject.section);     // search for section
        //    if(predicateObject.name)
        //        getRequestString += '&name=' + encodeURIComponent(predicateObject.name);           // search for name
        //}


        $http.get('/tickets/ticket-list').then(
            function success(response) {
                tableState.pagination.numberOfPages = response.data.last_page;                    // update number of pages with laravel response
                tableState.pagination.number = response.data.per_page;                            // update entries per page with laravel response
                ctrl1.displayed = response.data.data;                                              // save laravel response data
                ctrl1.isLoading=false;
            }
        );

    }
});
