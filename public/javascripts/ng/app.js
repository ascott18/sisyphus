
var app = angular.module('sisyphus.helpers', []);

app.config(function($interpolateProvider) {
    $interpolateProvider.startSymbol('[[');
    $interpolateProvider.endSymbol(']]');
});

app.config(function($httpProvider) {
    $httpProvider.defaults.headers.common = { 'X-Requested-With' : 'XMLHttpRequest' }
});



angular.module('filters', []).filter('zpad', function() {
    return function(input, n) {
        if(input === undefined)
            input = "";
        if(input.length >= n)
            return input;
        var zeros = "0".repeat(n);
        return (zeros + input).slice(-1 * n)
    };
});


app.directive('ngConfirmClick', [
function(){
    return {
        link: function (scope, element, attr) {
            var msg = attr.ngConfirmClickMessage || "Are you sure?";
            var clickAction = attr.ngConfirmClick;
            element.bind('click',function (event) {
                if ( window.confirm(msg) ) {
                    scope.$eval(clickAction)
                }
            });
        }
    };
}]);

app.directive('ngSpinner', ['$http', '$rootScope', function ($http, $rootScope){
    return {
        link: function (scope, elm, attrs)
        {
            $rootScope.spinnerActive = false;
            scope.isLoading = function () {
                return $http.pendingRequests.length > 0;
            };

            scope.$watch(scope.isLoading, function (loading)
            {
                $rootScope.spinnerActive = loading;
                if(loading){
                    elm.removeClass('ng-hide');
                }else{
                    elm.addClass('ng-hide');
                }
            });
        }
    };
}]);





// GENERIC ERROR HANDLING
// This is from http://www.codelord.net/2014/06/25/generic-error-handling-in-angularjs/
// Licensed under Apache 2.0

var HEADER_NAME = 'MyApp-Handle-Errors-Generically';
var specificallyHandleInProgress = false;

app.factory('RequestsErrorHandler', ['$q', '$rootScope', function($q, $rootScope) {
    return {
        // --- The user's API for claiming responsiblity for requests ---
        specificallyHandled: function(specificallyHandledBlock) {
            specificallyHandleInProgress = true;
            try {
                return specificallyHandledBlock();
            } finally {
                specificallyHandleInProgress = false;
            }
        },

        // --- Response interceptor for handling errors generically ---
        responseError: function(rejection) {
            var shouldHandle = (rejection && rejection.config && rejection.config.headers
            && rejection.config.headers[HEADER_NAME]);

            if (shouldHandle) {
                $rootScope.appErrors = $rootScope.appErrors || [];
                console.log(rejection);
                if (rejection.data && rejection.data.success == false && rejection.data.message )
                {
                    $rootScope.appErrors.push({
                        messages: [rejection.data.message]
                    });
                }
                else if (rejection.data.response && rejection.data.response.message )
                {
                    $rootScope.appErrors.push({
                        messages: [rejection.data.response.message]
                    });
                }
                else
                {
                    // Unprocessable Entity - response from Laravel's validator
                    if (rejection.status == 422) {
                        var messages = [];
                        for (var key in rejection.data){
                            if (rejection.data.hasOwnProperty(key)) {
                                var badVar = rejection.data[key];
                                for (var i = 0; i < badVar.length; i++){
                                    messages.push(badVar[i]);
                                }
                            }
                        }
                        $rootScope.appErrors.push({
                            messages: messages
                        });
                    }
                    else{
                        $rootScope.appErrors.push({
                            messages: [rejection.statusText]
                        });
                    }
                }
            }

            return $q.reject(rejection);
        }
    };
}]);

app.config(['$provide', '$httpProvider', function($provide, $httpProvider) {
    $httpProvider.interceptors.push('RequestsErrorHandler');

    // --- Decorate $http to add a special header by default ---

    function addHeaderToConfig(config) {
        config = config || {};
        config.headers = config.headers || {};

        // Add the header unless user asked to handle errors himself
        if (!specificallyHandleInProgress) {
            config.headers[HEADER_NAME] = true;
        }

        return config;
    }

    // The rest here is mostly boilerplate needed to decorate $http safely
    $provide.decorator('$http', ['$delegate', function($delegate) {
        function decorateRegularCall(method) {
            return function(url, config) {
                return $delegate[method](url, addHeaderToConfig(config));
            };
        }

        function decorateDataCall(method) {
            return function(url, data, config) {
                return $delegate[method](url, data, addHeaderToConfig(config));
            };
        }

        function copyNotOverriddenAttributes(newHttp) {
            for (var attr in $delegate) {
                if (!newHttp.hasOwnProperty(attr)) {
                    if (typeof($delegate[attr]) === 'function') {
                        newHttp[attr] = function() {
                            return $delegate[attr].apply($delegate, arguments);
                        };
                    } else {
                        newHttp[attr] = $delegate[attr];
                    }
                }
            }
        }

        var newHttp = function(config) {
            return $delegate(addHeaderToConfig(config));
        };

        newHttp.get = decorateRegularCall('get');
        newHttp.delete = decorateRegularCall('delete');
        newHttp.head = decorateRegularCall('head');
        newHttp.jsonp = decorateRegularCall('jsonp');
        newHttp.post = decorateDataCall('post');
        newHttp.put = decorateDataCall('put');

        copyNotOverriddenAttributes(newHttp);

        return newHttp;
    }]);
}]);

