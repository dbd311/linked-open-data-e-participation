angular.module('configService', [])

        .service('Config', function ($http) {
            return {
                loadLanguages: function () {
                    return $http.get('/load-languages');
                },
                loadNationalities: function () {
                    return $http.get('/load-nationalities');
                },
                loadGroups: function () {
                    return $http.get('/load-groups');
                },
                getHashtags: function (start) {
                    if (start.substring(0, 1) === '#') {
                        start = '%23' + start.substring(1, start.length);
                    }
                    return $http.get('/get-hashtags?start=' + start);
                },
                getAuthors: function (name) {
                    return $http.get('/get-authors?name=' + name);
                },
                getLangCode: function (lang) {
                    return $http.get('/get_eli_lang_code/'.concat(lang));
                },
                drawChart: function (data, chartID, width, height, legend_position) {
                    var chartArea = document.getElementById(chartID);
                    var chart = new google.visualization.PieChart(chartArea);
                    var formatter = new google.visualization.NumberFormat(
                            {negativeColor: 'red', negativeParens: true, pattern: '####.##'});

                    var datatable = google.visualization.arrayToDataTable(data);
                    formatter.format(datatable, 1);
                    if (typeof legend_position === 'undefined') {
                        legend_position = 'none';
                    }

                    var options = {
                        'legend': legend_position,
                        'width': width,
                        'height': height,
                        slices: {0: {color: 'green'}, 1: {color: 'gray'}, 2: {color: 'red'}}
                    };
                    chart.draw(datatable, options);
                },
                drawChartNoColor: function (data, chartID, width, height, legend_position) {
                    var chartArea = document.getElementById(chartID);
                    var chart = new google.visualization.PieChart(chartArea);
                    var formatter = new google.visualization.NumberFormat(
                            {negativeColor: 'red', negativeParens: true, pattern: '####.##'});

                    var datatable = google.visualization.arrayToDataTable(data);
                    formatter.format(datatable, 1);
                    if (typeof legend_position === 'undefined') {
                        legend_position = 'none';
                    }

                    var options = {
                        'legend': legend_position,
                        'width': width,
                        'height': height
                    };

                    chart.draw(datatable, options);
                },
                createLanguageFilterContainer: function (container, languages, onlyMyComments, userUri) {
                    var filter = 'FILTER (';
                    if (onlyMyComments === true) {
                        filter += '?user=<' + userUri + '>&&(';
                    }
                    var i = 0;
                    for (; i < languages.length - 1; i++) {
                        var language = languages[i];
                        filter += '?container=<' + container + '/' + language.code3 + '>||';
                    }
                    if (languages.length > 0) {
                        var language = languages[i];
                        filter += '?container=<' + container + '/' + language.code3 + '>)';
                    }
                    if (onlyMyComments === true) {
                        filter += ')';
                    }
                    return filter;
                },
                loadEnvParams: function () {
                    return $http.get('/services/get-env-parameters');
                }
            };
        });