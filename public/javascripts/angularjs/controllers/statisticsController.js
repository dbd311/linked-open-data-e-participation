angular.module('statisticsCtrl', [])

        .controller('statisticsCtrl', function ($scope, Config, Document) {
            $scope.chart_width = 130;
            $scope.chart_height = 130;

            var colorArray = ['#668014', '#949494', '#CD0000'];
            $scope.colorFunction = function () {
                return function (d, i) {
                    return colorArray[i];
                };
            };
            $scope.xFunction = function (d1) {
                return function (d) {
                    return d.Note;
                };
            };
            $scope.yFunction = function (d) {
                return function (d) {
                    return d.Value;
                };
            };

            /***
             * Display a google pie chart
             * @param {type} containerURI
             * @param {type} ID
             * @returns {undefined}
             */
            $scope.displayStatistics = function (containerURI, ID) {
                Document.getNumbersOfTypeDocumentNoAggregated(containerURI)
                        .success(function (data) {
                            var results = data.results.bindings;
                            for (var i in results) {
                                var result = results[i];
                                var arr = Document.getDataChart(result);
                                Config.drawChart(arr, ID, $scope.chart_width, $scope.chart_height);
                            }
                        });
            };

            /***
             * Display a google pie chart
             * @param {type} containerURI
             * @param {type} ID
             * @returns {undefined}
             */
            $scope.displayAggregatedStatistics = function (containerURI, ID) {
                Document.getNumbersOfTypeDocumentAggregated(containerURI)
                        .success(function (data) {
                            var results = data.results.bindings;

                            for (var i in results) {
                                var result = results[i];
                                var arr = Document.getDataChartAggregated(result);
                                $scope.totalComments = result.total.value;

                                Config.drawChart(arr, ID);
                            }
                        });
            };

            $scope.$on('updateComments', function (event, params) {
                $scope.totalComments = params.nbComments;
                Config.drawChart(params.array, 'document_statistics_pie_chart');
            });
        });
