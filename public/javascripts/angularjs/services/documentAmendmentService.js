angular.module('documentAmendmentService', [])

        .factory('Document', function ($http, $rootScope, Config) {
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
                    return $http.get('/get-documents/'.concat(Config.interfaceLanguage).concat('/').concat(criteria));
                },
                loadAnnexes: function (folder) {
                    return $http.get('/load-annexes/'.concat(folder));
                },
                filter: function (lang, criteria, themes, dateFrom, dateTo) {
                    var url = '/get-documents-filtering?';
                    if (lang) {
                        url += 'hl=' + lang;
                    }
                    if (criteria) {
                        url += '&ct=' + criteria;
                    }

                    if (themes) {
                        url += '&themes=' + themes;
                    }

                    if (dateFrom) {
                        url += '&date-from=' + dateFrom;
                    }

                    if (dateTo) {
                        url += '&date-to=' + dateTo;
                    }
                    return $http.get(url);
                },
                filterYears: function (lang, years) {
                    var url = '/get-documents-filtering-years?';
                    if (lang) {
                        url += 'hl=' + lang;
                    }
                    if (years) {
                        var yearList = '';
                        var i = 0;
                        for (; i < years.length - 1; i++) {
                            yearList += years[i] + ',';
                        }
                        if (years.length > 0) {
                            yearList += years[i];
                        }
                        url += '&years=' + yearList;
                    }
                    return $http.get(url);
                },
                getPopularDocument: function (eli_lang_code) {
                    var query = 'SELECT ?actURI ?actID ?title ?number WHERE { ?actURI a sioc:Forum; sioc:id ?actID;' +
                            ' lodep:num_amendment ?number; lodep:subject ?title. FILTER (LANG(?title) = "' + eli_lang_code +
                            '"). OPTIONAL{?actURI lodep:created_at ?date.}} ORDER BY DESC(?number) DESC(?date) LIMIT 1';
                    var queryUrl = Config.SPARQL_END_POINT + "?query=" + encodeURIComponent(query) + "&format=json&callback=JSON_CALLBACK";
                    return $http({method: 'JSONP', url: queryUrl});
                },
                getNumbersOfTypeDocumentAggregated: function (actURI) {
                    if (actURI.indexOf(Config.SITENAME) !== 0){
                        actURI = Config.SITENAME + actURI;
                    }
                    var genericActURI;
                    var fields = actURI.split(/[\/]+/);
                    var index = $rootScope.languages.indexOf(fields[fields.length - 1]);
                    if (index !== -1) {
                        genericActURI = actURI.substring(0, actURI.length - 4);
                    } else {
                        genericActURI = actURI;
                    }
                    var query = 'SELECT ?total ?insertion ?substitution ?deletion  WHERE {  <' + genericActURI +
                            '> lodep:num_amendment ?total. OPTIONAL{<' + genericActURI + '> lodep:num_insertion ?insertion.} OPTIONAL{<' + genericActURI +
                            '>  lodep:num_substitution ?substitution.} OPTIONAL{<' + genericActURI + '> lodep:num_deletion ?deletion.}}';
//                    alert(query);
                    var queryUrl = Config.SPARQL_END_POINT + "?query=" + encodeURIComponent(query) + "&format=json&callback=JSON_CALLBACK";
                    return $http.jsonp(queryUrl);
                },
                getNumbersOfTypeDocumentNoAggregated: function (actURI) {
                    if (actURI.indexOf(Config.SITENAME) !== 0){
                        actURI = Config.SITENAME + actURI;
                    }
                    var genericContainerURI = actURI.substring(0, actURI.length - 4);
                    var filter = Config.createLanguageFilterContainer(genericContainerURI, $rootScope.languages, false, '');
                    var query = 'SELECT ?totalna ?insertion_na ?substitution_na ?deletion_na  WHERE {?container lodep:num_items_total_na '
                            + '?totalna. ' + filter +
                            ' OPTIONAL{?container lodep:num_insertion_na ?insertion_na.} OPTIONAL{?container lodep:num_substitution_na ?substitution_na.} ' +
                            'OPTIONAL{?container lodep:num_deletion_na ?deletion_na. }} ORDER BY DESC(?totalna) LIMIT 1';
                    var queryUrl = Config.SPARQL_END_POINT + "?query=" + encodeURIComponent(query) + "&format=json&callback=JSON_CALLBACK";
                    return $http.jsonp(queryUrl);
                },
                getDataChart: function (result) {
                    var avg_insertion_na = 0.00;
                    if (result.insertion_na) {
                        avg_insertion_na = result.insertion_na.value;
                    }
                    var avg_substitution_na = 0.00;
                    if (result.substitution_na) {
                        avg_substitution_na = result.substitution_na.value;
                    }
                    var avg_deletion_na = 0.00;
                    if (result.deletion_na) {
                        avg_deletion_na = result.deletion_na.value;
                    }
                    var total = result.totalna.value;
                    avg_insertion_na /= total * 100;
                    avg_substitution_na /= total * 100;
                    avg_deletion_na /= total * 100;
                    return [['Amendment_type', 'Value'], ['insertion', avg_insertion_na], ['substitution', avg_substitution_na], ['deletion', avg_deletion_na]];
                },
                getDataChartAggregated: function (result) {
                    return [['Amendment_type', 'Value'], ['insertion', result.insertion.value / result.total.value * 100],
                        ['substitution', result.substitution.value / result.total.value * 100], ['deletion', result.deletion.value / result.total.value * 100]];
                },
                getNumbersOfTypeDocumentForItem: function (genericAct) {
                    var query = 'SELECT distinct  ?total ?insertion ?deletion ?substitution WHERE { <' + genericAct +
                            '> lodep:num_amendment ?total; lodep:num_insertion ?insertion; lodep:num_deletion ?deletion; lodep:num_substitution ?substitution.}';
                    var queryUrl = Config.SPARQL_END_POINT + "?query=" + encodeURIComponent(query) + "&format=json&callback=JSON_CALLBACK";
                    return $http.jsonp(queryUrl);
                },
                getDataChartForItem: function (result) {
                    var average_insertion = 100 * result.insertion.value / result.total.value;
                    var average_substitution = 100 * result.substitution.value / result.total.value;
                    var average_deletion = 100 * result.deletion.value / result.total.value;
                    return [
                        {Amendment_type: "insertion", Value: average_insertion},
                        {Amendment_type: "substitution", Value: average_substitution},
                        {Amendment_type: "deletion", Value: average_deletion}
                    ];
                },
            };
        });