angular.module('documentCtrl', [])
        .controller('documentCtrl', function ($filter, $scope, $sce, Config, Comment, Document) {
            $scope.nbSectionsLoad = 5;
            $scope.nbFirstBranches = $scope.nbSectionsLoad;
            $scope.cptSection = 0;

            $scope.positionComments = function (start) {
                if (document.getElementById('col-comments')) {
                    var topSection = document.getElementById('col-section').getBoundingClientRect().top;
                    var topComment = 0;
                    var heightComment = 0;
                    if (topSection < 0) {
                        topComment = (-1 * topSection) + 10;
                        var sizeFooter = 0;
                        if ($(window).height() >= document.getElementById('footer').getBoundingClientRect().top - 10) {
                            sizeFooter = $(window).height() - document.getElementById('footer').getBoundingClientRect().top + 10;
                        }
                        heightComment = $(window).height() - 20 - sizeFooter;
                    } else {
                        topComment = 0;
                        if (!start && topSection < 11) {
                            topComment = 10 - topSection;
                            heightComment = $(window).height() - 20;
                        } else {
                            heightComment = $(window).height() - $('#col-comments').offset().top + $(document).scrollTop() - 10;
                        }
                    }
                    if (heightComment > $('#col-section').height()) {
                        heightComment = $('#col-section').height();
                    }
                    document.getElementById('col-comments').style.top = topComment + "px";
                    document.getElementById('col-comments').style.height = heightComment + "px";
                }
            };

            $(window).scroll(function () {
                $scope.positionComments();
//                checkPositionSection();
            });

            $(window).resize(function () {
                $scope.positionComments();
//                checkPositionSection();
            });

            $scope.commentEmpty = true;
            $scope.changeCommentArea = function () {
                if ($scope.commentData.commentTextBox === '') {
                    $scope.commentEmpty = true;
                } else {
                    $scope.commentEmpty = false;
                }
            };

            $scope.replyEmpty = true;
            $scope.changeReplyArea = function () {
                if ($scope.reply.replyText === '') {
                    $scope.replyEmpty = true;
                } else {
                    $scope.replyEmpty = false;
                }
            };

            Config.loadLanguages().success(function (data) {
                $scope.listLanguages = data;
            });

            /**
             * Function for check if the user is connected
             * @param {String} userId
             */
            $scope.checkLogin = function (userId) {
                var e = document.getElementById('user_not_login');
                if (e === null) {
                    $scope.statusCommentLogin = 'hidden';
                    $scope.statusComment = '';
                    $scope.userId = userId;
                } else {
                    $scope.statusCommentLogin = '';
                    $scope.statusComment = 'hidden';
                }
            };


            /**
             * Function for charge the json data and the metadata in the document object
             * @param {String} path
             * @param {String} langDoc
             * @param {String} userId
             * @param {String} sectionId
             */
            $scope.loadDocument = function (path, langDoc, userId, sectionId) {
                $scope.checkLogin(userId);
                $scope.nbTotalComments = 0;
                $scope.collapseAll = false;
                // Update document language selector
                $scope.languageDocument = langDoc;

                // Load data json
                Document.loadDocument(path)
                        .success(function (data) {
                            $scope.document = data;

                            if (typeof $scope.document.num === 'undefined') {
                                $scope.document.num = 'INFOHUB';
                            }
                            $scope.document.docID = 'comnat:COM_' + $scope.document.year + '_' + $scope.document.num + '_FIN';
                            $scope.document.docCode = 'NA';

                            // Load metadata
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

                                            Comment.getNbComments('/eli/' + $scope.document.docCode + '/' + $scope.document.uri, false, userUri)
                                                    .success(function (data) {
                                                        $scope.document.nbComments = data.results.bindings[0].nbComments.value;
                                                    });
                                            constructListSections();
                                            $scope.displayAggregatedStatistics();
                                            $scope.sizeDocument = "col-xs-12";

                                            if (typeof ($scope.document.preamble) !== 'undefined') {
                                                $scope.document.preamble.collapsed = true;
                                            }
                                            if (sectionId === '') {
                                                $scope.loadSection($scope.document, false);
                                            } else {
                                                $scope.loadSection(searchSectionById(sectionId), true);
                                            }
                                        }
                                    });
                            // Load the document themes
                            Document.loadThemes($scope.document.docID, $scope.document.eli_lang_code)
                                    .success(function (themes) {
                                        if (themes.length > 0) {
                                            $scope.document.topics = themes;
                                        }
                                    });
                            // Extract folder from path
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
            /**
             * Function for close all the section
             * @param {boolean} collapsed
             */
            $scope.collapsedAll = function (collapsed) {
                if (collapsed === true) {
                    if (typeof ($scope.document.preamble) !== 'undefined')
                        $scope.document.preamble.collapsed = true;

                    for (var i = 0; i < $scope.listSections.length; i++) {
                        $scope.listSections[i].collapsed = true;
                        $scope.collapseAll = false;
                    }
                } else {
                    if (typeof ($scope.document.preamble) !== 'undefined')
                        $scope.document.preamble.collapsed = false;

                    for (var i = 0; i < $scope.listSections.length; i++) {
                        $scope.listSections[i].collapsed = false;
                        $scope.collapseAll = true;
                    }
                }
            };

            /**
             * Function for format a date
             * @param {String} date
             * @returns {Date} date
             */
            $scope.formatDate = function (date) {
                date = $filter('date')(new Date(date), "yyyy-MM-dd");
                return date;
            };

            /**
             * Function to get date time info from timestamp
             * @param {String} timestamp
             * @returns {String}
             */
            function getDateTimeFromTimeStamp(timestamp) {
                return timestamp.substring(0, 4) + "-" + timestamp.substring(4, 6) + "-" + timestamp.substring(6, 8) + " - " +
                        timestamp.substring(8, 10) + ":" + timestamp.substring(10, 12);
            }

            /**
             * Function to get timestamp from datetime
             * @param {String} datetime
             * @param {String} type
             * @returns {String}
             */
            function getTimeStampFromDateTime(datetime, type) {
                var dateParts = datetime.split('-');
                return dateParts[0] + dateParts[1] + dateParts[2] + type;
            }

            // Table of colors of pie chart
            var colorArray = ['#668014', '#949494', '#CD0000'];

            /**
             * Function which return a table of colors for pie chart
             * @returns {Function}
             */
            $scope.colorFunction = function () {
                return function (d, i) {
                    return colorArray[i];
                };
            };

            /**
             * Function which return a table of notes for pie chart
             * @returns {Function}
             */
            $scope.xFunction = function () {
                return function (d) {
                    return d.Note;
                };
            };

            /**
             * Function which return a table of values for pie chart
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
            $scope.toolTipContentFunction = function () {
                return function (key, x, y, graph) {
                    return  '<h3>' + key + '</h3>' + '<p>' + x + ' % </p>';
                };
            };

            /**
             * Function which return the data section with the id
             * @param {Section} section
             * @returns {Section} section
             */
            function searchSection(section) {
                for (var i = 0; i < $scope.listSections.length; i++) {
                    if (section.id === $scope.listSections[i].id) {
                        return $scope.listSections[i];
                    }
                }
            };

            /**
             * Function which return the data section with the id
             * @param {Section} id
             * @returns {Section} section
             */
            function searchSectionById(id) {
                for (var i = 0; i < $scope.listSections.length; i++) {
                    if (id === $scope.listSections[i].id) {
                        return $scope.listSections[i];
                    }
                }
            };

            /**
             * Function for calculate the section score
             * @param {Comment[]} comments
             * @param {Section} section
             */
            function calculScore(comments, section) {
                var total = comments.length;
                var score = 0;
                if (total !== 0) {
                    var points = 0;
                    for (var i = 0; i < total; i++) {
                        var note = comments[i].note.value;
                        if (note === 'yes') {
                            points++;
                        } else if (note === 'no') {
                            points--;
                        }
                    }
                    score = points / total;
                }
                // The score is green if there more 50% positive comments, red if less 50% and gray if exactly 50%
                if (score < 0) {
                    section.score = 'negative-score';
                } else if (score > 0) {
                    section.score = 'positive-score';
                } else {
                    section.score = 'neutral-score';
                }
            }

            /**
             * Load user activities on the whole document
             */
            function loadDocumentActivities() {

                $scope.document.nbAmmendements = 0;
                $scope.document.filterSort = 'date';
                $scope.document.filterPositive = true;
                $scope.document.filterNeutral = true;
                $scope.document.filterNegative = true;
                $scope.document.filterLanguage = 'all';


                Comment.getComments('/eli/' + $scope.document.docCode + '/' + $scope.document.uri)
                        .success(function (data) {
                            var comments = data.results.bindings;
                            calculScore(comments, $scope.document);
                            $scope.document.nbComments = comments.length;
                            for (var i = 0; i < $scope.document.nbComments; i++) {
                                $scope.document.nbAmmendements += parseInt(comments[i].num_insertion.value) + parseInt(comments[i].num_deletion.value) + parseInt(comments[i].num_substitution.value);
                            }
                        });
                $scope.loadSection($scope.document);
            }
            ;

            /**
             * Recursively function for building document first branch tree including top section and subsections as well as their activities (comments, amendments ...)
             * @param {Section} root
             * @param {Integer} n maxium number of branches
             */
            function buildDocumentFirstBranches(root, n)
            {
                root.filterSort = 'date';
                root.filterPositive = true;
                root.filterNeutral = true;
                root.filterNegative = true;
                root.filterLanguage = 'all';
                if($scope.cptSection < n) {
                    root.loaded = true;
                    root.nbAmmendements = 0;
                    Comment.getComments('/eli/' + $scope.document.docCode + '/' + root.uri)
                        .success(function (data) {
                            var comments = data.results.bindings;
                            calculScore(comments, root);
                            root.nbComments = comments.length;
                            for (var i = 0; i < root.nbComments; i++) {
                                root.nbAmmendements += parseInt(comments[i].num_insertion.value) + parseInt(comments[i].num_deletion.value) + parseInt(comments[i].num_substitution.value);
                            }
                        });
                }
                $scope.listSections.push(root);
                $scope.cptSection++;

                if (root.sections) {
                    var i = 0;
                    for (; i < root.sections.length; i++) {
                        var section = root.sections[i];
                        section.collapsed = true;
                        buildDocumentFirstBranches(section, n);
                    }
                }
            };
            
            $scope.loadOtherSections = function(idSection){
                if ($scope.listSections[idSection] && $scope.nbFirstBranches + $scope.nbSectionsLoad > idSection){
                    var section = $scope.listSections[idSection];
                    section.nbAmmendements = 0;
                    section.nbComments = 0;
                    Comment.getComments('/eli/' + $scope.document.docCode + '/' + section.uri)
                        .success(function (data) {
                            var comments = data.results.bindings;
                            calculScore(comments, section);
                            section.nbComments = comments.length;
                            for (var i = 0; i < section.nbComments; i++) {
                                section.nbAmmendements += parseInt(comments[i].num_insertion.value) + parseInt(comments[i].num_deletion.value) + parseInt(comments[i].num_substitution.value);
                            }
                            $scope.positionComments();
                            section.loaded = true;
                            $scope.loadOtherSections(idSection+1);
                        });
                } else {
                    $scope.nbFirstBranches = $scope.nbFirstBranches + $scope.nbSectionsLoad;
                    if(idSection >= $scope.listSections.length - 1){
                        $scope.sectionsLoaded = true;
                    }
                }
            };

            /**
             * Recursively function for building document tree including top section and subsections
             * @param {Section} root
             */
            function buildDocumentTree(root)
            {
                root.nbAmmendements = 0;
                root.filterSort = 'date';
                root.filterPositive = true;
                root.filterNeutral = true;
                root.filterNegative = true;
                root.filterLanguage = 'all';

                $scope.listSections.push(root);

                if (root.sections) {
                    for (var i = 0; i < root.sections.length; i++) {
                        var section = root.sections[i];
                        section.collapsed = true;
                        buildDocumentTree(section);
                    }
                }
            }
            ;

            /**
             * Method for construct the section tree
             */
            function constructListSections() {
                $scope.listSections = [];

                loadDocumentActivities($scope.document);

                buildDocumentFirstBranches($scope.document, $scope.nbFirstBranches);

//                buildDocumentTree($scope.document);
            }
            ;

            /**
             * Function which returns the class css corresponding to the selected section
             * @param {Section} section
             * @returns {String}
             */
            $scope.selectSection = function (section) {
                if ($scope.section) {
                    if (section === 'all') {
                        if (!$scope.isSection) {
                            return "selected-section";
                        } else {
                            return "hover-section";
                        }
                    } else if (section.id === $scope.section.id) {
                        return "selected-section";
                    } else {
                        return "hover-section";
                    }
                } else {
                    return "hover-section";
                }

            };

            /**
             * Function for charge the section
             * @param {Section} section
             * @param {boolean} isSection
             */
            $scope.loadSection = function (section, isSection) {
                $scope.loadComments = true;
                $scope.commentSelected = null;
                $scope.commentData.commentTextBox = '';
                $scope.tempCurrentPage = 0;
                loadDataSection(section, isSection);
                $scope.collaspedElement(false, section, false);

            };

            /**
             * Function for charge the data section
             * @param {Section} section
             * @param {boolean} isSection
             */
            function loadDataSection(section, isSection) {
                if (typeof (section) === 'undefined')
                    return;

                $scope.creationComment = false;
                for (var i = 0; i < $scope.listSections.length; i++) {
                    $scope.listSections[i].amendmentToEditCK = null;
                }
                $scope.sizeDocument = "col-xs-7";
                if (isSection) {
                    $scope.section = searchSection(section);
                    $scope.isSection = true;
                } else {
                    $scope.isSection = false;
                    $scope.section = $scope.document;
                }
                if (typeof section.filterAuthor === 'undefined') {
                    section.filterAuthor = '';
                }
                if (typeof section.filterSearch === 'undefined') {
                    section.filterSearch = '';
                }
                if (typeof section.filterHashtags === 'undefined') {
                    section.filterHashtags = '';
                }
                var dateFrom = '00000000000000';
                if (typeof section.filterDateFrom !== 'undefined' && section.filterDateFrom !== '') {
                    dateFrom = getTimeStampFromDateTime(section.filterDateFrom, '000000');
                }
                var dateTo = '99999999999999';
                if (typeof section.filterDateTo !== 'undefined' && section.filterDateTo !== '') {
                    dateTo = getTimeStampFromDateTime(section.filterDateTo, '999999');
                }
                var userUri = Config.siteName + '/user/id_user_' + $scope.userId;
                Comment.filterComments('/eli/' + $scope.document.docCode + '/' + section.uri, section.onlyMyComments, userUri, section.filterSearch, dateFrom, dateTo, section.filterAuthor, section.filterSort, section.filterPositive, section.filterNeutral, section.filterNegative, section.filterAmendments, section.filterLanguage, section.filterHashtags)
                        .success(function (data) {
                            formatComments(section, data.results.bindings);
                        });
            }
            ;

            function formatComments(section, comments) {
                $scope.comments = comments;
                $scope.nbCommentsSection = $scope.comments.length;
                section.nbComments = $scope.comments.length;
                calculScore($scope.comments, section);
                section.nbAmmendements = 0;
                for (var i = 0; i < $scope.comments.length; i++) {
                    var comment = $scope.comments[i];
                    comment.created_at.value = getDateTimeFromTimeStamp(comment.created_at.value);
                    if (comment.amended_at)
                        comment.amended_at.value = getDateTimeFromTimeStamp(comment.amended_at.value);
                    var str = comment.comment.value.replace(/%0A/g, "<br />");
                    comment.comment.value = unescape(str);
                    section.nbAmmendements += parseInt($scope.comments[i].num_insertion.value) + parseInt($scope.comments[i].num_deletion.value) + parseInt($scope.comments[i].num_substitution.value);
                    comment.edit = false;
                }
                countAmendements();
                section.currentPage = $scope.tempCurrentPage;
                section.pageSize = 5;
                section.numberOfPages = function () {
                    return Math.ceil($scope.comments.length / section.pageSize);
                };
                $scope.displayStatisticsSection();
                $scope.displayAggregatedStatistics();
                $scope.positionComments(true);
                $scope.loadComments = false;
            };

            /**
             * Function load replies
             * @param {Comment} comment
             * @param {boolean} close
             */
            $scope.loadReplies = function (comment, close) {
                $scope.tempCurrentPageReply = 0;
                $scope.reply.replyText = '';
                getRepliedPosts(comment, close);
            };

            /**
             * Function to get replied posts of a post
             * @param {Comment} comment
             * @param {boolean} close
             */
            function getRepliedPosts(comment, close) {
                if (!comment.post) {
                    comment = comment[0][0];
                }
                Comment.getReplies(comment)
                        .success(function (data) {
                            comment.replies = data.results.bindings;
                            for (var i = 0; i < comment.replies.length; i++) {
                                comment.replies[i].created_at.value = getDateTimeFromTimeStamp(comment.replies[i].created_at.value);
                                if (comment.replies[i].amended_at)
                                    comment.replies[i].amended_at.value = getDateTimeFromTimeStamp(comment.replies[i].amended_at.value);
                            }
                            comment.totalReplies.value = comment.replies.length;
                            seeAmendment();
                            comment.currentPage = $scope.tempCurrentPageReply;
                            comment.pageSize = 5;
                            comment.numberOfPages = function () {
                                return Math.ceil(comment.replies.length / comment.pageSize);
                            };
                            $scope.loadingSaveReply = false;
                        });

                if (($scope.commentSelected && $scope.commentSelected === comment)) {
                    if (close) {
                        $scope.commentSelected = null;
                    }
                } else {
                    $scope.commentSelected = null;
                    amendment(comment);
                }
            }
            ;

            /**
             * Function te get the amendment data
             * @param {Comment} comment
             * @returns {undefined}
             */
            function amendment(comment) {
                $scope.commentSelected = comment;
                $scope.commentSelected.modifications = [];
                Comment.getAmendments(comment)
                        .success(function (data) {
                            var allModifications = data.results.bindings;
                            for (var i = 0; i < allModifications.length; i++) {
                                var post = allModifications[i].post.value;
                                var langModif = post.substring(post.length - 3, post.length);
                                if (langModif === $scope.languageDocument) {
                                    $scope.commentSelected.modifications.push(allModifications[i]);
                                }
                            }

                            if ($scope.commentSelected.modifications.length === 0) {
                                $scope.commentSelected.amendment = $scope.section.content;
                            } else {
                                $scope.commentSelected.amendment = viewAmendment($scope.commentSelected.modifications);
                            }
                        });
            }
            ;

            /**
             * Function which return the css class for the selected comment
             * @param {Comment} comment
             * @returns {String}
             */
            $scope.selectComment = function (comment) {
                if ($scope.commentSelected === comment) {
                    return "selected-comment";
                } else {
                    return "no-selected-comment";
                }
            };

            // New user comment
            $scope.commentData = {
                commentType: 'mixed',
                commentTextBox: '',
                container: {}
            };

            /**
             * Function to change type of comment
             * @param {String} type
             */
            $scope.changeType = function (type) {
                if (type === 'yes') {
                    if ($scope.commentData.commentType === 'yes') {
                        $scope.commentData.commentType = 'mixed';
                        $scope.cssYes = '';
                        $scope.cssNo = '';
                    } else {
                        $scope.commentData.commentType = 'yes';
                        $scope.cssYes = 'value-yes-checked';
                        $scope.cssNo = '';
                    }
                } else {
                    if ($scope.commentData.commentType === 'no') {
                        $scope.commentData.commentType = 'mixed';
                        $scope.cssYes = '';
                        $scope.cssNo = '';
                    } else {
                        $scope.commentData.commentType = 'no';
                        $scope.cssYes = '';
                        $scope.cssNo = 'value-no-checked';
                    }
                }
            };

            // New user reply
            $scope.reply = {
                replyType: 'mixed'
            };

            /**
             * Function to change type of comment
             * @param {String} type
             */
            $scope.changeTypeReply = function (type) {
                if (type === 'yes') {
                    if ($scope.reply.replyType === 'yes') {
                        $scope.reply.replyType = 'mixed';
                        $scope.reply.cssYesReply = '';
                        $scope.reply.cssNoReply = '';
                    } else {
                        $scope.reply.replyType = 'yes';
                        $scope.reply.cssYesReply = 'value-yes-checked';
                        $scope.reply.cssNoReply = '';
                    }
                } else {
                    if ($scope.reply.replyType === 'no') {
                        $scope.reply.replyType = 'mixed';
                        $scope.reply.cssYesReply = '';
                        $scope.reply.cssNoReply = '';
                    } else {
                        $scope.reply.replyType = 'no';
                        $scope.reply.cssYesReply = '';
                        $scope.reply.cssNoReply = 'value-no-checked';
                    }
                }
            };

            /***
             * Function to add a comment for a section
             * @param {Section} section
             */
            $scope.addUserCommentSection = function (section) {
                document.getElementById('saveCommentsId').hidden = false;
                document.getElementById('creationCommentId').hidden = true;
                document.getElementById('showCommentsId').hidden = true;
                if ($scope.commentData.commentTextBox !== '') {
                    $scope.commentData.commentTextBox = $scope.commentData.commentTextBox.replace(/(?:\r\n|\r|\n)/g, " ");
                    $scope.commentData.commentTextBox = escape($scope.commentData.commentTextBox);
                    $scope.commentData.commentTextBox = $scope.commentData.commentTextBox.split("\\").join("\\\\");
                    $scope.commentData.id_fmx_element = section.id;
                    $scope.commentData.container.id = $scope.document.id;
                    $scope.commentData.num = $scope.document.num;
                    $scope.commentData.uri = section.uri;
                    $scope.commentData.year = $scope.document.year;
                    $scope.commentData.doc_code = $scope.document.docCode;

                    $scope.commentData.eli_language_code = $scope.document.eli_lang_code;
                    if (section.content) {
                        $scope.commentData.modifications = constructModifications(false);
                    }
                    // Save the comment data
                    Comment.save($scope.commentData)
                            .success(function () {
                                $scope.tempCurrentPage = 0;
                                loadDataSection(section, $scope.isSection);
                                $scope.displayAggregatedStatistics();
                                $scope.commentEmpty = true;
                                $scope.commentData.commentType = 'mixed';
                                var tmpText = $scope.commentData.commentTextBox;
                                $scope.commentData.commentTextBox = '';
                                $scope.cssYes = '';
                                $scope.cssNo = '';
                                $scope.commentSelected = null;
                                document.getElementById('saveCommentsId').hidden = true;
                                document.getElementById('creationCommentId').hidden = false;
                                document.getElementById('showCommentsId').hidden = false;
                                var dataTranslation = {
                                    uri: $scope.commentData.uri,
                                    textToTranslate: tmpText,
                                    sourceLanguage: $scope.commentData.eli_language_code
                                };
                                Comment.backTranslation(dataTranslation).success(function () {
                                });
                            });
                }
            };

            /***
             * Function to add a reply for a comment
             * @param {Comment} comment
             */
            $scope.addUserReply = function (comment) {
                if ($scope.reply.replyText !== '') {
                    $scope.loadingSaveReply = true;
                    $scope.reply.replyText = $scope.reply.replyText.replace(/(?:\r\n|\r|\n)/g, " ");
                    $scope.reply.replyText = escape($scope.reply.replyText);
                    $scope.reply.replyText = $scope.reply.replyText.split("\\").join("\\\\");
                    $scope.reply.containerId = '/eli/' + $scope.document.docCode + '/' + $scope.document.uri;
                    $scope.reply.postUri = comment[0][0].post.value;
                    $scope.reply.lang = $scope.languageDocument;
                    Comment.saveReply($scope.reply)
                            .success(function () {
                                $scope.tempCurrentPageReply = 0;
                                $scope.loadReplies(comment, false);
                                var tmpText = $scope.reply.replyText;
                                $scope.reply.replyText = '';
                                $scope.replyEmpty = true;
                                $scope.reply.replyType = 'mixed';
                                $scope.reply.cssYesReply = '';
                                $scope.reply.cssNoReply = '';
                                $scope.loadingSaveReply = false;
                                var dataTranslation = {
                                    uri: $scope.reply.containerId,
                                    textToTranslate: tmpText,
                                    sourceLanguage: $scope.reply.lang
                                };
                                Comment.backTranslation(dataTranslation).success(function () {
                                });
                            });
                }
            };

            /**
             * Function to display a pie chart for a section
             */
            $scope.displayStatisticsSection = function () {
                var total = $scope.comments.length;
                if (total > 0) {
                    var yes = 0;
                    var mixed = 0;
                    var no = 0;
                    for (var i = 0; i < total; i++) {
                        var result = $scope.comments[i];
                        if (result.note.value === 'yes') {
                            yes++;
                        } else if (result.note.value === 'mixed') {
                            mixed++;
                        } else {
                            no++;
                        }
                    }
                    $scope.dataPieChartSection = [
                        {Note: "Positive", Value: (yes / total) * 100},
                        {Note: "Neutral", Value: (mixed / total) * 100},
                        {Note: "Negative", Value: (no / total) * 100}
                    ];
                } else {
                    $scope.dataPieChartSection = [];
                }
            };

            /***
             * Function to display a pie chart for the document
             */
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
                                    $scope.nbTotalComments = result.total.value;

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
                            document.getElementById('allelements').hidden = false;
                            document.getElementById('loading').hidden = true;
                        });
            };

            function getParameter(param) {
                var vars = {};
                window.location.href.replace(location.hash, '').replace(
                        /[?&]+([^=&]+)=?([^&]*)?/gi, // regexp
                        function (m, key, value) { // callback
                            vars[key] = value !== undefined ? value : '';
                        }
                );

                if (param) {
                    return vars[param] ? vars[param] : null;
                }
                return vars;
            }

            /**
             * Function to change the document language
             */
            $scope.changeDocumentLanguage = function () {
                var newDocLang = $scope.languageDocument.toLowerCase();
                Document.getPath($scope.document.docID, newDocLang).success(function (data) {
                    var results = data.results.bindings;
                    for (var i in results) {
                        var result = results[i];
                        var path = result.path.value;
                        location.href = '/lod/documents/displayDoc?path=' + path + '&hl=' + newDocLang + '&lang=' + Config.lang;
                    }
                    if (results.length === 0) {
                        alert("This document is not available in this language, he will be open in english.");
                        $scope.languageDocument = "eng";
                        $scope.changeDocumentLanguage();
                    }
                });
            };

            /**
             * Function for close a section
             * @param {type} event
             * @param {type} section
             * @param {type} collapsed
             */
            $scope.collaspedElement = function (event, section, collapsed) {
                if (typeof (section) === 'undefined')
                    return;
                section.collapsed = collapsed;
                if (event !== false)
                    event.stopPropagation();
            };

            /**
             * Function to init a boolean creation comment
             * @param {boolean} creation
             */
            $scope.formCreation = function (creation) {
                $scope.creationComment = creation;
            };

            /**
             * Function to cancel the creation comment
             * @param {Section} section
             */
            $scope.cancelAdd = function (section) {
                $scope.commentData.commentTextBox = '';
                $scope.formCreation(false);
                $scope.commentSelected = null;
                if (section.num) {
                    loadDataSection(section, false);
                } else {
                    loadDataSection(section, true);
                }
            };

            /**
             * Function which return a text with tack changes style
             * @param {String} original
             * @param {String} edited
             * @returns {String} result
             */
            function trackChanges(original, edited) {
                original = original.split("<p>").join("");
                original = original.split("</p>").join("\n");
                original = original.split("<br>").join("\n");
                edited = edited.split("<p>").join("");
                edited = edited.split("</p>").join("\n");
                edited = edited.split("<br>").join("\n");
                if (original.split(" ")[0] !== edited.split(" ")[0])
                    edited = ' ' + edited;
                var result = diffString(original, edited);
                result = result.split("\n").join(" ");
                if (result[0] === ' ')
                    result = result.substring(1, result.length);
                result = result.split("  ").join(" ");
                return result;
            }
            ;

            /**
             * Function for count the nb of occurence in the string
             * @param {String} char
             * @param {String} string
             * @returns {Integer} nb
             */
            function countOccurrenceChar(char, string) {
                var word = string.split(char);
                var nb = word.length - 1;
                return nb;
            }

            /**
             * Remove extra space
             * @param {type} str
             * @returns {unresolved}
             */
            function removeExtraSpace(str) {
                str = str.replace(/[\s]{2,}/g, " ");
                str = str.replace(/^[\s]/, "");
                str = str.replace(/[\s]$/, "");
                return str;
            }

            /**
             * Function for display a amendment with track changes style
             * @param {Array} modifications
             * @returns {String} text
             */
            function viewAmendment(modifications) {
                modifications = sortModifications(modifications);
                var text = CKEDITOR.instances.originalText2.getData();
                text = text.split("<p>").join("");
                text = text.split("</p>").join("~");
                text = text.split("&nbsp;").join(" ");
                text = text.split("&lsquo;").join("‘");
                text = text.split("&rsquo;").join("’");
                text = text.split("~ ~").join("~~");
                var cursor = 0;
                for (var i = 0; i < modifications.length; i++) {
                    var begin = parseInt(modifications[i].begin_position.value);
                    var end = -1;
                    if (typeof modifications[i].end_position !== 'undefined') {
                        end = parseInt(modifications[i].end_position.value);
                    }
                    var str = "";
                    if (typeof modifications[i].new_content !== 'undefined') {
                        str = modifications[i].new_content.value;
                        var nbTagOpen = countOccurrenceChar("<", str);
                        var nbTagClose = countOccurrenceChar(">", str);
                        cursor = cursor + (nbTagOpen * 3) + (nbTagClose * 3);
                        str = str.split("&amp;lt;").join("&lt;");
                        str = str.split("&amp;gt;").join("&gt;");
                    }
                    if (end !== -1 && str !== "") {
                        text = text.substring(0, begin + cursor) + '<del>' + text.substring(begin + cursor, end + cursor + 1) + '</del>' + text.substring(end + cursor + 1, text.length + cursor);
                        cursor = cursor + 11;
                        var beginText = text.substring(0, end + 1 + cursor);
                        if (beginText[beginText.length - 1] === '~')
                            beginText = beginText.substring(0, beginText.length - 1) + ' ';
                        text = beginText + '<ins>' + str + '</ins>' + text.substring(end + 1 + cursor, text.length + cursor);
                        cursor = cursor + 11 + str.length;
                    } else if (end !== -1) {
                        text = text.substring(0, begin + cursor) + '<del>' + text.substring(begin + cursor, end + cursor + 1) + '</del>' + text.substring(end + cursor + 1, text.length + cursor);
                        cursor = cursor + 11;
                    } else {
                        var beginText = text.substring(0, begin + cursor);
                        if (beginText[beginText.length - 1] === '~')
                            beginText = beginText.substring(0, beginText.length - 1) + ' ';
                        text = beginText + '<ins>' + str + '</ins> ' + text.substring(begin - 1 + cursor, text.length + cursor);
                        text = text.replace('  ', ' ');
                        cursor = cursor + 12 + str.length;
                    }
                }
                text = text.split("&lt;span style=&quot;line-height: 1.6;&quot;&gt;").join("");
                text = text.split("&lt;/span&gt;").join("");
                text = text.split("&amp;lt;").join("\<");
                text = text.split("&amp;gt;").join("\>");
                text = text.split("&amp;").join("&");
                text = text.split("~").join("<br />");
                return text;
            }
            ;

            /**
             * Sort the list of modifications by the begin position
             * @param {Array} modifications
             * @returns {Array} modifications
             */
            function sortModifications(modifications) {
                var sort = false;
                var size = modifications.length;
                while (!sort) {
                    sort = true;
                    for (var i = 0; i < size - 1; i++) {
                        var x = parseInt(modifications[i].begin_position.value);
                        var y = parseInt(modifications[i + 1].begin_position.value);
                        if (x > y) {
                            var temp = modifications[i];
                            modifications[i] = modifications[i + 1];
                            modifications[i + 1] = temp;
                            sort = false;
                        }
                    }
                    size = size - 1;
                }
                return modifications;
            }
            ;

            /**
             * Function which construct the table of modifications amendment
             * @param {type} edit
             * @returns {Array} edit
             */
            function constructModifications(edit) {
                var originalText = CKEDITOR.instances.originalText.getData();
                originalText = originalText.split("&lsquo;").join("‘");
                originalText = originalText.split("&rsquo;").join("’");
                originalText = originalText.split("&bull;").join("•");
                originalText = originalText.split("&bdquo;").join("„");
                originalText = originalText.split("&ldquo;").join("“");
                var editContent;
                if (edit === true) {
                    editContent = CKEDITOR.instances.editeurEdit.getData();
                } else {
                    editContent = CKEDITOR.instances.editeur.getData();
                }
                editContent = $('<div/>').html(editContent).html();
                editContent = editContent.split("&nbsp;").join(" ");
                editContent = editContent.split("\\").join("\\\\");
                editContent = removeExtraSpace(editContent);
                editContent = editContent.split("<p> ").join("<p>");
                editContent = editContent.split("</p> ").join("</p>");
                editContent = editContent.split(" <p>").join("<p>");
                editContent = editContent.split(" </p>").join("</p>");
                var changes = trackChanges(originalText, editContent);
                changes = changes.split("</ins><ins>").join("");
                changes = changes.split("</del><del>").join("");
                changes = changes.split("<ins> </ins>").join("");
                changes = changes.split("> ").join(">");
                var tabResults = [];
                var cptModif = -1;
                var cursor = 0;
                for (var i = 0; i < changes.length; i++) {
                    var str = changes[i] + changes[i + 1] + changes[i + 2] + changes[i + 3] + changes[i + 4];
                    if (str === '<ins>' || str === '<del>') {
                        for (var j = i + 5; j < changes.length; j++) {
                            var str2 = changes[j] + changes[j + 1] + changes[j + 2] + changes[j + 3] + changes[j + 4] + changes[j + 5];
                            if (str2 === '</ins>' || str2 === '</del>') {
                                cptModif++;
                                var res = '';
                                for (var k = i; k <= j + 5; k++) {
                                    res += changes[k];
                                }
                                var begin = i - (cptModif * 5) - ((cptModif) * 6);
                                res = res.split("<ins>").join("");
                                res = res.split(" </ins>").join("");
                                res = res.split("<del>").join("");
                                res = res.split(" </del>").join("");

                                if (str === '<ins>') {
                                    var result = {
                                        begin: begin - cursor,
                                        str: res
                                    };
                                    cursor += res.length + 1;
                                } else if (str === '<del>') {
                                    var result = {
                                        begin: begin - cursor,
                                        end: begin + res.length - 1 - cursor
                                    };
                                }

                                tabResults.push(result);
                                break;
                            }
                        }
                    }
                }
                return tabResults;
            }
            ;

            /**
             * Function to display message if the amendment is in another language
             */
            $scope.anotherLanguageAmendment = false;
            function seeAmendment() {
                if ($scope.commentSelected) {
                    var langComment = $scope.commentSelected.post.value.substring($scope.commentSelected.post.value.length - 3, $scope.commentSelected.post.value.length);
                    var nbAmendments = parseInt($scope.commentSelected.num_deletion.value) + parseInt($scope.commentSelected.num_insertion.value) + parseInt($scope.commentSelected.num_substitution.value);
                    if (nbAmendments > 0 && $scope.languageDocument !== langComment) {
                        $scope.anotherLanguageAmendment = true;
                        $scope.otherLanguageComment = langComment;
                    } else {
                        $scope.anotherLanguageAmendment = false;
                    }
                }
            }
            ;

            /**
             * Function to load forms to edit a user comment and this ammendements
             * @param {type} section
             * @param {type} comment
             */
            $scope.edit = function (section, comment) {
                for (var i = 0; i < $scope.comments.length; i++) {
                    $scope.comments[i].edit = false;
                }
                comment.edit = true;
                comment.translation = null;
                comment.comment.value = $sce.trustAsHtml(comment.comment.value);
                if (section.content && $scope.languageDocument === comment.lang.value) {
                    Comment.getAmendments(comment).success(function (data) {
                        var allModifications = data.results.bindings;
                        if (allModifications.length === 0) {
                            section.amendmentToEditCK = {
                                content: $scope.section.content,
                                sectionId: $scope.section.id
                            };
                        } else {
                            var content = viewAmendment(allModifications);
                            content = content.replace(/<del>(.*?)<\/del>/ig, "");
                            section.amendmentToEditCK = {
                                content: content,
                                sectionId: $scope.section.id
                            };
                        }
                    });
                }
                if (comment.note.value === 'yes') {
                    $scope.editCssYes = 'value-yes-checked';
                    $scope.editCssNo = '';
                } else if (comment.note.value === 'no') {
                    $scope.editCssYes = '';
                    $scope.editCssNo = 'value-no-checked';
                } else {
                    $scope.editCssYes = '';
                    $scope.editCssNo = '';
                }
                $scope.loadingSaveEdit = false;
            };

            /**
             * Function to save editions user comment and this ammendements
             * @param {type} section
             * @param {type} comment
             */
            $scope.saveEdit = function (section, comment) {
                $scope.loadingSaveEdit = true;
                comment.hashtags = [];
                var modifications = null;
                if (section.content && section.amendmentToEditCK) {
                    modifications = constructModifications(true);
                }
                var strComment = $('<textarea />').html(comment.comment.value.toString()).text();
                strComment = strComment.replace(/(?:\r\n|\r|\n)/g, " ");
                strComment = escape(strComment);
                comment.comment.value = strComment.split("\\").join("\\\\");
                var data = {
                    post: comment.post.value,
                    comment: comment.comment.value,
                    note: comment.note.value,
                    modifications: modifications
                };
                Comment.editComment(data).success(function () {
                    $scope.tempCurrentPage = section.currentPage;
                    loadDataSection(section, section.idFmxElement === 'act' ? false : true);
                    $scope.loadingSaveEdit = false;
                    var dataTranslation = {
                        post: comment.post.value,
                        textToTranslate: comment.comment.value,
                        sourceLanguage: comment.lang.value
                    };
                    Comment.backTranslation(dataTranslation).success(function () {
                    });
                });
            };

            /**
             * Function to remove user comment
             * @param {type} section
             * @param {type} comment
             */
            $scope.deleteComment = function (section, comment) {
                comment.loadingDeleteComment = true;
                var data = {
                    post: comment.post.value
                };
                Comment.deleteComment(data).success(function () {
                    $scope.tempCurrentPage = 0;
                    loadDataSection(section, section.idFmxElement === 'act' ? false : true);
                    comment.loadingDeleteComment = false;
                });
            };

            /**
             * Function to load forms to edit a user reply and this ammendements
             * @param {type} reply
             */
            $scope.editReply = function (reply) {
                reply.edit = true;
                reply.translation = null;
                reply.comment.value = $sce.trustAsHtml(reply.comment.value);
                $scope.loadingSaveEdit = false;
            };

            /**
             * Function to save editions user reply
             * @param {type} comment
             * @param {type} reply
             */
            $scope.saveEditReply = function (comment, reply) {
                $scope.loadingSaveEdit = true;
                var strComment = $('<textarea />').html(reply.comment.value.toString()).text();
                strComment = strComment.replace(/(?:\r\n|\r|\n)/g, " ");
                strComment = escape(strComment);
                reply.comment.value = strComment.split("\\").join("\\\\");
                var data = {
                    post: reply.post.value,
                    comment: reply.comment.value
                };
                Comment.editReply(data).success(function () {
                    $scope.tempCurrentPageReply = comment.currentPage;
                    getRepliedPosts(comment, false);
                    $scope.loadingSaveEdit = false;
                    var dataTranslation = {
                        post: reply.post.value,
                        textToTranslate: reply.comment.value,
                        sourceLanguage: reply.lang.value
                    };
                    Comment.backTranslation(dataTranslation).success(function () {
                    });
                });
            };

            /**
             * Function to remove user reply
             * @param {type} comment
             * @param {type} reply
             */
            $scope.deleteReply = function (comment, reply) {
                reply.loadingDeleteComment = true;
                var data = {
                    post: reply.post.value
                };
                Comment.deleteReply(data).success(function () {
                    $scope.tempCurrentPageReply = 0;
                    getRepliedPosts(comment, false);
                    reply.loadingDeleteComment = false;
                });
            };

            /**
             * Function to check if user connected is the user comment
             * @param {type} comment
             * @param {type} user
             * @returns {Boolean}
             */
            $scope.isUserComment = function (comment, user) {
                var sipltUser = comment.user.value.split("id_user_");
                if (sipltUser[1] === user) {
                    return true;
                } else {
                    return false;
                }
            };

            /**
             * Function to check if user connected is the user reply
             * @param {type} reply
             * @param {type} user
             * @returns {Boolean}
             */
            $scope.isUserReply = function (reply, user) {
                var sipltUser = reply.creator.value.split("id_user_");
                if (sipltUser[1] === user) {
                    return true;
                } else {
                    return false;
                }
            };

            /**
             * Function to change the opinion user
             * @param {type} comment
             * @param {type} type
             */
            $scope.changeNote = function (comment, type) {
                if (type === 'yes') {
                    if (comment.note.value === 'yes') {
                        comment.note.value = 'mixed';
                        $scope.editCssYes = '';
                        $scope.editCssNo = '';
                    } else {
                        comment.note.value = 'yes';
                        $scope.editCssYes = 'value-yes-checked';
                        $scope.editCssNo = '';
                    }
                } else {
                    if (comment.note.value === 'no') {
                        comment.note.value = 'mixed';
                        $scope.editCssYes = '';
                        $scope.editCssNo = '';
                    } else {
                        comment.note.value = 'no';
                        $scope.editCssYes = '';
                        $scope.editCssNo = 'value-no-checked';
                    }
                }
            };

            /**
             * Function to translate a user comment
             * @param {type} comment
             */
            $scope.translate = function (comment) {
                if (comment.translation) {
                    comment.translation = null;
                } else {
                    var data = {
                        text: comment.comment.value
                    };
                    var data = {
                        post: comment.post.value,
                        target_eli_lang_code: $scope.languageDocument
                    };
                    Comment.getTranslation(data).success(function (result) {
                        comment.translation = result;
                    });
                }
            };

            /**
             * Function to translate a user reply
             * @param {type} reply
             */
            $scope.translateReply = function (reply) {
                if (reply.translation) {
                    reply.translation = null;
                } else {
                    var data = {
                        text: reply.comment.value
                    };
                    var data = {
                        post: reply.post.value,
                        target_eli_lang_code: $scope.languageDocument
                    };
                    Comment.getTranslation(data).success(function (result) {
                        reply.translation = result;
                    });
                }
            };

            /**
             * Function to like or dislike a user comment
             * @param {type} section
             * @param {type} comment
             * @param {type} note
             * @param {type} user
             * @param {type} msg
             * @param {type} msg2
             */
            $scope.likeDislikeComment = function (section, comment, note, user, msg, msg2) {
                var data = {
                    uri: section.uri,
                    repliedPost: comment.post.value,
                    note: note
                };
                var splitUser = comment.user.value.split('_');
                if (user === "0") {
                    alert(msg);
                } else if (user === splitUser[splitUser.length - 1]) {
                    alert(msg2);
                } else {
                    Comment.likeDislikeComment(data).success(function () {
                        $scope.tempCurrentPage = section.currentPage;
                        loadDataSection(section, section.idFmxElement === 'act' ? false : true);
                    });
                }
            };

            /**
             * Function to check if like or dislike for the user comment already exist
             * @param {type} comment
             * @param {type} user
             */
            $scope.isLikeDislikeComment = function (comment, user) {
                if (user !== "0") {
                    var data = {
                        repliedPost: comment.post.value
                    };
                    Comment.isLikeDislike(data).success(function (result) {
                        if (result[0] === 'no') {
                            comment.cssLike = "";
                            comment.cssDislike = "userDislike";
                        } else if (result[0] === 'yes') {
                            comment.cssLike = "userLike";
                            comment.cssDislike = "";
                        }
                    });
                }
            };

            /**
             * Function to like or dislike a user reply
             * @param {type} section
             * @param {type} comment
             * @param {type} reply
             * @param {type} note
             * @param {type} user
             * @param {type} msg
             * @param {type} msg2
             */
            $scope.likeDislikeReply = function (section, comment, reply, note, user, msg, msg2) {
                var data = {
                    uri: section.uri,
                    repliedPost: reply.post.value,
                    note: note
                };
                var splitUser = comment.user.value.split('_');
                if (user === "0") {
                    alert(msg);
                } else if (user === splitUser[splitUser.length - 1]) {
                    alert(msg2);
                } else {
                    Comment.likeDislikeComment(data).success(function () {
                        $scope.tempCurrentPageReply = comment.currentPage;
                        getRepliedPosts(comment, false);
                    });
                }
            };

            /**
             * Function to check if like or dislike for the user reply already exist
             * @param {type} reply
             * @param {type} user
             */
            $scope.isLikeDislikeReply = function (reply, user) {
                if (user !== "0") {
                    var data = {
                        repliedPost: reply.post.value
                    };
                    Comment.isLikeDislike(data).success(function (result) {
                        if (result[0] === 'no') {
                            reply.cssLike = "";
                            reply.cssDislike = "userDislike";
                        } else if (result[0] === 'yes') {
                            reply.cssLike = "userLike";
                            reply.cssDislike = "";
                        }
                    });
                }
            };

            /**
             * Check if an user is connected
             * @param {type} user
             * 
             * @returns {boolean} connected
             */
            $scope.isConnected = function (user) {
                if (user === "0") {
                    return false;
                } else {
                    return true;
                }
            };

            /**
             * Count nb amendements for all the document
             */
            function countAmendements() {
                $scope.nbTotalAmmendements = 0;
                for (var i = 0; i < $scope.document.sections.length; i++) {
                    $scope.nbTotalAmmendements += $scope.document.sections[i].nbAmmendements;
                }
            }
            ;

            /**
             * Change the section selected if he exit the window
             */
            function checkPositionSection() {
                var section = document.getElementById($scope.section.id);

                if (section.getBoundingClientRect().bottom < 0) {
                    for (var i = 0; i < $scope.listSections.length; i++) {
                        if ($scope.section.id === $scope.listSections[i].id) {
                            if (i < $scope.listSections.length - 1) {
                                $scope.section = $scope.listSections[i + 1];
                                $scope.loadSection($scope.section, true);
                                break;
                            }
                        }
                    }
                }
                if (section.getBoundingClientRect().top > $(window).height()) {
                    for (var i = 0; i < $scope.listSections.length; i++) {
                        if ($scope.section.id === $scope.listSections[i].id) {
                            if (i > 0) {
                                $scope.section = $scope.listSections[i - 1];
                                if (i === 1) {
                                    $scope.loadSection($scope.section, false);
                                } else {
                                    $scope.loadSection($scope.section, true);
                                }
                                break;
                            }
                        }
                    }
                }
            }
            ;

            /**
             * Filtering comments
             * @param {type} section
             */
            $scope.filterComments = function (section) {
                $scope.currentFiltering = true;
                if (typeof section.filterSearch === 'undefined') {
                    section.filterSearch = '';
                }
                if (typeof section.filterAuthor === 'undefined') {
                    section.filterAuthor = '';
                }
                if (typeof section.filterHashtags === 'undefined') {
                    section.filterHashtags = '';
                }
                var dateFrom = '00000000000000';
                if (typeof section.filterDateFrom !== 'undefined' && section.filterDateFrom !== '') {
                    dateFrom = getTimeStampFromDateTime(section.filterDateFrom, '000000');
                }
                var dateTo = '99999999999999';
                if (typeof section.filterDateTo !== 'undefined' && section.filterDateTo !== '') {
                    dateTo = getTimeStampFromDateTime(section.filterDateTo, '999999');
                }
                var userUri = Config.siteName + '/user/id_user_' + $scope.userId;
                Comment.filterComments('/eli/' + $scope.document.docCode + '/' + section.uri, section.onlyMyComments, userUri, section.filterSearch, dateFrom, dateTo, section.filterAuthor, section.filterSort, section.filterPositive, section.filterNeutral, section.filterNegative, section.filterAmendments, section.filterLanguage, section.filterHashtags)
                        .success(function (data) {
                            formatComments(section, data.results.bindings);
                            $scope.currentFiltering = false;
                        });
            };

            /**
             * Clear the filtering comments
             * @param {type} section
             */
            $scope.clearFilter = function (section) {
                section.filteringComments = false;
                section.filterSearch = '';
                section.filterDateFrom = '';
                section.filterDateTo = '';
                section.filterAuthor = '';
                section.filterSort = 'date';
                section.filterPositive = true;
                section.filterNeutral = true;
                section.filterNegative = true;
                section.filterAmendments = false;
                section.onlyMyComments = false;
                section.filterLanguage = 'all';
                section.filterHashtags = '';
                Comment.getComments('/eli/' + $scope.document.docCode + '/' + section.uri)
                        .success(function (data) {
                            formatComments(section, data.results.bindings);
                        });
            };

            // JQuery to create the propositions list of hashtags
            var count, countFilter, countEdit, countAuthor = -1;
            $(window).keydown(function (e) {
                if (e.which === 40) {
                    e.preventDefault();
                    if ($scope.hashtags.length > count + 1) {
                        count = count + 1;
                        $(".hashtag-option")[count].focus();
                    }
                    if ($scope.hashtags.length > countFilter + 1) {
                        countFilter = countFilter + 1;
                        $(".hashtag-option-filter")[countFilter].focus();
                    }
                    countEdit = countEdit + 1;
                    if ($(".hashtag-option-edit")[countEdit]) {
                        $(".hashtag-option-edit")[countEdit].focus();
                    }
                    if ($scope.authors.length > countAuthor + 1) {
                        countAuthor = countAuthor + 1;
                        $(".authors-option")[countAuthor].focus();
                    }
                }
                if (e.which === 38) {
                    e.preventDefault();
                    if (count > 0) {
                        count = count - 1;
                        $(".hashtag-option")[count].focus();
                    }
                    if (countFilter > 0) {
                        countFilter = countFilter - 1;
                        $(".hashtag-option-filter")[countFilter].focus();
                    }
                    if (countEdit > 0) {
                        countEdit = countEdit - 1;
                        $(".hashtag-option-edit")[countEdit].focus();
                    }
                    if (countAuthor > 0) {
                        countAuthor = countAuthor - 1;
                        $(".authors-option")[countAuthor].focus();
                    }
                }
            });

            $(window).click(function () {
                $('#hashtags').hide();
                $('#hashtags-filter').hide();
                $('#authors-filter').hide();
                $('#new-hashtags').hide();
                $('#edit-hashtags').hide();
            });

            /* 
             * Hashtags suggestion 
             * @param {String} section
             */
            $scope.hashtags = [];
            $scope.getHashtags = function (section, event) {
                if (event.keyCode !== 38 && event.keyCode !== 40) {
                    if (section.filterHashtags.length > 0) {
                        var listHashtags = section.filterHashtags.split(';');
                        //var data = {pattern: listHashtags[listHashtags.length - 1], lang: Config.lang};
                        if (removeExtraSpace(listHashtags[listHashtags.length - 1]).length > 0) {
                            $('#hashtags-filter').show();
                            Config.getHashtags(removeExtraSpace(listHashtags[listHashtags.length - 1])).then(function (results) {
                                $scope.hashtags = results.data;
                            });
                        }
                    } else {
                        $scope.hashtags = [];
                    }
                    countFilter = -1;
                }
            };

            /**
             * Function to add a new hashtag to the hashtags list 
             * @param {String} section
             * @param {String} hashtag
             */
            $scope.addHashtag = function (section, hashtag) {
                var hashtags = section.filterHashtags.split(';');
                section.filterHashtags = '';
                for (var i = 0; i < hashtags.length - 1; i++) {
                    section.filterHashtags += hashtags[i] + '; ';
                }
                // add this hashtag to the list
                if (section.filterHashtags.indexOf(hashtag) === -1) {
                    section.filterHashtags += hashtag + ';';
                }
                section.filterHashtags = removeExtraSpace(section.filterHashtags);
                section.filterHashtags += ' ';
                $(".hashtag-input").value += ';';
                $(".hashtag-input").focus();
                $scope.hashtags = [];
            };
            
            /* 
             * Authors suggestion 
             * @param {String} section
             */
            $scope.authors = [];
            $scope.getAuthors = function (section, event) {
                if (event.keyCode !== 13 && event.keyCode !== 38 && event.keyCode !== 40) {
                    if (section.filterAuthor.length > 0) {
                        $('#authors-filter').show();
                        Config.getAuthors(section.filterAuthor).then(function (results) {
                            $scope.authors = results.data.results.bindings;
                        });
                    } else {
                        $scope.authors = [];
                    }
                    countAuthor = -1;
                }
            };

            /**
             * Function to add a new author to the authors list 
             * @param {String} section
             * @param {String} author
             */
            $scope.addAuthor = function (section, author) {
                section.filterAuthor = author;
                $(".authors-input").focus();
                $scope.authors = [];
                $('#authors-filter').hide();
            };

            /**
             * Function to add a new hashtag and remove others
             * @param {String} hashtag
             */
            $scope.newHashtag = function (hashtag) {
                $scope.section.filterHashtags = removeExtraSpace(hashtag);
                $(".hashtag-input").value += ';';
                $(".hashtag-input").focus();
                $scope.hashtags = [];
                $scope.filterComments($scope.section);
            };

            /**
             * Function to suggested hashtags when the user writes # in a comment
             */
            $scope.checkShowHashtags = function () {
                var splitComment = $scope.commentData.commentTextBox.split(" ");
                if (splitComment[splitComment.length - 1].substring(0, 1) === '#') {
                    Config.getHashtags(splitComment[splitComment.length - 1]).then(function (results) {
                        $('#new-hashtags').show();
                        $scope.hashtags = results.data;
                    });
                } else {
                    $scope.hashtags = [];
                }
                count = -1;
            };

            /**
             * Function to suggested hashtags when the user writes # in a comment edition
             */
            $scope.checkShowEditHashtags = function (comment) {
                var splitComment = comment.comment.value.split(" ");
                if (splitComment[splitComment.length - 1].substring(0, 1) === '#') {
                    Config.getHashtags(splitComment[splitComment.length - 1]).then(function (results) {
                        $('#edit-hashtags').show();
                        comment.hashtags = results.data;
                    });
                } else {
                    comment.hashtags = [];
                }
                countEdit = -1;
            };

            /**
             * Function to add a new hashtag to the new hashtags list 
             * @param {String} hashtag
             */
            $scope.addNewHashtag = function (hashtag) {
                var splitComment = $scope.commentData.commentTextBox.split(" ");
                var lengthLastWord = splitComment[splitComment.length - 1].length;
                $scope.commentData.commentTextBox = $scope.commentData.commentTextBox.substring(0, $scope.commentData.commentTextBox.length - lengthLastWord);
                $scope.commentData.commentTextBox += hashtag + ' ';
                $(".comment-txt").focus();
                $scope.hashtags = [];
            };

            /**
             * Function to add a new hashtag to the new hashtags edition list 
             * @param {String} comment
             * @param {String} hashtag
             */
            $scope.addEditHashtag = function (comment, hashtag) {
                var splitComment = comment.comment.value.split(" ");
                var lengthLastWord = splitComment[splitComment.length - 1].length;
                comment.comment.value = comment.comment.value.substring(0, comment.comment.value.length - lengthLastWord);
                comment.comment.value += hashtag + ' ';
                $(".edit-comment").focus();
                comment.hashtags = [];
            }
            ;
        });
