angular.module('compile', []).directive('compile', ['$compile', function ($compile) {
    return function(scope, element, attrs) {
        scope.$watch(
            function(scope) {
                return scope.$eval(attrs.compile);
            },
            function(value) {
                if (typeof value === "string"){
                    value = value.replace(/(^|\s)(#[a-z\d-]+)/ig, "$1<span class=\"highlighted\" ng-click=\"newHashtag('$2')\">$2</span>");
                }
                element.html(value);
                $compile(element.contents())(scope);
            }
        );
    };
}]);