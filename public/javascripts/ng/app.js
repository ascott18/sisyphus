
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