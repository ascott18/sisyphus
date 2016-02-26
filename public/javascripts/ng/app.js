
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

app.run(function($rootScope){
    $rootScope.espanol = function(){
        $rootScope.espanol = function(){};
        $("body").append(
            '<div id="google_translate_element" style="display: none;"></div>' +
            '<script type="text/javascript">' +
            'function googleTranslateElementInit() {' +
            '    new google.translate.TranslateElement({pageLanguage: "en", includedLanguages: "en,es",' +
            '        layout: google.translate.TranslateElement.FloatPosition.BOTTOM_RIGHT, autoDisplay: false}, "google_translate_element");' +
            ' console.log($(".goog-te-combo").val("es")); } </script>' +
            '<style>body{top: 0 !important;} .skiptranslate{display: none;}</style>' +
            '<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>'
            );
    }
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

app.service('BreadcrumbService', function($rootScope){
    $rootScope.breadcrumbAppends = [];

    return {
        push: function(crumb){
            $rootScope.breadcrumbAppends.push(crumb);
        },

        clear: function(){
            $rootScope.breadcrumbAppends = [];
        }
    };
});

app.service('StHelper', function($http){
    return {
        callServer: function(tableState, config, $scope ){
            var pagination = tableState.pagination;
            var start = pagination.start || 0;
            var end = pagination.number || 10;
            var page = (start/end) + 1;

            config.method = 'GET';
            config.params = config.params || {};
            config.params.table_state = tableState;
            config.params.page = page;
            config.headers = {
                'X-ST-PIPE': true
            };

            $http(config).then(function(response){
                tableState.pagination.numberOfPages = response.data.last_page;                    // update number of pages with laravel response
                tableState.pagination.number = response.data.per_page;                            // update entries per page with laravel response
                $scope.displayed = response.data.data;                                              // save laravel response data
            });
        },

        reset: function(stCtrl){
            if (stCtrl)
            {
                stCtrl.tableState().pagination.start = 0;
                stCtrl.pipe();
            }
        }
    };
});

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

        // string.prototype.repeat doesn't work in IE - its part of ecmascript6.
        var zeros = "";
        while (n-- > input.length){
            zeros += "0";
        }

        return (zeros + input);
    };
});

// Automatically adds text to an element when it doesn't have children.
app.directive('emptyPlaceholder', ['$http',
    function($http){
       return {
           link: function(scope, element, attr) {
               var text = attr.emptyPlaceholder || "No results found.";
               var table = $(element);

               var hasStartedRequest = false;
               var hasFinishedRequest = false;
               scope.$watchGroup(
                   [
                       function () { return table.find("tbody").children().length; },
                       function () {
                           for (var i = 0; i < $http.pendingRequests.length; i++){
                               if ($http.pendingRequests[i].headers['X-ST-PIPE'])
                                   return true;
                           }
                           return false;
                       }
                   ],
                   function (newValues, oldValues) {
                       if (newValues[1] && !hasStartedRequest) {
                           hasStartedRequest = true;
                       }
                       else if (!newValues[1] && hasStartedRequest) {
                           hasFinishedRequest = true;
                       }

                       table.siblings(".empty-table-placeholder").remove();
                       if (newValues[0] == 0) // The number of children that the table has currently.
                       {
                           if ( !hasFinishedRequest){
                               table.after("<h2 class='text-muted empty-table-placeholder'>Loading...</h2>");
                           }
                           else {
                               table.after("<h2 class='text-muted empty-table-placeholder'>" +  text + "</h2>");
                           }
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
app.filter('momentObj', function () {
    return function (value, format) {
        return moment(value);
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
            });
        }
    };
}]);

app.run(['$templateCache', function($templateCache) {
    $templateCache.put('courseWithListings',
        '[[course.listings[0].department]] [[course.listings[0].number | zpad:3]]-[[course.listings[0].section | zpad:2]]' +
        '<ng-transclude></ng-transclude>' +
        '<small class="text-muted" ng-if="course.listings.length > 1"> <br> XL as [[getXlString(course)]] </small>');
}]);

app.directive('courseWithListings', function($filter) {
    var getXlString = function(course){
        var listings = course.listings;
        listings = $filter('filter')(listings, {'listing_id': '!' + listings[0].listing_id});
        listings = $filter('orderBy')(listings, ['department', 'number', 'section']);

        var str = "";

        for(var i = 0; i < listings.length; i++){
            var listing = listings[i];
            var prev = i > 0 ? listings[i-1] : null;

            if (!prev || listing.department != prev.department || listing.number != prev.number){
                if (i != 0) str += '; ';
                str += listing.department + ' ' + $filter('zpad')(listing.number, 3) + '-';
            }
            else if (i != 0) {
                str += ', '
            }
            str += $filter('zpad')(listing.section, 2);
        }

        return str;
    };

    return {
        restrict: 'E',
        transclude: true,
        scope: {
            course: '='
        },
        templateUrl: 'courseWithListings',
        link: function(scope, element, attrs){
            scope.getXlString = getXlString
        }
    }
});




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


app.controller('HelpModalController', ['$scope', 'HelpService', function($scope, HelpService) {

    $scope.SELECT_OPTION = HelpService.SELECT_OPTION;
    $scope.COURSE_OPTION = HelpService.COURSE_OPTION;
    $scope.BOOK_OPTION = HelpService.BOOK_OPTION;

    $scope.stage = $scope.SELECT_OPTION;

    $scope.showModal = false;
    $scope.options = [];
    $scope.href = "/tickets/create/";
    $scope.title = HelpService.title;

    $scope.getStage = function() {
        return $scope.stage;
    };

    $scope.toggleModal = function () {
        $scope.showModal = !$scope.showModal;
    };

    $scope.selectOption = function (selected) {
        if (selected.options && selected.options.length != 0) {
            $scope.options = selected.options;
            $scope.stage = selected.optionType;
        }
        else {
            $scope.createTicket(selected);
        }
    };

    $scope.createTicket = function (ticketInfo) {
        window.location.href = $scope.href + "?url=" + ticketInfo.url + "&title=" + ticketInfo.title + "&department=" + ticketInfo.department;
    }
}]);

app.directive('modal', ['HelpService', function(HelpService) {
    return {
        templateUrl: '/javascripts/ng/templates/helpModal.html',
        restrict: 'EA',
        replace: true,
        scope: false,
        controller : 'HelpModalController',
        link: function postLink(scope, element, attrs) {
            scope.title = attrs.title;
            scope.HelpService = HelpService;

            scope.$watch(attrs.visible, function(value){
                if(value == true)
                    $(element).modal('show');
                else
                    $(element).modal('hide');
            });

            $(element).on('shown.bs.modal', function(){
                scope.$apply(function(){
                    scope.$parent[attrs.visible] = true;
                });
            });

            $(element).on('hidden.bs.modal', function(){
                scope.$apply(function(){
                    scope.$parent[attrs.visible] = false;
                });
                scope.stage = scope.SELECT_OPTION;
            });
        }
    };
}]);

app.factory("HelpService", function() {

    var defaultOptions = [{header: "Report a Problem", body: "Does something not look right? Let us know."},
                          {header: "Ask a Question", body: "Need some help? Submit a question."}];

    var SELECT_OPTION = 0;
    var COURSE_OPTION = 1;
    var BOOK_OPTION = 2;


    var options = defaultOptions;

    var updateOptions = function(option) {
        this.options = defaultOptions.push(option);
    };

    var addCourseHelpOption = function(courses) {
        var selectCourseHelpOption =  {header: "Report Error in Course List",
            body: "Select this option if a course you are teaching is not listed here or a course you are not teaching is listed.",
            optionType: COURSE_OPTION};

        var options = [];

        for (var index in courses) {
            var option = {};
            var course = courses[index];
            option['course'] = course;
            option['url'] = "/courses/details/" + course.course_id;
            option['title'] = "test";
            option['department'] = course.listings[0].department;
            options.push(option);
        }

        var option = {};
        option['body'] = "Missing course";
        option['url'] = null;
        options.push(option);

        selectCourseHelpOption['options'] = options;

        updateOptions(selectCourseHelpOption);
    };

    var addBookHelpOption = function(books) {
        var selectBooksHelpOptions = [{header: "Report Problem with Book",
                                       body: "Is there a problem with a book? Please report it.",
                                       optionType: BOOK_OPTION}];

        var options = [];

        for (var index in books) {
            var option = {};
            var book = books[index];
            option['book'] = book;
            option['url'] = "/books/details/" + book.book_id;
            option['title'] = "test";
            options.push(option);
        }

        selectBooksHelpOptions['options'] = options;

        updateOptions(selectBooksHelpOptions);
    };


    var service = {options              : options,
                   updateOptions        : updateOptions,
                   addCourseHelpOption  : addCourseHelpOption,
                   addBookHelpOption    : addBookHelpOption,
                   SELECT_OPTION        : SELECT_OPTION,
                   COURSE_OPTION       : COURSE_OPTION,
                   BOOK_OPTION        : BOOK_OPTION};
    return service;
});
