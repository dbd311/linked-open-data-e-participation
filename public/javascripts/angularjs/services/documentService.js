angular.module('documentService', [])

        .factory('Document', function ($http, Config) {
            return {
                loadDocument: function (path) {
                    return $http.get('/load-document?path=' + path);
                },
                loadMetaData: function (docId, lang) {
                    return $http.get('/load-metadata/'.concat(docId).concat('/').concat(lang));
                },
                loadThemes: function (docId, lang) {
                    return $http.get('/get-themes/'.concat(docId).concat('/').concat(lang));
                },
                get: function (criteria) {
                    return $http.get('/get-documents/'.concat(criteria).concat("?lang=" + Config.lang));
                },
                loadAnnexes: function (folder) {
                    return $http.get('/load-annexes/'.concat(folder));
                },
                filter: function (lang, criteria, themes, dateFrom, dateTo, topicsTab) {
                    var url = '/get-documents-filtering?';
                    if (lang) url += 'lang=' + lang;
                    if (criteria) url += '&ct=' + criteria;
                    if (themes)  url += '&themes=' + themes;
                    if (dateFrom) url += '&date-from=' + dateFrom;
                    if (dateTo) url += '&date-to=' + dateTo;
                    var topics = "";
                    for(var i = 0; i < topicsTab.length; i++){
                        topics += topicsTab[i] + ';';
                    }
//                    var years = "";
//                    for(var i = 0; i < yearsTab.length; i++){
//                        years += yearsTab[i] + ';'; 
//                    }
                    if (topics !== "") url += '&topics=' + topics;
//                    if (years !== "") url += '&years=' + years;
                    return $http.get(url);
                },
                getPopularDocument: function (eli_lang_code) {
                    var query = 'SELECT ?actURI ?actID ?title ?number WHERE { ?actURI a sioc:Forum; sioc:id ?actID;' +
                            ' lodep:num_items_total ?number; lodep:subject ?title. FILTER (LANG(?title) = "' + eli_lang_code +
                            '"). OPTIONAL{?actURI lodep:created_at ?date.}} ORDER BY DESC(?number) DESC(?date) LIMIT 1';
                    var queryUrl = Config.sparqlEndPoint + "?query=" + encodeURIComponent(query) + "&format=json&callback=JSON_CALLBACK";
                    return $http({method: 'JSONP', url: queryUrl});
                },
                getNumbersOfTypeDocumentAggregated: function (actURI) {
                    if (actURI.indexOf(Config.siteName) !== 0){
                        actURI = Config.siteName + actURI;
                    }
                    var query = 'SELECT ?total ?yes ?mixed ?no  WHERE {  <' + actURI +
                            '> lodep:num_items_total ?total. OPTIONAL{<' + actURI + '> lodep:num_items_yes ?yes.} OPTIONAL{<' + actURI +
                            '>  lodep:num_items_mixed ?mixed.} OPTIONAL{<' + actURI + '> lodep:num_items_no ?no.}}';

                    var queryUrl = Config.sparqlEndPoint + "?query=" + encodeURIComponent(query) + "&format=json&callback=JSON_CALLBACK";
                    return $http.jsonp(queryUrl);
                },
                getNumbersOfTypeDocumentNoAggregated: function (actURI) {
                    if (actURI.indexOf(Config.siteName) !== 0){
                        actURI = Config.siteName + actURI;
                    }
                    var genericContainerURI = actURI.substring(0, actURI.length - 4);
                    var filter = Config.createLanguageFilterContainer(genericContainerURI, Config.listLanguages, false, '');
                    var query = 'SELECT ?totalna ?yesna ?mixedna ?nona  WHERE {  ?container lodep:num_items_total_na '
                            + '?totalna. ' + filter +
                            ' OPTIONAL{?container lodep:num_items_yes_na ?yesna.} OPTIONAL{?container lodep:num_items_mixed_na ?mixedna.} ' +
                            'OPTIONAL{?container lodep:num_items_no_na ?nona. }} ORDER BY DESC(?totalna) LIMIT 1';
                    var queryUrl = Config.sparqlEndPoint + "?query=" + encodeURIComponent(query) + "&format=json&callback=JSON_CALLBACK";
                    return $http.jsonp(queryUrl);
                },
                getDataChart: function (result) {
                    var avg_yesna = 0.00;
                    if (result.yesna) {
                        avg_yesna = result.yesna.value;
                    }
                    var avg_mixedna = 0.00;
                    if (result.mixedna) {
                        avg_mixedna = result.mixedna.value;
                    }
                    var avg_nona = 0.00;
                    if (result.nona) {
                        avg_nona = result.nona.value;
                    }
                    var total = result.totalna.value;
                    avg_yesna /= total * 100;
                    avg_mixedna /= total * 100;
                    avg_nona /= total * 100;
                    return [['Note', 'Value'], ['positive', avg_yesna], ['neutral', avg_mixedna], ['negative', avg_nona]];
                },
                getDataChartAggregated: function (result) {
                    return [['Note', 'Value'], ['positive', result.yes.value / result.total.value * 100],
                        ['neutral', result.mixed.value / result.total.value * 100], ['negative', result.no.value / result.total.value * 100]];
                },
                getNumbersOfTypeDocumentForItem: function (genericAct) {
                    var query = 'SELECT distinct  ?total ?yes ?no ?mixed WHERE { <' + genericAct +
                            '> lodep:num_items_total ?total; lodep:num_items_yes ?yes; lodep:num_items_no ?no; lodep:num_items_mixed ?mixed.}';
                    var queryUrl = Config.sparqlEndPoint + "?query=" + encodeURIComponent(query) + "&format=json&callback=JSON_CALLBACK";
                    return $http.jsonp(queryUrl);
                },
                getDataChartForItem: function (result) {
                    var average_yes = 100 * result.yes.value / result.total.value;
                    var average_mixed = 100 * result.mixed.value / result.total.value;
                    var average_no = 100 * result.no.value / result.total.value;
                    return [
                        {Note: "positive", Value: average_yes},
                        {Note: "neutral", Value: average_mixed},
                        {Note: "negative", Value: average_no}
                    ];
                },
                getPath: function (docID, eli_lang_code) {
                    var query = 'SELECT ?path WHERE {?act a sioc:Forum;sioc:id "' + docID + '"^^rdfs:Literal; lodep:title ?title. FILTER(lang(?title) = "' + eli_lang_code + '") ?act sioc:has_space ?path.}';
                    var queryUrl = Config.sparqlEndPoint + "?query=" + encodeURIComponent(query) + "&format=json&callback=JSON_CALLBACK";
                    return $http.jsonp(queryUrl);
                },
                getTotalDocuments: function (year, lang) {
                    var query = 'SELECT COUNT(*) as ?total WHERE {?act a sioc:Forum; lodep:title ?title; sioc:has_parent ?p. ?p lodep:procedure_year ?year. FILTER(?year = "' + year + '"^^xsd:gYear) FILTER(lang(?title) = "' + lang + '")}';
                    var queryUrl = Config.sparqlEndPoint + "?query=" + encodeURIComponent(query) + "&format=json&callback=JSON_CALLBACK";
                    return $http.jsonp(queryUrl);
                },
                existFile: function (searchedFile) {
                    return $http.get('/exist-file?searchedFile='.concat(searchedFile));
                }
            };
        });