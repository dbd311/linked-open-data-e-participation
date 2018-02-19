angular.module('trustedHtml', []).directive('trustedHtml', ['$sce', function($sce) {
return {
        require: 'ngModel',
        link: function(scope, element, attrs, ngModel) {
            ngModel.$formatters.push(function(value) {
                function htmlDecode(input){
                    var elem = document.createElement('div');
                    elem.innerHTML = input;
                    return elem.childNodes.length === 0 ? '' : elem.childNodes[0].nodeValue;
                }
                return htmlDecode(value);
            });
        }
    };
}]);