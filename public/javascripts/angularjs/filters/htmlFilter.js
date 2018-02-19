angular.module("htmlFilter", []).filter("htmlFilter", ['$sce', function($sce) {
    return function(htmlCode){
        return $sce.trustAsHtml(htmlCode);
    };
}]);