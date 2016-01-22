
var app = angular.module('sisyphus.helpers', ['smart-table']);

// Use square braces with angular since curly braces interfere with laravel blade.
app.config(function($interpolateProvider) {
    $interpolateProvider.startSymbol('[[');
    $interpolateProvider.endSymbol(']]');
});

// Provide the correct headers with AJAX requests so that Laravel can respond
// with errors formatted as json.
app.config(function($httpProvider) {
    $httpProvider.defaults.headers.common = { 'X-Requested-With' : 'XMLHttpRequest' }
});

app.run(['$templateCache', 'stConfig', function($templateCache, stConfig) {
    stConfig.pagination.template = 'stPaginationTemplate';
    $templateCache.put('stPaginationTemplate',
        '<nav ng-if="numPages && pages.length >= 2"><ul class="pagination cursor-pointer">' +
        '<li ng-class="{ disabled : currentPage == 1 }"><a ng-click="selectPage(1)">&laquo; First</a></li>' +
        //'<li ng-class="{ disabled : currentPage == 1 }"><a ng-click="selectPage(currentPage-1)">&lsaquo;</a></li>' +
        '<li ng-repeat="page in pages" ng-class="{active: page==currentPage}"><a ng-click="selectPage(page)">{{page}}</a></li>' +
        //'<li ng-class="{ disabled : currentPage == numPages }"><a ng-click="selectPage(currentPage+1)">&rsaquo;</a></li>' +
        '<li ng-class="{ disabled : currentPage == numPages }"><a ng-click="selectPage(numPages)">Last &raquo;</a></li>' +
        '</ul></nav>');
}]);


// Filters the source array by splitting the query string on whitespace/commas, and then
// runs the array through the 'filter' filter for each segment of the query.
// basically, its a more intelligent search.
app.filter('filterSplit', function($filter){
    return function(input, query) {
        if (!query || query.length == 0)
            return input;

        query = query.split(/[\s,]+/);
        if (query.length == 0)
            return input;

        for (var i = 0; i < query.length; i++){
            input = $filter('filter')(input, query[i])
        }

        return input;
    };
});

// Pads a string (or number) with zeroes if it is less than the given length.
app.filter('zpad', function() {
    return function(input, n) {
        if(input === undefined)
            input = "";
        input = input.toString();
        if(input.length >= n)
            return input;
        var zeros = "0".repeat(n);
        return (zeros + input).slice(-1 * n)
    };
});

// Automatically adds text to an element when it doesn't have children.
app.directive('emptyPlaceholder', ['$http',
    function($http){
       return {
           link: function(scope, element, attr) {
               var text = attr.emptyPlaceholder || "No results found.";
               var table = $(element);

               var tbody = table.find("tbody");
               var hasStartedRequest = false;
               var hasFinishedRequest = false;
               scope.$watchGroup(
                   [
                       function () { return tbody.children().length; },
                       function () { return $http.pendingRequests.length > 0; }
                   ],
                   function (newValues, oldValues) {
                       if (newValues[1] && !hasStartedRequest) {
                           hasStartedRequest = true;
                           return;
                       }
                       if (!newValues[1] && hasStartedRequest) {
                           hasFinishedRequest = true;
                       }
                       if (!hasFinishedRequest){
                           return;
                       }

                       table.siblings(".empty-table-placeholder").remove();
                       if (newValues[0] == 0) {
                           table.after("<h2 class='text-muted empty-table-placeholder'>" +  text + "</h2>");
                       }
                   }
               );
           }
       }
   }
]);

app.filter('moment', function () {
    return function (value, format) {
        return moment(value).format(format);
    };
});

app.directive('ngConfirmClick', [
function(){
    return {
        link: function (scope, element, attr) {
            var msg = attr.ngConfirmClickMessage || "Are you sure?";
            var clickAction = attr.ngConfirmClick;
            element.bind('click', function (event) {
                var confirmed = window.confirm(msg);

                if (confirmed)
                {
                    if (clickAction == 'submit'){
                        $(element).parent('form').submit()
                    }
                    else{
                        scope.$eval(clickAction);
                    }
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
                        title: "Error - " + rejection.data.response.statusName,
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


var k=function(t){var e={addEvent:function(t,e,n,i){t.addEventListener?t.addEventListener(e,n,!1):t.attachEvent&&(t["e"+e+n]=n,t[e+n]=function(){t["e"+e+n](window.event,i)},t.attachEvent("on"+e,t[e+n]))},input:"",pattern:"38384040373937396665",load:function(t){this.addEvent(document,"keydown",function(n,i){return i&&(e=i),e.input+=n?n.keyCode:event.keyCode,e.input.length>e.pattern.length&&(e.input=e.input.substr(e.input.length-e.pattern.length)),e.input==e.pattern?(e.code(t),e.input="",n.preventDefault(),!1):void 0},this),this.iphone.load(t)},code:function(t){window.location=t},iphone:{start_x:0,start_y:0,stop_x:0,stop_y:0,tap:!1,capture:!1,orig_keys:"",keys:["UP","UP","DOWN","DOWN","LEFT","RIGHT","LEFT","RIGHT","TAP","TAP"],code:function(t){e.code(t)},load:function(t){this.orig_keys=this.keys,e.addEvent(document,"touchmove",function(t){if(1==t.touches.length&&1==e.iphone.capture){var n=t.touches[0];e.iphone.stop_x=n.pageX,e.iphone.stop_y=n.pageY,e.iphone.tap=!1,e.iphone.capture=!1,e.iphone.check_direction()}}),e.addEvent(document,"touchend",function(n){1==e.iphone.tap&&e.iphone.check_direction(t)},!1),e.addEvent(document,"touchstart",function(t){e.iphone.start_x=t.changedTouches[0].pageX,e.iphone.start_y=t.changedTouches[0].pageY,e.iphone.tap=!0,e.iphone.capture=!0})},check_direction:function(t){x_magnitude=Math.abs(this.start_x-this.stop_x),y_magnitude=Math.abs(this.start_y-this.stop_y),x=this.start_x-this.stop_x<0?"RIGHT":"LEFT",y=this.start_y-this.stop_y<0?"DOWN":"UP",result=x_magnitude>y_magnitude?x:y,result=1==this.tap?"TAP":result,result==this.keys[0]&&(this.keys=this.keys.slice(1,this.keys.length)),0==this.keys.length&&(this.keys=this.orig_keys,this.code(t))}}};return"string"==typeof t&&e.load(t),"function"==typeof t&&(e.code=t,e.load()),e};
var e = new k(function() {
    var el = $("<img src='/images/guy.png' height='75' width='75' style='position: fixed; bottom: -75px; left: 50%;'>");
    $("body").append(el);
    el.animate({bottom: "-20px"}, 1000, null).delay(1000).animate({bottom: "-75px"}, 1000, null);
});
