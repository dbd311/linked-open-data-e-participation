angular.module('adminService', [])

.factory('Admin', function ($http) {

    return {
        existGraph: function (graphName) {
            return $http.get('/virtuoso/exist-graph?name='.concat(graphName));
        },
        showLinkUpdateTranslation: function () {
            return $http.get('/show-link-update-translation');
        }
    };
});