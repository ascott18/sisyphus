
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

app.controller('NewTicketController', ['$scope', '$http', 'HelpService', 'statusFilter', function($scope, $http, HelpService, statusFilter) {
    $scope.ticket = {};


    //var unloadListener = function (e) {
    //    var confirmationMessage = 'If you leave before submitting, your changes will be lost.';
    //
    //    (e || window.event).returnValue = confirmationMessage; //Gecko + IE
    //    return confirmationMessage; //Gecko + Webkit, Safari, Chrome etc.
    //};

    //window.addEventListener("beforeunload", unloadListener);

    $scope.setTicket = function(ticket) {
        $scope.ticket = ticket;
    };

    $scope.submitTicket = function(){
        //window.removeEventListener("beforeunload", unloadListener);

        $http.post('/tickets/submit-ticket', {ticket : $scope.ticket}).then(
            function success(response){
                var ticket = response.data;
                $scope.ticket = ticket;
        });
    };
}]);


app.filter('status', function () {
    return function(input) {
        switch(input) {
            case 0:
                out = "New";
                break;
            case 1:
                out = "In Progress";
                break;
            default:
                out = "Closed";
                break;
        }
        return out;
    };
});

app.controller('TicketController', ['$scope', '$http', 'TicketsService', 'statusFilter', function($scope, $http, TicketsService, statusFilter) {
    $scope.ticket = {};
    $scope.comments = [];
    $scope.comment = {body: ""};

    $scope.statuses = TicketsService.statuses;
    $scope.statusSelected;

    $scope.setTicket = function(ticket) {
        $scope.ticket = ticket;
        $scope.statusSelected = $scope.statuses[$scope.ticket.status];
        $scope.comments = ticket.comments;
    };

    $scope.submitComment = function() {

        if ($scope.comment['body'].trim()) {
            $http.post('/tickets/submit-comment', {comment: $scope.comment, ticket_id: $scope.ticket["ticket_id"], status : $scope.statusSelected.key}).then(
                function success(response){
                    $scope.comment = {body: ""};
                    $scope.comments = response.data.comments;
                    $scope.ticket.status = response.data.status;
                });
        }
    };
}]);

app.directive('ticketDetails', function() {
    return {
        restrict: 'E',
        scope: {
            ticket: '=',
            author: '='
        },
        templateUrl: '/javascripts/ng/templates/ticketDetails.html'
    };
});

app.service('TicketsService', function () {
    var statuses = [{key : 0, value: "New"},
                    {key : 1, value: "In progress"},
                    {key : 2, value: "Closed"}];

    var service = {
        statuses: statuses
    };

    return service;
});

app.controller('TicketsIndexController', ['$scope', '$http', 'TicketsService', 'statusFilter', function($scope, $http, TicketService, statusFilter) {
    var ctrl1 = this;
    $scope.stCtrl=null;
    $scope.stTableRef=null;

    this.displayed = [];

    $scope.statuses = TicketService.statuses;

    $scope.updateStatus= function()
    {
        if($scope.stCtrl)
            $scope.stCtrl.pipe();

        if($scope.stTableRef)
            $scope.stTableRef.pagination.start = 0;
    };

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

        ctrl.isLoading = true;

        var pagination = tableState.pagination;
        var start = pagination.start || 0;
        var end = pagination.number || 10;
        var page = (start/end)+1;

        console.log($scope.statusSelected);
        tableState.statusSelected = $scope.statusSelected;
        var getRequestString = '/tickets/ticket-list';

        var config = {
            params: {
                page: page,
                table_state: tableState
            }
        };

        $http.get(getRequestString, config).then(function(response){
            tableState.pagination.numberOfPages = response.data.last_page;                    // update number of pages with laravel response
            tableState.pagination.number = response.data.per_page;                            // update entries per page with laravel response
            console.log(response.data.data.length);
            ctrl1.displayed = response.data.data;                                              // save laravel response data
            ctrl1.isLoading=false;
        });
    }
}]);


