
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

app.controller('NewTicketController', ['$scope', '$http', 'HelpService', function($scope, $http, HelpService) {
    $scope.ticket = {department : 'CSCD', 'url' : 'google.com'};


    //var unloadListener = function (e) {
    //    var confirmationMessage = 'If you leave before submitting, your changes will be lost.';
    //
    //    (e || window.event).returnValue = confirmationMessage; //Gecko + IE
    //    return confirmationMessage; //Gecko + Webkit, Safari, Chrome etc.
    //};

    //window.addEventListener("beforeunload", unloadListener);

    $scope.submitTicket = function(){
        //window.removeEventListener("beforeunload", unloadListener);

        $http.post('/tickets/submit-ticket', {ticket : $scope.ticket }).then(
            function success(response){
                var ticket = response.data;
                $scope.ticket = ticket;
        });
    };
}]);

app.controller('TicketController', function($scope, $http) {
    $scope.ticket = {};
    $scope.comments = [];
    $scope.comment = {body: ""};

    $scope.setTicket = function(ticket) {
        $scope.ticket = ticket;
        $scope.comments = ticket.comments;
    };

    $scope.submitComment = function() {

        if ($scope.comment['body'].trim()) {
            $http.post('/tickets/submit-comment', {comment: $scope.comment, ticketId: $scope.ticket["ticket_id"]}).then(
                function success(response){
                    $scope.comment = {body: ""};
                    $scope.comments = response.data.comments;
                });
        }
    };
});

app.filter('ticketStatus', function () {
    return function(input) {
        switch(input) {
            case 0:
                out = "New";
                break
            case 1:
                out = "Waiting";
                break;
            case 2:
                out = "In Progress";
                break;
            default:
                out = "Closed";
                break;
        }
        return out;
    };
});

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

app.controller('TicketsIndexController', function($scope, StHelper) {
    $scope.statuses = ["New", "Waiting", "In Progress", "Closed"];

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


