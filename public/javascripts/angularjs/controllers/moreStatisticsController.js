angular.module('moreStatisticsCtrl', [])

.controller('moreStatisticsCtrl', function ($scope, $filter, Document, Comment, Config, Statistics, StatisticsAmendments) {
    
    $('#statsPage').hide();
    $scope.commentsStats = "active";
    $scope.noCommentsStats = "";
    $('.allChartsAmendments').hide();
    
    $scope.changeStatsType = function(type){
        if (type === 'C'){
            $('.allChartsAmendments').hide();
            $('.allChartsComments').show();
            $scope.commentsStats = "active";
            $scope.noCommentsStats = "";
        } else {
            $('.allChartsComments').hide();
            $('.allChartsAmendments').show();
            $scope.commentsStats = "";
            $scope.noCommentsStats = "active";
        }
    };
    
    $scope.loadBarCharts = function(containerURI,aggregated,noDataComments,noDataAmendments){
        loadDataBarChartNeutralCommentsByNationalityAgregated(containerURI);
        $scope.noDataComments = noDataComments;
        $scope.noDataAmendments = noDataAmendments;
        if (aggregated === true) {
            $scope.aggregated = "active";
            $scope.noAggregated = "";
            loadDataDonutChartCommentsAgregated(containerURI);
            loadDataDonutChartAmendmentsAgregated(containerURI);
            loadDataBarChartPositiveCommentsByNationalityAgregated(containerURI);
            loadDataBarChartNegativeCommentsByNationalityAgregated(containerURI);
            loadDataBarChartNeutralCommentsByNationalityAgregated(containerURI);
            loadDataBarChartPositiveCommentsByGroupAgregated(containerURI);
            loadDataBarChartNegativeCommentsByGroupAgregated(containerURI);
            loadDataBarChartNeutralCommentsByGroupAgregated(containerURI);
            loadDataBarChartInsertionsByNationalityAgregated(containerURI);
            loadDataBarChartDeletionsByNationalityAgregated(containerURI);
            loadDataBarChartSubstitutionsByNationalityAgregated(containerURI);
            loadDataBarChartInsertionsByGroupAgregated(containerURI);
            loadDataBarChartDeletionsByGroupAgregated(containerURI);
            loadDataBarChartSubstitutionsByGroupAgregated(containerURI);
        } else {
            $scope.aggregated = "";
            $scope.noAggregated = "active";
            Config.loadLanguages().success(function(languages) {
                loadDataDonutChartCommentsNoAgregated(containerURI,languages);
                loadDataDonutChartAmendmentsNoAgregated(containerURI,languages);
                loadDataBarChartPositiveCommentsByNationalityNoAgregated(containerURI,languages);
                loadDataBarChartNegativeCommentsByNationalityNoAgregated(containerURI,languages);
                loadDataBarChartNeutralCommentsByNationalityNoAgregated(containerURI,languages);
                loadDataBarChartPositiveCommentsByGroupNoAgregated(containerURI,languages);
                loadDataBarChartNegativeCommentsByGroupNoAgregated(containerURI,languages);
                loadDataBarChartNeutralCommentsByGroupNoAgregated(containerURI,languages);
                loadDataBarChartInsertionsByNationalityNoAgregated(containerURI,languages);
                loadDataBarChartDeletionsByNationalityNoAgregated(containerURI,languages);
                loadDataBarChartSubstitutionsByNationalityNoAgregated(containerURI,languages);
                loadDataBarChartInsertionsByGroupNoAgregated(containerURI,languages);
                loadDataBarChartDeletionsByGroupNoAgregated(containerURI,languages);
                loadDataBarChartSubstitutionsByGroupNoAgregated(containerURI,languages);
            });
        }
    };
    
    $scope.showNoAgrregated = function(id){
        if (id === 'ACT') return true;
        else return false;
    };
    
    function loadDataDonutChartCommentsAgregated(containerURI) {
        Statistics.getAggregated(containerURI)
        .success(function (data) {
            loadCommentsChart(data.results.bindings[0]);
        });
    };
    
    function loadDataDonutChartCommentsNoAgregated(containerURI,languages) {
        Statistics.getNoAggregated(containerURI,languages)
        .success(function (data) {
            loadCommentsChart(data.results.bindings[0]);
        });
    };
    
    function loadDataDonutChartAmendmentsAgregated(containerURI) {
        StatisticsAmendments.getAggregated(containerURI)
        .success(function (data) {
            loadAmendmentsChart(data.results.bindings[0]);
        });
    };
    
    function loadDataDonutChartAmendmentsNoAgregated(containerURI,languages) {
        StatisticsAmendments.getNoAggregated(containerURI,languages)
        .success(function (data) {
            loadAmendmentsChart(data.results.bindings[0]);
        });
    };
    
    function loadCommentsChart(results){
        var opinions = [
            {Note: "Positive", Value: results.yes.value},
            {Note: "Neutral", Value: results.mixed.value},
            {Note: "Negative", Value: results.no.value}
        ];
        if(parseInt(opinions[0].Value) + parseInt(opinions[1].Value) + parseInt(opinions[2].Value) === 0){
            opinions = [];
        }
        var chartElement = d3.select("#chartComments svg");
        var chart;
        nv.addGraph(function() {
            hideAllBarCharts()
            var myColors = ['#668014', '#949494', '#CD0000'];
                d3.scale.myColors = function() {
                    return d3.scale.ordinal().range(myColors);
                };
            chart = nv.models.pieChart()
                    .showLabels(true)
                    .labelType("percent")
                    .donut(true)
                    .donutRatio(0.35)
                    .showLegend(false)
                    .noData($scope.noDataComments)
                    .color(d3.scale.myColors().range())
            .x(function(d) {
                return d.Note;
            })
            .y(function(d) {
                return d.Value;
            })
            .showLabels(true);
            chart.valueFormat(d3.format('d'));
            chartElement.datum(opinions).call(chart);
            chart.pie.dispatch.on("elementClick", function(e) {
                hideAllBarCharts();
                if(e.label === "Positive"){
                    $('#chartPositiveCommentsNationality').show();
                    $('#chartPositiveCommentsGroup').show();
                } else if(e.label === "Negative"){
                    $('#chartNegativeCommentsNationality').show();
                    $('#chartNegativeCommentsGroup').show();
                } else{
                    $('#chartNeutralCommentsNationality').show();
                    $('#chartNeutralCommentsGroup').show();
                }
            });
            return chart;
        });
    };
    
    function loadAmendmentsChart(results){
        var words = [
            {Note: "Insertions", Value: results.insertion.value},
            {Note: "Deletions", Value: results.deletion.value},
            {Note: "Substitutions", Value: results.substitution.value}
        ];
        var chartElement;
        if(parseInt(words[0].Value) + parseInt(words[1].Value) + parseInt(words[2].Value) === 0){
            words = [];
            chartElement = d3.select("#chartAmendmentsEmpty svg");
            $('#chartAmendmentsEmpty').show();
            $('#chartAmendments').hide();
        } else {
            chartElement = d3.select("#chartAmendments svg");
            $('#chartAmendmentsEmpty').hide();
            $('#chartAmendments').show();
        }
        var chart;
        nv.addGraph(function() {
            hideAllBarCharts();
            var myColors = ['#668014', '#CD0000', '#949494'];
                d3.scale.myColors = function() {
                    return d3.scale.ordinal().range(myColors);
                };
            chart = nv.models.pieChart()
                    .showLabels(true)
                    .labelType("percent")
                    .donut(true)
                    .donutRatio(0.35)
                    .showLegend(false)
                    .noData($scope.noDataAmendments)
                    .color(d3.scale.myColors().range())
            .x(function(d) {
                return d.Note;
            })
            .y(function(d) {
                return d.Value;
            })
            .showLabels(true);
            chart.valueFormat(d3.format('d'));
            chartElement.datum(words).call(chart);
            chart.pie.dispatch.on("elementClick", function(e) {
                hideAllBarCharts();
                if(e.label === "Insertions"){
                    $('#chartInsertionsNationality').show();
                    $('#chartInsertionsGroup').show();
                } else if(e.label === "Deletions"){
                    $('#chartDeletionsNationality').show();
                    $('#chartDeletionsGroup').show();
                } else{
                    $('#chartSubstitutionsNationality').show();
                    $('#chartSubstitutionsGroup').show();
                }
            });
            return chart;
        });
    };
    
    function hideAllBarCharts() {
        $('#chartPositiveCommentsNationality').hide();
        $('#chartNegativeCommentsNationality').hide();
        $('#chartNeutralCommentsNationality').hide();
        $('#chartPositiveCommentsGroup').hide();
        $('#chartNegativeCommentsGroup').hide();
        $('#chartNeutralCommentsGroup').hide();
        $('#chartInsertionsNationality').hide();
        $('#chartDeletionsNationality').hide();
        $('#chartSubstitutionsNationality').hide();
        $('#chartInsertionsGroup').hide();
        $('#chartDeletionsGroup').hide();
        $('#chartSubstitutionsGroup').hide();
    };
    
    function addBarChart(idChart,values){
        var chart = nv.models.discreteBarChart()
            .x(function(d) { return d.Label; })
            .y(function(d) { return d.Value; })
            .tooltips(true)
            .showXAxis(false);
        chart.yAxis.tickFormat(d3.format('d'));
        chart.valueFormat(d3.format('d'));
        d3.select('#' + idChart + ' svg')
            .datum([{key: "Cumulative Return",values: values}])
            .call(chart);
        nv.utils.windowResize(chart.update);
        return chart;
    };
    
    function loadDataBarChartNeutralCommentsByNationality(results) {
        var nationalities = [];
        for (var i in results) {
            var result = results[i];
            if (result.mixed.value !== "0"){
                var splitName = result.nationality.value.split("/");
                var name = splitName[splitName.length-1];
                var nationality = {
                    "Label" : name.charAt(0).toUpperCase() + name.substring(1).toLowerCase(),
                    "Value" : parseInt(result.mixed.value)
                };
                nationalities.push(nationality);
            }
        }
        nv.addGraph(function() {
            return addBarChart('chartNeutralCommentsNationality',nationalities);
        });
    };
    
    function loadDataBarChartNeutralCommentsByNationalityAgregated(containerURI) {
        Statistics.getByNationalityAggregated(containerURI)
        .success(function (data) {
            loadDataBarChartNeutralCommentsByNationality(data.results.bindings);
        });
    };
    
    function loadDataBarChartNeutralCommentsByNationalityNoAgregated(containerURI,languages) {
        Statistics.getByNationalityNoAggregated(containerURI,languages)
        .success(function (data) {
            loadDataBarChartNeutralCommentsByNationality(data.results.bindings);
        });
    };
    
    function loadDataBarChartNegativeCommentsByNationality(results){
        var nationalities = [];
        for (var i in results) {
            var result = results[i];
            if (result.no.value !== "0"){
                var splitName = result.nationality.value.split("/");
                var name = splitName[splitName.length-1];
                var nationality = {
                    "Label" : name.charAt(0).toUpperCase() + name.substring(1).toLowerCase(),
                    "Value" : parseInt(result.no.value)
                };
                nationalities.push(nationality);
            }
        }
        nv.addGraph(function() {
            return addBarChart('chartNegativeCommentsNationality',nationalities);
        });
    };
    
    function loadDataBarChartNegativeCommentsByNationalityAgregated(containerURI) {
        Statistics.getByNationalityAggregated(containerURI)
        .success(function (data) {
            loadDataBarChartNegativeCommentsByNationality(data.results.bindings);
        });
    };
    
    function loadDataBarChartNegativeCommentsByNationalityNoAgregated(containerURI,languages) {
        Statistics.getByNationalityNoAggregated(containerURI,languages)
        .success(function (data) {
            loadDataBarChartNegativeCommentsByNationality(data.results.bindings);
        });
    };
    
    function loadDataBarChartPositiveCommentsByNationality(results){
        var nationalities = [];
        for (var i in results) {
            var result = results[i];
            if (result.yes.value !== "0"){
                var splitName = result.nationality.value.split("/");
                var name = splitName[splitName.length-1];
                var nationality = {
                    "Label" : name.charAt(0).toUpperCase() + name.substring(1).toLowerCase(),
                    "Value" : parseInt(result.yes.value)
                };
                nationalities.push(nationality);
            }
        }
        nv.addGraph(function() {
            return addBarChart('chartPositiveCommentsNationality',nationalities);
        });
    };
    
    function loadDataBarChartPositiveCommentsByNationalityAgregated(containerURI) {
        Statistics.getByNationalityAggregated(containerURI)
        .success(function (data) {
            loadDataBarChartPositiveCommentsByNationality(data.results.bindings);
        });
    };
    
    function loadDataBarChartPositiveCommentsByNationalityNoAgregated(containerURI,languages) {
        Statistics.getByNationalityNoAggregated(containerURI,languages)
        .success(function (data) {
            loadDataBarChartPositiveCommentsByNationality(data.results.bindings);
        });
    };
    
    function loadDataBarChartNeutralCommentsByGroup(results){
        var groups = [];
        for (var i in results) {
            var result = results[i];
            if (result.mixed.value !== "0"){
                var splitName = result.group.value.split("/");
                var name = splitName[splitName.length-1];
                var group = {
                    "Label" : name.charAt(0).toUpperCase() + name.substring(1).toLowerCase(),
                    "Value" : parseInt(result.mixed.value)
                };
                groups.push(group);
            }
        }
        nv.addGraph(function() {
            return addBarChart('chartNeutralCommentsGroup',groups);
        });
    };
    
    function loadDataBarChartNeutralCommentsByGroupAgregated(containerURI) {
        Statistics.getByGroupAggregated(containerURI)
        .success(function (data) {
            loadDataBarChartNeutralCommentsByGroup(data.results.bindings);
        });
    };
    
    function loadDataBarChartNeutralCommentsByGroupNoAgregated(containerURI,languages) {
        Statistics.getByGroupNoAggregated(containerURI,languages)
        .success(function (data) {
            loadDataBarChartNeutralCommentsByGroup(data.results.bindings);
        });
    };
    
    function loadDataBarChartPositiveCommentsByGroup(results){
        var groups = [];
        for (var i in results) {
            var result = results[i];
            if (result.yes.value !== "0"){
                var splitName = result.group.value.split("/");
                var name = splitName[splitName.length-1];
                var group = {
                    "Label" : name.charAt(0).toUpperCase() + name.substring(1).toLowerCase(),
                    "Value" : parseInt(result.yes.value)
                };
                groups.push(group);
            }
        }
        nv.addGraph(function() {
            return addBarChart('chartPositiveCommentsGroup',groups);
        });
    };
    
    function loadDataBarChartPositiveCommentsByGroupAgregated(containerURI) {
        Statistics.getByGroupAggregated(containerURI)
        .success(function (data) {
            loadDataBarChartPositiveCommentsByGroup(data.results.bindings);
        });
    };
    
    function loadDataBarChartPositiveCommentsByGroupNoAgregated(containerURI,languages) {
        Statistics.getByGroupNoAggregated(containerURI,languages)
        .success(function (data) {
            loadDataBarChartPositiveCommentsByGroup(data.results.bindings);
        });
    };
    
    function loadDataBarChartNegativeCommentsByGroup(results){
        var groups = [];
        for (var i in results) {
            var result = results[i];
            if (result.no.value !== "0"){
                var splitName = result.group.value.split("/");
                var name = splitName[splitName.length-1];
                var group = {
                    "Label" : name.charAt(0).toUpperCase() + name.substring(1).toLowerCase(),
                    "Value" : parseInt(result.no.value)
                };
                groups.push(group);
            }
        }
        nv.addGraph(function() {
            return addBarChart('chartNegativeCommentsGroup',groups);
        });
    };
    
    function loadDataBarChartNegativeCommentsByGroupAgregated(containerURI) {
        Statistics.getByGroupAggregated(containerURI)
        .success(function (data) {
            loadDataBarChartNegativeCommentsByGroup(data.results.bindings);
        });
    };
    
    function loadDataBarChartNegativeCommentsByGroupNoAgregated(containerURI,languages) {
        Statistics.getByGroupNoAggregated(containerURI,languages)
        .success(function (data) {
            loadDataBarChartNegativeCommentsByGroup(data.results.bindings);
        });
    };
    
    function loadDataBarChartInsertionsByNationality(results){
        var nationalities = [];
        for (var i in results) {
            var result = results[i];
            if (result.insertion.value !== "0"){
                var splitName = result.nationality.value.split("/");
                var name = splitName[splitName.length-1];
                var nationality = {
                    "Label" : name.charAt(0).toUpperCase() + name.substring(1).toLowerCase(),
                    "Value" : parseInt(result.insertion.value)
                };
                nationalities.push(nationality);
            }
        }
        nv.addGraph(function() {
            return addBarChart('chartInsertionsNationality',nationalities);
        });
    };
    
    function loadDataBarChartInsertionsByNationalityAgregated(containerURI) {
        StatisticsAmendments.getByNationalityAggregated(containerURI)
        .success(function (data) {
            loadDataBarChartInsertionsByNationality(data.results.bindings);
        });
    };
    
    function loadDataBarChartInsertionsByNationalityNoAgregated(containerURI,languages) {
        StatisticsAmendments.getByNationalityNoAggregated(containerURI,languages)
        .success(function (data) {
            loadDataBarChartInsertionsByNationality(data.results.bindings);
        });
    };
    
    function loadDataBarChartDeletionsByNationality(results){
        var nationalities = [];
        for (var i in results) {
            var result = results[i];
            if (result.deletion.value !== "0"){
                var splitName = result.nationality.value.split("/");
                var name = splitName[splitName.length-1];
                var nationality = {
                    "Label" : name.charAt(0).toUpperCase() + name.substring(1).toLowerCase(),
                    "Value" : parseInt(result.deletion.value)
                };
                nationalities.push(nationality);
            }
        }
        nv.addGraph(function() {
            return addBarChart('chartDeletionsNationality',nationalities);
        });
    };
    
    function loadDataBarChartDeletionsByNationalityAgregated(containerURI) {
        StatisticsAmendments.getByNationalityAggregated(containerURI)
        .success(function (data) {
            loadDataBarChartDeletionsByNationality(data.results.bindings);
        });
    };
    
    function loadDataBarChartDeletionsByNationalityNoAgregated(containerURI,languages) {
        StatisticsAmendments.getByNationalityNoAggregated(containerURI,languages)
        .success(function (data) {
            loadDataBarChartDeletionsByNationality(data.results.bindings);
        });
    };
    
    function loadDataBarChartSubstitutionsByNationality(results){
        var nationalities = [];
        for (var i in results) {
            var result = results[i];
            if (result.substitution.value !== "0"){
                var splitName = result.nationality.value.split("/");
                var name = splitName[splitName.length-1];
                var nationality = {
                    "Label" : name.charAt(0).toUpperCase() + name.substring(1).toLowerCase(),
                    "Value" : parseInt(result.substitution.value)
                };
                nationalities.push(nationality);
            }
        }
        nv.addGraph(function() {
            return addBarChart('chartSubstitutionsNationality',nationalities);
        });
    };
    
    function loadDataBarChartSubstitutionsByNationalityAgregated(containerURI) {
        StatisticsAmendments.getByNationalityAggregated(containerURI)
        .success(function (data) {
            loadDataBarChartSubstitutionsByNationality(data.results.bindings);
        });
    };
    
    function loadDataBarChartSubstitutionsByNationalityNoAgregated(containerURI,languages) {
        StatisticsAmendments.getByNationalityNoAggregated(containerURI,languages)
        .success(function (data) {
            loadDataBarChartSubstitutionsByNationality(data.results.bindings);
        });
    };
    
    function loadDataBarChartInsertionsByGroup(results){
        var groups = [];
        for (var i in results) {
            var result = results[i];
            if (result.insertion.value !== "0"){
                var splitName = result.group.value.split("/");
                var name = splitName[splitName.length-1];
                var group = {
                    "Label" : name.charAt(0).toUpperCase() + name.substring(1).toLowerCase(),
                    "Value" : parseInt(result.insertion.value)
                };
                groups.push(group);
            }
        }
        nv.addGraph(function() {
            return addBarChart('chartInsertionsGroup',groups);
        });
    };
    
    function loadDataBarChartInsertionsByGroupAgregated(containerURI) {
        StatisticsAmendments.getByGroupAggregated(containerURI)
        .success(function (data) {
            loadDataBarChartInsertionsByGroup(data.results.bindings);
        });
    };
    
    function loadDataBarChartInsertionsByGroupNoAgregated(containerURI,languages) {
        StatisticsAmendments.getByGroupNoAggregated(containerURI,languages)
        .success(function (data) {
            loadDataBarChartInsertionsByGroup(data.results.bindings);
        });
    };
    
    function loadDataBarChartDeletionsByGroup(results){
        var groups = [];
        for (var i in results) {
            var result = results[i];
            if (result.deletion.value !== "0"){
                var splitName = result.group.value.split("/");
                var name = splitName[splitName.length-1];
                var group = {
                    "Label" : name.charAt(0).toUpperCase() + name.substring(1).toLowerCase(),
                    "Value" : parseInt(result.deletion.value)
                };
                groups.push(group);
            }
        }
        nv.addGraph(function() {
            return addBarChart('chartDeletionsGroup',groups);
        });
    };
    
    function loadDataBarChartDeletionsByGroupAgregated(containerURI) {
        StatisticsAmendments.getByGroupAggregated(containerURI)
        .success(function (data) {
            loadDataBarChartDeletionsByGroup(data.results.bindings);
        });
    };
    
    function loadDataBarChartDeletionsByGroupNoAgregated(containerURI,languages) {
        StatisticsAmendments.getByGroupNoAggregated(containerURI,languages)
        .success(function (data) {
            loadDataBarChartDeletionsByGroup(data.results.bindings);
        });
    };
    
    function loadDataBarChartSubstitutionsByGroup(results){
        var groups = [];
        for (var i in results) {
            var result = results[i];
            if (result.substitution.value !== "0"){
                var splitName = result.group.value.split("/");
                var name = splitName[splitName.length-1];
                var group = {
                    "Label" : name.charAt(0).toUpperCase() + name.substring(1).toLowerCase(),
                    "Value" : parseInt(result.substitution.value)
                };
                groups.push(group);
            }
        }
        nv.addGraph(function() {
            return addBarChart('chartSubstitutionsGroup',groups);
        });
    };
    
    function loadDataBarChartSubstitutionsByGroupAgregated(containerURI) {
        StatisticsAmendments.getByGroupAggregated(containerURI)
        .success(function (data) {
            loadDataBarChartSubstitutionsByGroup(data.results.bindings);
        });
    };
    
    function loadDataBarChartSubstitutionsByGroupNoAgregated(containerURI,languages) {
        StatisticsAmendments.getByGroupNoAggregated(containerURI,languages)
        .success(function (data) {
            loadDataBarChartSubstitutionsByGroup(data.results.bindings);
        });
    };
    
    $scope.loadDocument = function (path) {
        $scope.onlyMyComments = false;
        $scope.nbTotalComments = " " + 0;
        Document.loadDocument(path)
            .success(function (data) {
                $scope.document = data;
                if (typeof $scope.document.num === 'undefined') {
                    $scope.document.num = 'INFOHUB';
                }
                $scope.document.docID = 'comnat:COM_' + $scope.document.year + '_' + $scope.document.num + '_FIN';
                $scope.document.docCode = 'NA';
                Document.loadMetaData($scope.document.docID, $scope.document.eli_lang_code)
                        .success(function (metadata) {
                            if (metadata.results.bindings.length > 0) {
                                var infos = metadata.results.bindings[0];
                                if (infos.procedure_code)
                                    $scope.document.procedureCode = infos.procedure_code.value;
                                if (infos.id_celex)
                                    $scope.document.idCelex = infos.id_celex.value;
                                if (infos.date_adopted)
                                    $scope.document.dateAdopted = $scope.formatDate(infos.date_adopted.value);
                                if (infos.procedure_type_label)
                                    $scope.document.procedureTypeLabel = infos.procedure_type_label.value;
                                if (infos.directory_code) {
                                    var splitDirectory = infos.directory_code.value.split('/');
                                    $scope.document.directoryCode = splitDirectory[splitDirectory.length - 1];
                                }
                                if (infos.doc_code) {
                                    $scope.document.docCode = infos.doc_code.value;
                                } else {
                                    $scope.document.docCode = 'NA';
                                }
                                var userUri = Config.siteName + '/user/id_user_' + $scope.userId;
                                Comment.getComments('/eli/' + $scope.document.docCode + '/' + $scope.document.uri, $scope.onlyMyComments, userUri)
                                        .success(function (data) {
                                            $scope.document.nbComments = data.results.bindings.length;
                                        });
                                $scope.displayAggregatedStatistics();
                            }
                        });
                Document.loadThemes($scope.document.docID, $scope.document.eli_lang_code)
                        .success(function (themes) {
                            if (themes.length > 0) {
                                $scope.document.topics = themes;
                            }
                            $('#loading').hide();
                            $('#statsPage').show();
                        });
                var folder = path.split("/")[0];
                $scope.document.folder = folder;
                Document.loadAnnexes(folder)
                        .success(function (annexes) {
                            if (annexes.length > 0) {
                                $scope.document.annexes = annexes;
                            }
                        });
            })
            .error(function () {
                $scope.document = '';
                alert("There is a problem to load the document.");
            });
     };

     $scope.formatDate = function (date) {
         date = $filter('date')(new Date(date), "yyyy-MM-dd");
         return date;
     };

     $scope.displayAggregatedStatistics = function () {
        var containerURI = $scope.document.uri;
        var beginUri = '/eli/' + $scope.document.docCode + '/';
        if (containerURI.indexOf(beginUri) !== 0) {
            containerURI = beginUri + containerURI;
        }
        var genericContainerURI = containerURI.substring(0, containerURI.length - 4);
        Document.getNumbersOfTypeDocumentAggregated(genericContainerURI)
            .success(function (data) {
                var results = data.results.bindings;
                if (results) {
                    for (var i in results) {
                        var result = results[i];
                        $scope.nbTotalComments = " " + result.total.value;

                        var yes = 0;
                        var mixed = 0;
                        var no = 0;
                        if (typeof result.yes !== 'undefined')
                            yes = result.yes.value;
                        if (typeof result.mixed !== 'undefined')
                            mixed = result.mixed.value;
                        if (typeof result.no !== 'undefined')
                            no = result.no.value;
                        var percentYes = 0;
                        var percentMixed = 0;
                        var percentNo = 0;
                        if (yes !== 0)
                            percentYes = yes / result.total.value * 100;
                        if (mixed !== 0)
                            percentMixed = mixed / result.total.value * 100;
                        if (no !== 0)
                            percentNo = no / result.total.value * 100;
                        $scope.dataPieChartDocument = [
                            {Note: "Positive", Value: percentYes},
                            {Note: "Neutral", Value: percentMixed},
                            {Note: "Negative", Value: percentNo}
                        ];
                    }
                } else {
                    $scope.dataPieChartDocument = [];
                }
            });
     };

     var colorArrayPC = ['#668014', '#949494', '#CD0000'];
     $scope.colorFunctionPC = function () {
         return function (d, i) {
             return colorArrayPC[i];
         };
     };

     $scope.xFunctionPC = function () {
         return function (d) {
             return d.Note;
         };
     };

     $scope.yFunctionPC = function () {
         return function (d) {
             return d.Value;
         };
     };

     $scope.toolTipContentFunctionPC = function(){
         return function(key, x, y, graph) {
             return  '<h3>' + key + '</h3>' + '<p>' +  x + ' % </p>';
             };
     };
});