
var app = angular.module('sisyphus.helpers', [])

app.config(function($interpolateProvider) {
    $interpolateProvider.startSymbol('[[');
    $interpolateProvider.endSymbol(']]');
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

app.directive('ngSpinner', ['$http', '$rootScope' ,function ($http, $rootScope){
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
