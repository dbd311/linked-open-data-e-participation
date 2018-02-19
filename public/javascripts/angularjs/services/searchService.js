angular.module('searchService', [])
    .factory('Search', function (Config, $http) {

    return {
        getSuggestions: function (data) {
                var query = "SELECT  DISTINCT ?concept ?concept_name  WHERE {";
                    query += "{?concept skos:prefLabel ?concept_name. FILTER (LANG(?concept_name)='" + data.lang + "' && REGEX(lcase(?concept_name),  lcase('^" + data.pattern.trim() + "')))} UNION";
                    query += "{?concept skos:altLabel ?concept_name. FILTER (LANG(?concept_name)='" + data.lang + "' && REGEX(lcase(?concept_name),  lcase('^" + data.pattern.trim() + "')))}";
                    query += "} limit 4";
            var queryUrl = Config.sparqlEndPoint + "?query=" + encodeURIComponent(query) + "&format=json&callback=JSON_CALLBACK";
            return $http.jsonp(queryUrl);
        },
        getDomainNames: function (lang_code) {
            return $http.get('/get-domains/'.concat(lang_code));
        },
        getThesaurusNames: function (lang_code, domain) {
            return $http.get('/get-thesaurus-names/'.concat(lang_code).concat('/').concat(domain));
        },
        getConceptNames: function (lang_code, thesaurus) {
            return $http.get('/get-concept-names/'.concat(lang_code).concat('/').concat(thesaurus));
        },
        getRelatedTermAndNarrowerNames: function (lang_code, concept) {
            return $http.get('/get-narrower-names/'.concat(lang_code).concat('/').concat(concept));
        },
        getNarrowerNames: function (lang_code, narrower) {
            return $http.get('/get-narrower-names-of-narrower-names/'.concat(lang_code).concat('/').concat(narrower));
        },
        hasChildNarrowerTerm: function (lang_code, narrower) {
            return $http.get('/has-child-narrower-term/'.concat(lang_code).concat('/').concat(narrower));
        },
        getProcedureYears: function () {
            return $http.get('get-procedure-years');
        }
    };
});
