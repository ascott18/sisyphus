
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



var saveMessage = function($http, message) {
    $http.post('/messages/save-message', message).then(
        function success(response){
            // TODO: handle this properly - display a little thing that says "Saving" or "Saved"?
            console.log("Saved!", response);
        },
        function error(response){
            // TODO: handle this properly.
            console.log("Not Saved!", response);
        })
};


app.controller('MessageSaver', function($scope, $timeout, $http){
    var save = function(){
        saveMessage($http, $scope.message);
    };

    var debounceSaveUpdates = function(newVal, oldVal) {
        if (newVal != oldVal ) {
            if ($scope.timeout) {
                $timeout.cancel($scope.timeout)
            }
            $scope.timeout = $timeout(save, 1000);  // 1000 = 1 second
        }
    };

    $(window).bind('beforeunload', function(){
        // timeout.cancel returns true if the timer was running when we canceled it,
        // which means that we do need to perform a save.
        if ($scope.timeout && $timeout.cancel($scope.timeout)) {
            save();
        }
    });

    $scope.$watch('message.subject', debounceSaveUpdates);
    $scope.$watch('message.body', debounceSaveUpdates);
});

app.controller('MessagesController', function($scope, $timeout, $http){

    // STAGE CONTROL

    $scope.STAGE_COMPOSE = 1;
    $scope.STAGE_SEND = 2;

    $scope.stage = $scope.STAGE_COMPOSE;

    $scope.setStage = function(stage){
        $scope.stage = stage;
    };



    // MESSAGES

    $scope.selectMessage = function(message){
        $scope.selectedMessage = message;
    };

    $scope.isMessageSelected = function(message){
        return message == $scope.selectedMessage;
    };

    $scope.anyMessageSelected = function(){
        return $scope.selectedMessage && !!$scope.selectedMessage.message_id;
    };

    $scope.reloadMessages = function(after) {
        $scope.messages = [];
        $scope.selectMessage(null);

        $http.get('/messages/all').then(
            function success(response) {
                $scope.messages = response.data;
                if (after) after();
            },
            function error(response) {
                // TODO: handle properly
                console.log("Couldn't get messages", response);
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






    // RECIPIENTS

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


    // Recipient selection functions
    $scope.selectUsersMissingOrders = new selector(function(r){return r.least_num_orders == 0});
    $scope.selectAllUsers = new selector(function(r){return true});
    $scope.selectNoUsers = new selector(function(r){return false});

    // Go fetch all possible recipients of the messages.
    $http.get('/messages/all-recipients').then(
        function success(response) {
            $scope.recipients = response.data;
        },
        function error(response) {
            // TODO: handle properly
            console.log("Couldn't get recipients", response);
        }
    );




})