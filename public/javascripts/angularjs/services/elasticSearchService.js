/**
 * Services for ElasticSearch
 * @author Duy Dinh <dinhbaduy@gmail.com>
 * @date 25/07/2016
 */
angular.module('elasticSearchService', [])
        .factory('ElasticSearch', function ($http, $filter) {
            return {
                indexDocuments: function (dateFrom, dateTo) {
                    var url = '/services/document-indexing';

                    if (dateFrom) {
                        url += '?dateFrom=' + dateFrom;

                        if (dateTo) {
                            url += '&dateTo=' + dateTo;
                        } else {
                            var dateNow = $filter('date')(new Date(), 'yyyy-MM-dd');
                            url += '&dateTo=' + dateNow;
                        }
                    }
//                    alert(url);
                    return $http.post(url);
                },
                loadIndices: function () {
                    var url = '/services/get-indices';
                    return $http.get(url);
                },
                runQuery: function (query, lang) {
                    var url = '/runQuery?q=' + query + '&lang=' + lang;
                    return $http.get(url);
                }
            };
        });