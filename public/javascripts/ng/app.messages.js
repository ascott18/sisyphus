
var app = angular.module('sisyphus', ['textAngular'])

app.config(function($interpolateProvider) {
    $interpolateProvider.startSymbol('[[');
    $interpolateProvider.endSymbol(']]');
});

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

app.controller('MessagesController', function($scope){

    // Constants
    $scope.STAGE_COMPOSE = 1;
    $scope.STAGE_SEND = 2;

    $scope.messages = [
        { subject: "Message 1", body: "Hi!", lastSent: new Date().setDate(1)},
        { subject: "Message 2", body: "Hello!", lastSent: new Date().setDate(2)}
    ];

    $scope.stage = $scope.STAGE_COMPOSE;

    $scope.selectedMessage = $scope.messages[1];

    $scope.selectMessage = function(message){
        $scope.selectedMessage = message;
    }

    $scope.isMessageSelected = function(message){
        return message == $scope.selectedMessage;
    }


    $scope.newMessage = function(){
        var message = {
            subject: "New Message",
            isNew: true,
            body: "<h3>EWU Department of Computer Science</h3><p>Here's everything that I have to say to the lazy peons!</p>"
        };

        $scope.messages.push(message);

        $scope.selectMessage(message);
    }




    $scope.recipients = [
        { user_id: 1, name: "Stuart Steiner", selected: false},
        { user_id: 2, name: "Thomas Capaul", selected: false},
        { user_id: 3, name: "Carol Taylor", selected: true},
        { user_id: 4, name: "Dan Tappan", selected: false},
    ];


    $scope.toggleRecipient = function(recipient){
        recipient.selected = !recipient.selected;
    }

    $scope.isRecipientSelected = function(recipient){
        return recipient.selected;
    }
})