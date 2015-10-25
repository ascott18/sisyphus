
var app = angular.module('sisyphus', ['sisyphus.helpers', 'angularUtils.directives.dirPagination', 'textAngular'])

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

app.controller('MessagesController', function($scope, $timeout, $http){

    // Constants
    $scope.STAGE_COMPOSE = 1;
    $scope.STAGE_SEND = 2;

    $scope.stage = $scope.STAGE_COMPOSE;

    $scope.selectMessage = function(message){
        $scope.selectedMessage = message;
    };

    $scope.isMessageSelected = function(message){
        return message == $scope.selectedMessage;
    };

    $scope.anyMessageSelected = function(){
        return !!$scope.selectedMessage.message_id;
    };

    $scope.reloadingMessages = 0;
    $scope.reloadMessages = function(after) {
        $scope.messages = [];
        $scope.selectMessage(null);
        $scope.reloadingMessages++;

        $http.get('/messages/all').then(
            function success(response) {
                $scope.messages = response.data;
                if (after) after();
                $scope.reloadingMessages--;
            },
            function error(response) {
                // TODO: handle properly
                console.log("Couldn't get messages", response);
                $scope.reloadingMessages--;
            }
        );
    };
    $scope.reloadMessages();


    $scope.deleteMessage = function(message){
        $http.post('/messages/delete-message', message).then(
            function success(response){
                var index = $scope.messages.indexOf(message);
                if (index > -1)
                {
                    $scope.messages.splice(index, 1);
                    if ($scope.isMessageSelected(message))
                        $scope.selectMessage($scope.messages[Math.min(index-1, $scope.messages.length)]);
                }
                else
                {
                    $scope.reloadMessages();
                }
            },
            function error(response){
                // TODO: handle this properly.
                console.log("Not Deleted!", response);
                $scope.reloadMessages();
            })
    };

    $scope.newMessage = function(message){
        $http.post('/messages/create-message', message).then(
            function success(response){
                var message = response.data;

                message['isNew'] = true;
                $scope.messages.push(message);
                $scope.selectMessage(message);

            },
            function error(response){
                // TODO: handle this properly.
                console.log("Not Added!", response);
            });
    };



    var timeout = null;
    var saveUpdates = function() {
        if (! $scope.selectedMessage['message_id'] ) return;

        $http.post('/messages/save-message', $scope.selectedMessage).then(
            function success(response){
                // TODO: handle this properly - display a little thing that says "Saving" or "Saved".
                console.log("Saved!", response);
            },
            function error(response){
                // TODO: handle this properly.
                console.log("Not Saved!", response);
            })
    };

    var debounceSaveUpdates = function(newVal, oldVal) {
        if (newVal != oldVal) {
            if (timeout) {
                $timeout.cancel(timeout)
            }
            timeout = $timeout(saveUpdates, 1000);  // 1000 = 1 second
        }
    };

    $(window).bind('beforeunload', function(){
        saveUpdates();
    });

    $scope.$watch('selectedMessage.subject', debounceSaveUpdates);
    $scope.$watch('selectedMessage.body', debounceSaveUpdates);





    $scope.recipients = [];

    $scope.toggleRecipient = function(recipient){
        recipient.selected = !recipient.selected;
    };

    $scope.isRecipientSelected = function(recipient){
        return recipient.selected;
    };

    function selector(predicate){
        return function() {angular.forEach($scope.recipients, function(recipient){
            recipient.selected = predicate(recipient);
        })};
    }

    $scope.selectUsersMissingOrders = new selector(function(r){return r.least_num_orders == 0});
    $scope.selectAllUsers = new selector(function(r){return true});
    $scope.selectNoUsers = new selector(function(r){return false});

    $http.get('/messages/all-users').then(
        function success(response) {
            $scope.recipients = response.data;
        },
        function error(response) {
            // TODO: handle properly
            console.log("Couldn't get users", response);
        }
    );




})