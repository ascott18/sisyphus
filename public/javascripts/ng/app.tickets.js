
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

    // pop-up to warn the user we don't keep track of their edits.
    var unloadListener = function (e) {
        var confirmationMessage = 'If you leave before submitting, your changes will be lost.';

        (e || window.event).returnValue = confirmationMessage; //Gecko + IE
        return confirmationMessage; //Gecko + Webkit, Safari, Chrome etc.
    };

    window.addEventListener("beforeunload", unloadListener);

    $scope.setTicket = function(ticket) {
        $scope.ticket = ticket;

    };

    $scope.submitTicket = function(){
        window.removeEventListener("beforeunload", unloadListener);

        $http.post('/tickets/submit-ticket', {ticket : $scope.ticket}).then(
            function success(response){
                var ticket = response.data.ticket;
                $scope.ticket = ticket;

                //upon successfully submitting the ticket, send emails.
                $http.post('/tickets/send-new-ticket-email', {ticket_id: $scope.ticket.ticket_id}).then(
                    function success(response) {
                        //change the location of the user, maybe should take them to tickets tab?
                        window.location.href = '/';
                    }
                );
        });
    };
}]);


//This is a filter used to convert the number value of the status to a string value.
app.filter('status', ['TicketsService', function (TicketsService) {
    return function(input) {
        var statuses = TicketsService.statuses;
        //find the right number and return the appropriate string.
        for (var index in statuses) {
            if (input == statuses[index].key) {
                return statuses[index].value;
            }
        }
        //That status doesn't exist in the tickets service.
        return "Invalid Status";
    };
}]);

app.controller('TicketController', ['$scope', '$http', 'TicketsService', 'statusFilter', function($scope, $http, TicketsService, statusFilter) {
    $scope.ticket = {};
    $scope.comments = [];
    $scope.comment = {body: ""};

    $scope.statuses = TicketsService.statuses;
    $scope.statusSelected;

    var hasURL = function(ticket) {
        if ($scope.ticket.url == "null") {
            return false;
        }
        return true;
    };

    $scope.setTicket = function(ticket) {

        $scope.ticket = ticket;
        $scope.ticket.urlValid = hasURL(ticket);

        $scope.statusSelected = $scope.statuses[$scope.ticket.status];
        $scope.comments = ticket.comments;
    };

    $scope.submitComment = function() {

        if ($scope.comment['body'].trim()) {

            // create the comment.
            $http.post('/tickets/submit-comment', {comment: $scope.comment, ticket_id: $scope.ticket["ticket_id"], status : $scope.statusSelected.key}).then(
                function success(response){

                    $scope.comments = response.data.ticket.comments;
                    $scope.comment = {body: ""};
                    $scope.ticket.status = response.data.status;

                    var comment_id = response.data.comment.ticket_comment_id;

                    // Upon successful ticket comment creation,
                    // send an email that a comment has been made
                    $http.post('/tickets/send-new-comment-email', {ticket_id: $scope.ticket.ticket_id, ticket_comment_id : comment_id ,status: $scope.statusSelected}).then(
                        function success(response) {
                            //console.log("Email was successfully sent");
                        }
                    );
                });
        }
    };
}]);

//Keeps track of what status value means what. The filter uses this to display the string status value to user.
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


