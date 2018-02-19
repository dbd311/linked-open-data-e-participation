angular.module("removeHtmlFilter", []).filter('removeHtmlFilter', function() {
    return function(htmlCode){
        var text = String(htmlCode).replace(/<[^>]+>/gm, '');
        return text.substring(0, 70).concat('...');
    };
});