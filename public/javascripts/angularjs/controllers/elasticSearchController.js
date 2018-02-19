/***
 * @author Duy Dinh
 * @date 23 May 2016
 * @desc This controller is defined for handling ElasticSearch tasks
 */
angular.module('elasticSearchCtrl', [])

        .controller('elasticSearchCtrl', function ($scope, $http, ElasticSearch, Config, Document) {

            $scope.dateAll = true; // enable checkbox dateAll
            $scope.counter = -1;
            /**
             * index documents
             */
            $scope.indexDocuments = function () {
                $scope.startTime = new Date();

                ElasticSearch.indexDocuments($scope.dateFrom, $scope.dateTo)
                        .success(function (data) {

                            $scope.counter = data.counter;
                            $scope.endTime = new Date();
                        }).error(function (data, status) {
                    alert('Indexing documents failed! *** Data: ' + data + ' *** Status: ' + status);
                });
            };


            /**
             * Reset dates when selecting all documents for indexing
             */
            $scope.resetDates = function () {

                if ($scope.dateAll) {
                    $scope.dateFrom = '';
                    $scope.dateTo = '';
                }
            };

            /**             
             * Reset date FROM when selecting all documents for indexing
             */
            $scope.resetDateFrom = function () {
                $scope.dateFrom = '';
//                alert($scope.dateAll);
                $scope.dateAll = false;
//                alert($scope.dateAll);
            };

            /**             
             * Reset date TO when selecting all documents for indexing
             */
            $scope.resetDateTo = function () {
                $scope.dateTo = '';
                $scope.dateAll = false;
            };

            /***
             * Load indices 
             * @return an array of indices names            
             */
            $scope.loadIndices = function () {
                ElasticSearch.loadIndices().success(function (data) {
                    var res = JSON.parse(data);
                    $scope.indices = res['indices'];
                    var eli_lang_code = Config.langDoc;
                    var pos = find(eli_lang_code, $scope.indices);
                    if (pos < 0) {
                        pos = 0;
                    }

                    $scope.indList = $scope.indices[pos];

                }).error(function (status) {
                    alert(status);
                });
            };

            /***
             * Look up eli lang code in an array
             * @param {type} e
             * @param {type} arr
             * @returns {Number}
             */
            function find(e, arr) {
                for (var i = 0; i < arr.length; i++) {
                    if (e === arr[i].eli_lang_code) {
                        return i;
                    }
                }
                return -1;
            }
            ;


            /***
             * Delete a particular index or all indices
             * @param {type} index
             * @param {type} deleteAll
             * @returns {unresolved}
             */
            $scope.cleanIndex = function (index) {
                var url = '/services/clean-document-indices';

                if (typeof index !== 'undefined') {
                    url += '?i=' + index;
                }

                var response = $http.get(url);
                $scope.message = response;
                // reload indices
                $scope.loadIndices();
            };




            /***
             * Search for a query in a particular language
             */
            $scope.search = function (query, lang) {
                //alert(decodeURIComponent(query));
                ElasticSearch.runQuery(decodeURIComponent(query), lang).success(function (data) {

                    //alert(data);
                    $scope.results = data.hits.hits;

//                    alert('update metadata');
                    for (var i in $scope.results) {
                        $scope.getMetadata($scope.results[i], lang);
                        var summary = '';
                        var stopped = false;
                        angular.forEach($scope.results[i].highlight, function (value, key) {
                            if (!stopped) {
//                                alert(value);
                                summary += value + ' <b>...</b> ';
                            }
                            var threshold = 300;
                            if (summary.length > threshold) {
                                stopped = true;
                                var max = summary.length < threshold ? summary.length : threshold;
                                // find the previous full word
                                while (summary.charAt(max) !== ' ') {
                                    max--;
                                }
                                summary = summary.substring(0, max) + ' <b>...</b> ';
                            }
                        });
                        $scope.results[i].summary = summary;
                        //alert(summary);
                    }

                }).error(function (data, status) {
                    alert('Failed to search for query: "' + query + '" in language: "' + lang + '"');
                });
            };
            /*
             * Check if a JSON element is empty
             * @param {type} e
             * 
             */
            $scope.isEmpty = function (e) {

                var res = typeof (e) === 'undefined';

//                if (!res){
//                    alert(e);
//                }
                return res;
            };

            $scope.initSearchForm = function (query) {                
                $scope.q = query;
            }

            $scope.getMetadata = function (doc, lang) {

                // retrieve metadata awa some general statistics
                var id = 'comnat:COM_' + doc._source.year + '_' + doc._source.num + '_FIN';
                var query = 'SELECT DISTINCT * WHERE{\
                    ?act a sioc:Forum; sioc:id "' + id + '"^^rdfs:Literal;\
                    lodep:title ?title.\
                    FILTER (lang(?title) = "' + doc._source.eli_lang_code + '")\
                    OPTIONAL{?act lodep:procedure_number ?num.}\
                    OPTIONAL{?act lodep:created_at ?date_adopted.}\
                    OPTIONAL{?act lodep:id_celex ?id_celex.}\
                    OPTIONAL{?act lodep:doc_code ?doc_code.}\
                    OPTIONAL{?act sioc:has_space ?path.}\
                    ?act sioc:has_parent ?genericAct.\
                    ?genericAct lodep:num_items_total ?total; lodep:num_items_yes ?yes; lodep:num_items_mixed ?mixed; lodep:num_items_no ?no.\
                    OPTIONAL{?act lodep:procedure_code ?procedure_code.}\
                    OPTIONAL{?act lodep:directory_code ?directory_code.}\
                    OPTIONAL{?act lodep:procedure_type ?type_proc.\
                    ?type_proc rdfs:label ?procedure_type_label.\
                    FILTER (lang(?procedure_type_label) = "' + doc._source.eli_lang_code + '")\
                    }\
                  }';

                var queryUrl = Config.sparqlEndPoint + "?query=" + encodeURIComponent(query) + "&format=json&callback=JSON_CALLBACK";

                $http.jsonp(queryUrl).then(function (json) {

                    for (var i in json.data.results.bindings) {
                        var result = json.data.results.bindings[i];
                        doc.procedure = result.procedure_code.value;
                        doc.path = result.path.value;

                        doc.nbOfComments = result.total.value;

                        doc.nbOfCommentsY = result.yes.value;
                        doc.nbOfCommentsM = result.mixed.value;
                        doc.nbOfCommentsN = result.no.value;

                        doc.dataPieChart = $scope.generatePieChartStatistics(result);
                    }
                });

                // retrieve themes

                Document.loadThemes(id, lang)
                        .success(function (themes) {
                            if (themes.length > 0) {
                                doc.themes = themes;
                            }
                        })
                        .error(function (data, status) {
                            alert('Cannot load themes for document ' + id);
                        });
            };


            /***
             * Generate statistics from the result
             * @param {type} doc
             * @param {type} result
             * @returns {undefined}
             */
            $scope.generatePieChartStatistics = function (result) {
                // compute pie chart statistics
                var average_yes = 100 * result.yes.value / result.total.value;
                var average_mixed = 100 * result.mixed.value / result.total.value;
                var average_no = 100 * result.no.value / result.total.value;
//                        alert('calculated stats :' + average_yes + ' ' + average_mixed + ' ' + average_no);
                return [
                    {Note: "positive", Value: average_yes},
                    {Note: "neutral", Value: average_mixed},
                    {Note: "negative", Value: average_no}
                ];
            };
            /**
             * Calculate statistics for a document
             * @param {type} yes
             * @param {type} mixed
             * @param {type} no
             * @param {type} total
             * @param {type} document             
             */
            $scope.calculateStatistics = function (yes, mixed, no, total, document) {

                var average_yes = 100 * yes / total;
                var average_mixed = 100 * mixed / total;
                var average_no = 100 * no / total;
                //alert('calculated stats : ' + yes);

                document.dataPieChart = [
                    {Note: "positive", Value: average_yes},
                    {Note: "neutral", Value: average_mixed},
                    {Note: "negative", Value: average_no}
                ];
            };

            var colorArray = ['#668014', '#949494', '#CD0000'];
            $scope.colorFunction = function () {
                return function (d, i) {
                    return colorArray[i];
                };
            };
            $scope.xFunction = function () {
                return function (d) {
                    return d.Note;
                };
            };
            $scope.yFunction = function () {
                return function (d) {
                    return d.Value;
                };
            };

        });
