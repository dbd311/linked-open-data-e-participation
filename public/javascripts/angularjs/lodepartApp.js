var App = angular.module('lodepartApp',
        [
            'react',
            /* services */
            'adminService', 'configService',
            'documentService', 'commentService',
            'searchService', 'userService',
            'statisticsService', 'statisticsAmendmentsService',
            /* controllers*/
            'adminCtrl', 'configCtrl',
            'documentCtrl', 'moreStatisticsCtrl', 'registerCtrl',
            'searchCtrl', 'statisticsCtrl', 'userCtrl',
            'elasticSearchCtrl', 'elasticSearchService',
            /* plugins or directives*/
            'ngMaterial',
            'ckeditor', 'trustedHtml', 'compile', 'nvd3ChartDirectives',
            'ngSanitize',
            'htmlFilter', 'removeHtmlFilter', 'reverse', 'startFrom'
        ], function ($interpolateProvider) {

    $interpolateProvider.startSymbol('[[');
    $interpolateProvider.endSymbol(']]');
});
