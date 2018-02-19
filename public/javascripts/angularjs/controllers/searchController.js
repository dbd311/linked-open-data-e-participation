angular.module('searchCtrl', [])
    .controller('searchCtrl', function ($filter, $scope, Config, Document, Search) {
            
        // Init the selected criteria
        $scope.selectedCriteria = '1';

        // Init the themes
        $scope.themes = '';

        // JQuery to create the propositions list of keywords (in the search)
        $(".keywords-search").keydown(function (e) {
            if (e.which === 40) {
                e.preventDefault();
                if ($scope.suggestions.length > count + 1) {
                    count = count + 1;
                    $(".theme-option")[count].focus();
                }
            }
            if (e.which === 38) {
                e.preventDefault();
                if (count > 0) {
                    count = count - 1;
                    $(".theme-option")[count].focus();
                }
            }
        });
        
        $(window).click(function() {
            $('#suggestions').hide();
        });

        /**
         * Pagination : function to load a clicked number of page
         */
        function loadPage() {
            $scope.documentsDisplay = [];
            var begin = (($scope.currentPage - 1) * $scope.nbElementsPerPage);
            var end = begin + Number($scope.nbElementsPerPage);
            for (var i = begin; i < end; i++) {
                if (i < $scope.documents.length) {
                    $scope.documentsDisplay.push($scope.documents[i]);
                }
            }
        };

        /**
         * Pagination : function to count the page number
         * @returns {Array|Number}
         */
        $scope.range = function () {
            if (typeof $scope.documents === 'undefined')
                return 0;
            return new Array(Math.ceil($scope.documents.length / $scope.nbElementsPerPage));
        };

        /**
         * Pagination : function to charge the page in parameter
         * @param {type} page
         */
        $scope.changePage = function (page) {
            $scope.currentPage = page;
            loadPage();
        };

        /**
         * Pagination : function to charge the previous page
         */
        $scope.previousPage = function () {
            $scope.currentPage = $scope.currentPage - 1;
            loadPage();
        };

        /**
         * Pagination : function to charge the next page
         */
        $scope.nextPage = function () {
            $scope.currentPage++;
            loadPage();
        };

        /**
         * Function the format a date
         * @param {String} date
         * @returns {String} date
         */
        $scope.formatDate = function (date) {
            if (date !== "")
                date = $filter('date')(new Date(date), "yyyy-MM-dd");
            return date;
        };

        // List of colors for pie chart
        var colorArray = ['#668014', '#949494', '#CD0000'];

        /**
         * Function which return the list of colors
         * @returns {Function}
         */
        $scope.colorFunction = function () {
            return function (d, i) {
                return colorArray[i];
            };
        };

        /**
         * Function which return the list of notes
         * @returns {Function}
         */
        $scope.xFunction = function () {
            return function (d) {
                return d.Note;
             };
        };

        /**
         * Function which return the list of values
         * @returns {Function}
         */
        $scope.yFunction = function () {
            return function (d) {
                return d.Value;
            };
        };
        
        /**
         * Function to return the pie chart tooltip
         * @returns {Function}
         */
        $scope.toolTipContentFunction = function(){
            return function(key, x, y, graph) {
                return  '<h3>' + key + '</h3>' + '<p>' +  x + ' % </p>';
                };
        };

        /**
         * Function to init the list of documents
         */
        $scope.initDocuments = function () {
            $scope.loadingSearch = true;
            $scope.currentPage = 1;
            $scope.nbElementsPerPage = "5";
            Document.get('date')
                    .success(function (docs) {
                        $scope.documents = docs;
                        for (var i = 0; i < $scope.documents.length; i++) {
                            var doc = $scope.documents[i];
                            doc.date = $scope.formatDate(doc.date);
                        }
                        loadPage();
                        $scope.loadingSearch = false;
                    }).error(function (data, status) {
                        $scope.loadingSearch = false;
            });
        };

        /**
         * Function to draw the document pie chart
         * @param {Document} document
         * @param {String} genericAct
         * @returns {Number}
         */
        $scope.drawChartForItem = function (document, genericAct) {
            document = document[0][0];
            Document.getNumbersOfTypeDocumentForItem(genericAct)
                    .success(function (data) {
                        var results = data.results.bindings;
                        for (var i in results) {
                            var result = results[i];
                            if (result.total.value > 0) {
                                document.dataPieChart = Document.getDataChartForItem(result);
                            }
                        }
                    });
            return 0;
        };

        /**
         * Function to find the most popular document for the current week
         */
        $scope.findMostPopularThisWeek = function () {
            Document.get('date')
                    .success(function (docs) {
                        $scope.documents = docs;
                        var docPopular = $scope.documents[0];
                        for (var i = 0; i < $scope.documents.length; i++) {
                            var document = $scope.documents[i];
                            if (parseInt(document.nbOfComments) > parseInt(docPopular.nbOfComments)) {
                                docPopular = document;
                            }
                        }
                        $scope.popularDoc = {};
                        var title = docPopular.subject.charAt(0).toUpperCase() + docPopular.subject.substring(1).toLowerCase();
                        $scope.popularDoc.title_popuplar_this_week = title;
                        $scope.popularDoc.short_title_popuplar_this_week = title.substring(0, 90);
                        if (title.length > 90) {
                            $scope.popularDoc.short_title_popuplar_this_week = $scope.popularDoc.short_title_popuplar_this_week.concat('...');
                        }
                        $scope.popularDoc.numberOfComments = docPopular.nbOfComments;
                        $scope.popularDoc.path = docPopular.path;
                        Document.getNumbersOfTypeDocumentAggregated(docPopular.genericActURI)
                                .success(function (data) {
                                    var results = data.results.bindings;
                                    for (var i in results) {
                                        var result = results[i];
                                        $scope.popularDoc.dataPieChart = [
                                            {Note: "Positive", Value: result.yes.value / result.total.value * 100},
                                            {Note: "Neutral", Value: result.mixed.value / result.total.value * 100},
                                            {Note: "Negative", Value: result.no.value / result.total.value * 100}
                                        ];
                                    }
                                });
                        loadPage();
                    });
        };

        /**
         * Function to calculate the statistics for a document
         * @param {Number} yes
         * @param {Number} mixed
         * @param {Number} no
         * @param {Number} total
         * @param {Document} document

         */
        $scope.calculateStatistics = function (yes, mixed, no, total, document) {
            document = document[0][0];
            var average_yes = 100 * yes / total;
            var average_mixed = 100 * mixed / total;
            var average_no = 100 * no / total;

            document.dataPieChart = [
                {Note: "positive", Value: average_yes},
                {Note: "neutral", Value: average_mixed},
                {Note: "negative", Value: average_no}
            ];
        };

        /**
         * Function which filtering the list of documents with the user search
         */
        $scope.filteredDocuments = function () {
            $scope.loadingSearch = true;
            $scope.currentPage = 1;
            var topics = constructTopicsChecked();
//            var years = constructYearsChecked();
            Document.filter(Config.lang, $scope.selectedCriteria, $scope.themes, $scope.dateFrom, $scope.dateTo, topics)
                    .success(function (docs) {
                        $scope.documents = docs;
                        for (var i = 0; i < $scope.documents.length; i++) {
                            $scope.documents[i].date = $scope.formatDate($scope.documents[i].date);
                        }
                        loadPage();
                        $scope.loadingSearch = false;
                    }).error(function (data, status) {
                alert('Cannot filter documents! Please checking document service!')
            });
        };
        
        /**
        * Remove extra space
        * @param {type} str
        * @returns {unresolved}
        */
        function removeExtraSpace(str){
            str = str.replace(/[\s]{2,}/g," ");
            str = str.replace(/^[\s]/, "");
            str = str.replace(/[\s]$/,"");
            return str;    
        }

        /* 
         * Keywords suggestion 
         */
        $scope.suggestions = [];
        $scope.getSuggestions = function () {
            var listThemes = $scope.themes.split(';');
            var pattern = listThemes[listThemes.length - 1];
            if(pattern.length > 1) {
                $('#suggestions').show();
                var data = {pattern: listThemes[listThemes.length - 1], lang: Config.lang};
                Search.getSuggestions(data).then(function (result) {
                        $scope.suggestions = result.data.results.bindings;
                    });
            } else {
                $scope.suggestions = [];
            }
            count = - 1;
        };

        /**
         * Function to add a new theme to the keywords list 
         * @param {String} theme
         */
        $scope.addTheme = function (theme) {
            var themes = $scope.themes.split(';');
            $scope.themes = '';
            for (var i = 0; i < themes.length - 1; i++) {
                $scope.themes += themes[i] + '; ';
            }
            // add this theme to the list
            if ($scope.themes.indexOf(theme) === -1) {
                $scope.themes += theme + ';';
            }
            $scope.themes = removeExtraSpace($scope.themes);
            $(".theme-input").value += ';';
            $(".theme-input").focus();
            $scope.suggestions = [];
        };

        $scope.total = [];
        /**
         * Return the number of documents
         * @param {Integer} year
         */
        $scope.getTotalDocuments = function (year) {
            var count = 0;
            Document.getTotalDocuments(year, Config.langDoc).success(function (data) {
                var results = data.results.bindings;
                for (var i in results) {
                    count = results[i].total.value;
                    $scope.total[year] = count;
                }
            });
        };
        
        /**
         * load eurovoc domains for a particular language
         */
        $scope.loadDomainNames = function () {
            Search.getDomainNames(Config.lang).success(function (data) {
                var domains = data.results.bindings;
                $scope.domains = domains;
            });
        };

        /**
         * load thesaurus names for a particular domain in a language
         * @param {String} domain
         */
        $scope.loadThesaurusNames = function (domain) {
            Search.getThesaurusNames(Config.lang, domain.domain_name.value).success(function (data) {
                var thesaurusNames = data.results.bindings;
                domain.thesaurusNames = thesaurusNames;
            });
        };
        $scope.collapseThesaurusNames = function (domain) {
            domain.thesaurusNames = [];
        };

        /**
         * load concept names for a particular thesaurus in a domain in a language
         * @param {String} thesaurus
         */
        $scope.loadConceptNames = function (thesaurus) {
            Search.getConceptNames(Config.lang, thesaurus.thesaurus_name.value).success(function (data) {
                var conceptNames = data.results.bindings;
                thesaurus.conceptNames = conceptNames;
            });
        };
        $scope.collapseConceptNames = function (thesaurus) {
            thesaurus.conceptNames = [];
        };

        /**
         * load related terms and narrowers for a particular concept in a thesaurus in a domain in a language
         * @param {String} concept
         */
        $scope.loadRelatedTermAndNarrowerNames = function (concept) {
            Search.getRelatedTermAndNarrowerNames(Config.lang, concept.concept_name.value).success(function (data) {
                concept.relatedTermAndNarrowerNames = data.results.bindings;
            });
        };
        $scope.collapseRelatedTermAndNarrowerNames = function (concept) {
            concept.relatedTermAndNarrowerNames = [];
        };

        /**
         * load related terms of narrowers for a particular concept in a thesaurus in a domain in a language
         * @param {String} narrower
         */
        $scope.loadRelatedTermOfNarrowerNames = function (narrower) {
            Search.getNarrowerNames(Config.lang, narrower.narrower_name.value).success(function (data) {
                narrower.relatedTermOfNarrowerNames = data.results.bindings;
            });
        };
        $scope.collapseRelatedTermOfNarrowerNames = function (narrower) {
            narrower.relatedTermOfNarrowerNames = [];
        };
        
        $scope.hasChildNarrowerTerm =  function(narrower) {
            Search.hasChildNarrowerTerm(Config.lang,narrower.narrower_name.value).success(function (data) {
                if (data === "true"){
                    narrower.hasChild = true;
                } else {
                    narrower.hasChild = false;
                }
            });
        };

        $scope.procedureYears = [];
        /**
         * Load procedures of the last five years
         */
        $scope.loadProceduresYears = function () {
            Search.getProcedureYears().success(function (data) {
                for (var i = 0; i < data.length; i++) {
                    var year = data[i][0];
                    var nb = data[i][1];
                    $scope.procedureYears.push({'value': year, 'nb' : nb, 'checked': true});
                }
            });
        };

        function constructTopicsChecked(){
            var tabTopicsChecked = [];
            for(var i = 0; i < $scope.domains.length; i++){
                var domain = $scope.domains[i];
                if (domain.checked === true){
                    tabTopicsChecked.push(domain.domain_name.value);
                }
                if (domain.thesaurusNames) {
                    for(var j = 0; j < domain.thesaurusNames.length; j++){
                        var thesaurus = domain.thesaurusNames[j];
                        if(thesaurus.checked === true) {
                             tabTopicsChecked.push(thesaurus.thesaurus_name.value);
                        }
                        if (thesaurus.conceptNames) {
                            for(var k = 0; k < thesaurus.conceptNames.length; k++){
                                var concept = thesaurus.conceptNames[k];
                                if(concept.checked === true) {
                                     tabTopicsChecked.push(concept.concept_name.value);
                                }
                                if (concept.relatedTermAndNarrowerNames) {
                                    for(var l = 0; l < concept.relatedTermAndNarrowerNames.length; l++){
                                        var relatedTermAndNarrower = concept.relatedTermAndNarrowerNames[l];
                                        if(relatedTermAndNarrower.checked === true) {
                                             tabTopicsChecked.push(relatedTermAndNarrower.narrower_name.value);
                                        }
                                        if (relatedTermAndNarrower.relatedTermOfNarrowerNames) {
                                            for(var m = 0; m < relatedTermAndNarrower.relatedTermOfNarrowerNames.length; m++){
                                                var relatedTermOfNarrower = relatedTermAndNarrower.relatedTermOfNarrowerNames[m];
                                                if(relatedTermOfNarrower.checked === true) {
                                                     tabTopicsChecked.push(relatedTermOfNarrower.narrower_of_narrower_name.value);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            return tabTopicsChecked;
        };

//        function constructYearsChecked(){
//            var tabYearsChecked = [];
//            for(var i = 0; i < $scope.procedureYears.length; i++){
//                var procedureYear = $scope.procedureYears[i];
//                if (procedureYear.checked === true){
//                    tabYearsChecked.push(procedureYear.value);
//                }
//            }
//            return tabYearsChecked;
//        };
    });