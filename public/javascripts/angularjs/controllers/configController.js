angular.module('configCtrl', [])

        .controller('configCtrl', function ($location, $scope, Config, Document) {

            /**
             * 
             * @param {type} langDoc eli lang code (3 chars code, e.g. eng, fra, etc.)
             * @param {type} lang interface language (2 chars code, e.g. en, fr, etc.)
             * @param {type} sparqlEndPoint
             * @param {type} siteName
             * @param {type} cellarSparqlEndPoint
             *
             */
            $scope.initialize = function (langDoc, lang, sparqlEndPoint, siteName, cellarSparqlEndPoint) {

                $scope.config = Config;
                $scope.config.langDoc = langDoc;
                $scope.config.lang = returnLang(lang);
                Config.loadLanguages().success(function (data) {
                    $scope.config.listLanguages = data;
                });
                $scope.config.nationality = {};
                Config.loadNationalities().success(function (data) {
                    $scope.config.listNationalities = data;
                });
                $scope.config.sparqlEndPoint = sparqlEndPoint;
                $scope.config.siteName = siteName;
                $scope.config.cellarSparqlEndPoint = cellarSparqlEndPoint;
                // TODO : PASSED IN PARAMETERS beginUri AND docCode
                //$scope.config.beginUri = '/eli/PROP_DEC_NO_ADDRESSEE/';
                //$scope.config.docCode = 'PROP_DEC_NO_ADDRESSEE';

                $('#myProfile').popover({
                    html: true,
                    container: '#wrapper',
                    placement: 'auto bottom',
                    content: function () {
                        return $('#account-info').html();
                    }
                });
            };

            $scope.loadConfiguration = function () {
                Config.loadEnvParams().success(function (data) {
                    $scope.params = data.params;
                });
            };



            /***
             * Redirects the user to the website corresponding to the selected language
             */
            $scope.changeLang = function () {
                Config.getLangCode($scope.config.lang).success(function (data) {
                    var hl = data.toLowerCase();
                    var path = $location.$$absUrl;
                    var splitPath = path.split("?");
                    if (splitPath.length === 1) {
                        path = $location.$$absUrl + "?lang=" + $scope.config.lang;
                    } else {
                        path = splitPath[0];
                        var splitParams = splitPath[1].split("&");
                        if (splitParams.length === 1) {
                            var splitValues = splitPath[1].split("=");
                            if (splitValues[0] === "lang") {
                                path += "?lang=" + $scope.config.lang;
                            } else {
                                path += splitPath[1] + "&lang=" + $scope.config.lang;
                            }
                        } else {
                            var splitFirstValue = splitParams[0].split("=");
                            if (splitFirstValue[0] === "lang") {
                                path += "?lang=" + $scope.config.lang;
                            } else if (splitFirstValue[0] === "hl") {
                                path += "?hl=" + hl;
                            } else {
                                path += "?" + splitParams[0];
                            }
                            for (var i = 1; i < splitParams.length; i++) {
                                var splitValues = splitParams[i].split("=");
                                if (splitValues[0] === "lang") {
                                    path += "&lang=" + $scope.config.lang;
                                } else if (splitValues[0] === "hl") {
                                    path += "&hl=" + hl;
                                } else {
                                    path += "&" + splitParams[i];
                                }
                            }
                        }
                        if (path.split('&hl=').length > 1) {
                            var splitUrl = path.split('.json');
                            path = splitUrl[0].substring(0, splitUrl[0].length - 2) + $scope.config.lang.toUpperCase() + '.json' + splitUrl[1];
                        }
                    }
                    var splitFile = path.split('path=');
                    if (splitFile.length > 1) {
                        var file = splitFile[1].split('&')[0];
                        Document.existFile(file).success(function(exist) {
                            if (exist === 'true'){
                                location.href = path;
                            } else {
                                var splitUrl = path.split('.json&hl=');
                                var splitUrlLang = splitUrl[1].split('&lang=');
                                path = splitUrl[0].substring(0, splitUrl[0].length - 2) + 'EN.json&hl=eng&lang=' + splitUrlLang[1];
                                location.href = path;
                            }
                        });
                    } else {
                        location.href = path;
                    }
                });
            };
            
            $scope.loadConfiguration = function () {
                Config.loadEnvParams().success(function (data) {
                    $scope.params = data.params;
                });
            };

            function returnLang(lang) {
                var lang = lang;
                var path = $location.$$absUrl;
                var splitPath = path.split("?");
                if (splitPath.length > 1) {
                    var splitParams = splitPath[1].split("&");
                    if (splitParams.length === 1) {
                        var splitValues = splitParams[0].split("=");
                        if (splitValues[0] === "lang") {
                            lang = splitValues[1];
                        }
                    } else {
                        for (var i = 1; i < splitParams.length; i++) {
                            var splitValues = splitParams[i].split("=");
                            if (splitValues[0] === "lang") {
                                lang = splitValues[1];
                            }
                        }
                    }
                }
                return lang;
            }
            ;

            $scope.loadConfiguration = function () {
                Config.loadEnvParams().success(function (data) {
                    $scope.params = data.params;                    
                });
            };
        });