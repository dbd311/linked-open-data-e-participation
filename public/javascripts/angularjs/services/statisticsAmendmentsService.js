angular.module('statisticsAmendmentsService', [])

.factory('StatisticsAmendments', function ($http, Config) {
    return {
        getAggregated: function (containerURI) {
            var genericContainerURI = containerURI.substring(0, containerURI.length - 4);
            var query = 'SELECT DISTINCT COUNT(?amend_deletion) as ?deletion COUNT(?amend_insertion) as ?insertion COUNT(?amend_substitution) as ?substitution \
                WHERE{ \
                {?amend_deletion lodep:modifies ?container. \
                FILTER (regex (?container, <' + genericContainerURI + '>)) \
		?amend_deletion a prv:Deletion. \
                ?amend_deletion prv:has_creator ?user.\
                } UNION \
		{?amend_insertion lodep:modifies ?container. \
                FILTER (regex (?container, <' + genericContainerURI + '>)) \
		?amend_insertion a prv:Insertion. \
                ?amend_insertion prv:has_creator ?user.\
                } UNION \
		{?amend_substitution lodep:modifies ?container. \
                FILTER (regex (?container, <' + genericContainerURI + '>)) \
		?amend_substitution a prv:Substitution. \
                ?amend_substitution prv:has_creator ?user.\
                } \
		}';
            var queryUrl = Config.sparqlEndPoint + "?query=" + encodeURIComponent(query) + "&format=json&callback=JSON_CALLBACK";
            return $http.jsonp(queryUrl);
        },
        
        getNoAggregated: function (containerURI,languages) {
            var genericContainerURI = containerURI.substring(0, containerURI.length - 4);
            var filter = Config.createLanguageFilterContainer(genericContainerURI,languages,false,'');
            var query = 'SELECT DISTINCT COUNT(?amend_deletion) as ?deletion COUNT(?amend_insertion) as ?insertion COUNT(?amend_substitution) as ?substitution \
                WHERE{ \
                {?amend_deletion lodep:modifies ?container. '
                + filter +
                ' ?amend_deletion a prv:Deletion. \
                ?amend_deletion  prv:has_creator ?user.\
                } UNION \
                {?amend_insertion lodep:modifies ?container. '
                + filter +
                '   ?amend_insertion a prv:Insertion. \
                ?amend_insertion prv:has_creator ?user.\
                } UNION \
                {?amend_substitution lodep:modifies ?container. '
                + filter +
                '  ?amend_substitution a prv:Substitution. \
                ?amend_substitution prv:has_creator ?user.\
                } \
                }';
            var queryUrl = Config.sparqlEndPoint + "?query=" + encodeURIComponent(query) + "&format=json&callback=JSON_CALLBACK";
            return $http.jsonp(queryUrl);
        },
        
        getByGroupAggregated: function (containerURI) {
            var genericContainerURI = containerURI.substring(0, containerURI.length - 4);
            var query = 'SELECT DISTINCT ?group COUNT(?amend_deletion) as ?deletion COUNT(?amend_insertion) as ?insertion COUNT(?amend_substitution) as ?substitution \
                WHERE{ \
                {?amend_deletion lodep:modifies ?container. \
                FILTER (regex (?container, <' + genericContainerURI + '>)) \
		?amend_deletion a prv:Deletion. \
                ?amend_deletion prv:has_creator ?user.\
                ?user sioc:member_of ?group. } UNION \
		{?amend_insertion lodep:modifies ?container. \
                FILTER (regex (?container, <' + genericContainerURI + '>)) \
		?amend_insertion a prv:Insertion. \
                ?amend_insertion prv:has_creator ?user.\
                ?user sioc:member_of ?group. } UNION \
		{?amend_substitution lodep:modifies ?container. \
                FILTER (regex (?container, <' + genericContainerURI + '>)) \
		?amend_substitution a prv:Substitution. \
                ?amend_substitution prv:has_creator ?user.\
                ?user sioc:member_of ?group. } \
		} ORDER BY ?group';
            var queryUrl = Config.sparqlEndPoint + "?query=" + encodeURIComponent(query) + "&format=json&callback=JSON_CALLBACK";
            return $http.jsonp(queryUrl);
        },
        
        getByGroupNoAggregated: function (containerURI,languages) {
            var genericContainerURI = containerURI.substring(0, containerURI.length - 4);
            var filter = Config.createLanguageFilterContainer(genericContainerURI,languages,false,'');
            var query = 'SELECT DISTINCT ?group COUNT(?amend_deletion) as ?deletion COUNT(?amend_insertion) as ?insertion COUNT(?amend_substitution) as ?substitution \
                WHERE{ \
                {?amend_deletion lodep:modifies ?container. '
                + filter +
                ' ?amend_deletion a prv:Deletion. \
                ?amend_deletion  prv:has_creator ?user.\
                ?user sioc:member_of ?group. } UNION \
                {?amend_insertion lodep:modifies ?container. '
                + filter +
                '   ?amend_insertion a prv:Insertion. \
                ?amend_insertion prv:has_creator ?user.\
                ?user sioc:member_of ?group. } UNION \
                {?amend_substitution lodep:modifies ?container. '
                + filter +
                '  ?amend_substitution a prv:Substitution. \
                ?amend_substitution prv:has_creator ?user.\
                ?user sioc:member_of ?group. } \
                } ORDER BY ?group';
            var queryUrl = Config.sparqlEndPoint + "?query=" + encodeURIComponent(query) + "&format=json&callback=JSON_CALLBACK";
            return $http.jsonp(queryUrl);
        },
        
        getByNationalityAggregated: function (containerURI) {
            var genericContainerURI = containerURI.substring(0, containerURI.length - 4);
            var query =
                'SELECT DISTINCT ?nationality COUNT(?amend_deletion) as ?deletion COUNT(?amend_insertion) as ?insertion COUNT(?amend_substitution) as ?substitution \
                WHERE{ \
                {?amend_deletion   lodep:modifies ?container.\
                FILTER(regex(?container, <' + genericContainerURI + '>))\
                ?amend_deletion   prv:has_creator ?user.\
                ?user   lodep:nationality ?nationality. \
                ?amend_deletion a prv:Deletion.} UNION \
                {?amend_insertion  lodep:modifies ?container.\
                FILTER(regex(?container, <' + genericContainerURI + '>))\
                ?amend_insertion   prv:has_creator ?user.\
                ?user   lodep:nationality ?nationality. \
                ?amend_insertion a prv:Insertion.} UNION \
                {?amend_substitution  lodep:modifies ?container.\
                FILTER(regex(?container, <' + genericContainerURI + '>))\
                ?amend_substitution   prv:has_creator ?user.\
                ?user   lodep:nationality ?nationality. \
                ?amend_substitution a prv:Substitution.}\
                } ORDER BY ?nationality';
            var queryUrl = Config.sparqlEndPoint + "?query=" + encodeURIComponent(query) + "&format=json&callback=JSON_CALLBACK";
            return $http.jsonp(queryUrl);
        },
        
        getByNationalityNoAggregated: function (containerURI,languages) {
            var genericContainerURI = containerURI.substring(0, containerURI.length - 4);
            var filter = Config.createLanguageFilterContainer(genericContainerURI,languages,false,'');
            var query =
                'SELECT DISTINCT ?nationality COUNT(?amend_deletion) as ?deletion COUNT(?amend_insertion) as ?insertion COUNT(?amend_substitution) as ?substitution \
                WHERE{ \
                {?amend_deletion   lodep:modifies ?container.'
                + filter +
                '?amend_deletion   prv:has_creator ?user.\
                ?user   lodep:nationality ?nationality. \
                ?amend_deletion a prv:Deletion.} UNION \
                {?amend_insertion   lodep:modifies ?container.'
                + filter +
                '?amend_insertion   prv:has_creator ?user.\
                ?user   lodep:nationality ?nationality. \
                ?amend_insertion a prv:Insertion.} UNION \
                {?amend_substitution   lodep:modifies ?container.'
                + filter +
                '?amend_substitution   prv:has_creator ?user.\
                ?user   lodep:nationality ?nationality. \
                ?amend_substitution a prv:Substitution.} \
                } ORDER BY ?nationality';
            var queryUrl = Config.sparqlEndPoint + "?query=" + encodeURIComponent(query) + "&format=json&callback=JSON_CALLBACK";
            return $http.jsonp(queryUrl);
        }
    };
});
