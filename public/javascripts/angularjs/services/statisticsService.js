angular.module('statisticsService', [])

.factory('Statistics', function ($http, Config) {
    return {
        getAggregated: function (containerURI) {
            var genericContainerURI = containerURI.substring(0, containerURI.length - 4);
            var query = 'SELECT DISTINCT COUNT(?post_yes) as ?yes COUNT(?post_no) as ?no COUNT(?post_mixed) as ?mixed\
                WHERE{ \
                {?post_yes sioc:has_container ?container. \
                FILTER (regex (?container, <' + genericContainerURI + '>)) \
                ?post_yes sioc:has_creator ?user.\
                ?post_yes sioc:note "yes"^^rdfs:Literal.} UNION \
                {?post_no sioc:has_container ?container. \
                FILTER (regex (?container, <' + genericContainerURI + '>)) \
                ?post_no sioc:has_creator ?user.\
                ?post_no sioc:note "no"^^rdfs:Literal.} UNION \
                {?post_mixed sioc:has_container ?container. \
                FILTER (regex (?container, <' + genericContainerURI + '>)) \
                ?post_mixed sioc:has_creator ?user.\
                ?post_mixed sioc:note "mixed"^^rdfs:Literal.} \
                }';
            var queryUrl = Config.sparqlEndPoint + "?query=" + encodeURIComponent(query) + "&format=json&callback=JSON_CALLBACK";
            return $http.jsonp(queryUrl);
        },
        getNoAggregated: function (containerURI,languages) {
            var genericContainerURI = containerURI.substring(0, containerURI.length - 4);
            var filter = Config.createLanguageFilterContainer(genericContainerURI,languages,false,'');
            var query =
                'SELECT DISTINCT COUNT(?post_yes) as ?yes COUNT(?post_no) as ?no  COUNT(?post_mixed) as ?mixed  \
                WHERE{ \
                {?post_yes   sioc:has_container ?container.'
                + filter +
                '?post_yes   sioc:has_creator ?user.\
                ?post_yes sioc:note "yes"^^rdfs:Literal.} UNION \
                {?post_no   sioc:has_container ?container.'
                + filter +
                '?post_no   sioc:has_creator ?user.\
                ?post_no sioc:note "no"^^rdfs:Literal.} UNION \
                {?post_mixed   sioc:has_container ?container.'
                + filter +
                '?post_mixed   sioc:has_creator ?user.\
                ?post_mixed sioc:note "mixed"^^rdfs:Literal.}\
                }';
            var queryUrl = Config.sparqlEndPoint + "?query=" + encodeURIComponent(query) + "&format=json&callback=JSON_CALLBACK";
            return $http.jsonp(queryUrl);
        },
        getByGroupAggregated: function (containerURI) {
            var genericContainerURI = containerURI.substring(0, containerURI.length - 4);
            var query = 'SELECT DISTINCT ?group COUNT(?post_yes) as ?yes COUNT(?post_no) as ?no COUNT(?post_mixed) as ?mixed\
                WHERE{ \
                {?post_yes sioc:has_container ?container. \
                FILTER (regex (?container, <' + genericContainerURI + '>)) \
                ?post_yes sioc:has_creator ?user.\
                ?user sioc:member_of ?group. \
                ?post_yes sioc:note "yes"^^rdfs:Literal.} UNION \
                {?post_no sioc:has_container ?container. \
                FILTER (regex (?container, <' + genericContainerURI + '>)) \
                ?post_no sioc:has_creator ?user.\
                ?user sioc:member_of ?group. \
                ?post_no sioc:note "no"^^rdfs:Literal.} UNION \
                {?post_mixed sioc:has_container ?container. \
                FILTER (regex (?container, <' + genericContainerURI + '>)) \
                ?post_mixed sioc:has_creator ?user.\
                ?user sioc:member_of ?group. \
                ?post_mixed sioc:note "mixed"^^rdfs:Literal.} \
                } ORDER BY ?group';
            var queryUrl = Config.sparqlEndPoint + "?query=" + encodeURIComponent(query) + "&format=json&callback=JSON_CALLBACK";
            return $http.jsonp(queryUrl);
        },
        getByGroupNoAggregated: function (containerURI,languages) {
            var genericContainerURI = containerURI.substring(0, containerURI.length - 4);
            var filter = Config.createLanguageFilterContainer(genericContainerURI,languages,false,'');
            var query =
                'SELECT DISTINCT ?group COUNT(?post_yes) as ?yes COUNT(?post_no) as ?no  COUNT(?post_mixed) as ?mixed  \
                WHERE{ \
                {?post_yes   sioc:has_container ?container.'
                + filter +
                '?post_yes   sioc:has_creator ?user.\
                ?user sioc:member_of ?group. ?post_yes sioc:note "yes"^^rdfs:Literal.} UNION \
                {?post_no   sioc:has_container ?container.'
                + filter +
                '?post_no   sioc:has_creator ?user.\
                ?user sioc:member_of ?group. ?post_no sioc:note "no"^^rdfs:Literal.} UNION \
                {?post_mixed   sioc:has_container ?container.'
                + filter +
                '?post_mixed   sioc:has_creator ?user.\
                ?user sioc:member_of ?group. ?post_mixed sioc:note "mixed"^^rdfs:Literal.}\
                } ORDER BY ?group';
            var queryUrl = Config.sparqlEndPoint + "?query=" + encodeURIComponent(query) + "&format=json&callback=JSON_CALLBACK";
            return $http.jsonp(queryUrl);
        },
        getByNationalityAggregated: function (containerURI) {
            var genericContainerURI = containerURI.substring(0, containerURI.length - 4);
            var query =
                'SELECT DISTINCT ?nationality COUNT(?post_yes) as ?yes COUNT(?post_no) as ?no COUNT(?post_mixed) as ?mixed  \
                WHERE{ \
                {?post_yes   sioc:has_container ?container.\
                FILTER(regex(?container, <' + genericContainerURI + '>))\
                ?post_yes   sioc:has_creator ?user.\
                ?user   lodep:nationality ?nationality. ?post_yes sioc:note "yes"^^rdfs:Literal.} UNION \
                {?post_no   sioc:has_container ?container.\
                FILTER(regex(?container, <' + genericContainerURI + '>))\
                ?post_no   sioc:has_creator ?user.\
                ?user   lodep:nationality ?nationality. ?post_no sioc:note "no"^^rdfs:Literal.} UNION \
                {?post_mixed   sioc:has_container ?container.\
                FILTER(regex(?container, <' + genericContainerURI + '>)) \
                ?post_mixed   sioc:has_creator ?user. \
                ?user   lodep:nationality ?nationality. ?post_mixed sioc:note "mixed"^^rdfs:Literal.} \
                } ORDER BY ?nationality';
            var queryUrl = Config.sparqlEndPoint + "?query=" + encodeURIComponent(query) + "&format=json&callback=JSON_CALLBACK";
            return $http.jsonp(queryUrl);
        },
        
        getByNationalityNoAggregated: function (containerURI,languages) {
            var genericContainerURI = containerURI.substring(0, containerURI.length - 4);
            var filter = Config.createLanguageFilterContainer(genericContainerURI, languages,false,'');
            var query =
                'SELECT DISTINCT ?nationality COUNT(?post_yes) as ?yes COUNT(?post_no) as ?no  COUNT(?post_mixed) as ?mixed  \
                WHERE{ \
                {?post_yes   sioc:has_container ?container.'
                + filter +
                '?post_yes   sioc:has_creator ?user.\
                ?user   lodep:nationality ?nationality. ?post_yes sioc:note "yes"^^rdfs:Literal.} UNION \
                {?post_no   sioc:has_container ?container.'
                + filter +
                '?post_no   sioc:has_creator ?user.\
                ?user   lodep:nationality ?nationality. ?post_no sioc:note "no"^^rdfs:Literal.} UNION \
                {?post_mixed   sioc:has_container ?container.'
                + filter +
                '?post_mixed   sioc:has_creator ?user.\
                ?user   lodep:nationality ?nationality. ?post_mixed sioc:note "mixed"^^rdfs:Literal.}\
                } ORDER BY ?nationality';
            var queryUrl = Config.sparqlEndPoint + "?query=" + encodeURIComponent(query) + "&format=json&callback=JSON_CALLBACK";
            return $http.jsonp(queryUrl);
        }
    };
});