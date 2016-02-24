
var app = angular.module('sisyphus', ['sisyphus.helpers', 'textAngular']);


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


app.filter('status', ['TicketsService', function (TicketsService) {
    return function(input) {
        var statuses = TicketsService.statuses;
        for (var index in statuses) {
            if (input == statuses[index].key) {
                return statuses[index].value;
            }
        }
        return statuses[statuses.length - 1].value;
    };
}]);

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

app.controller('TicketsIndexController', function($scope, StHelper, statusFilter, TicketsService) {
    $scope.statuses = TicketsService.statuses;

    $scope.updateStatus = function()
    {
        StHelper.reset($scope.stCtrl);
    };

    $scope.callServer = function(tableState, ctrl) {
        // Keep track of the smart-table controller so that we can
        // reset the table when the user changes the status filter.
        $scope.stCtrl = ctrl;

        tableState.statusSelected = $scope.statusSelected;

        var config = {
            url: '/tickets/ticket-list'
        };

        StHelper.callServer(tableState, config, $scope );
    }
});


