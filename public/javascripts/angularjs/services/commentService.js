angular.module('commentService', [])

.factory('Comment', function ($http, Config) {

    return {
        save: function (commentData) {
            return $http({
                method: 'POST',
                url: '/add-user-comment',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(commentData)
            });
        },
        saveReply: function (reply) {
            return $http({
                method: 'POST',
                url: '/reply-to-previous-comment',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(reply)
            });
        },
        getComments: function (container) {
            if (container.indexOf(Config.siteName) !== 0) {
                container = Config.siteName + container;
            }             
            var genericContainerURI = container.substring(0, container.length - 4);
            var filter = Config.createLanguageFilterContainer(genericContainerURI, Config.listLanguages);
                var query = 'SELECT DISTINCT ?post ?user ?user_id ?created_at ?amended_at ?comment ?note ?firstName ?lastName ?avatar LANG(?comment) as ?lang COUNT(distinct ?reply_post) as ?totalReplies ?num_like ?num_dislike COUNT(distinct ?insertion) as ?num_insertion COUNT(distinct ?deletion) as ?num_deletion COUNT(distinct ?substitution) as ?num_substitution ';
                query += 'WHERE { ?post sioc:has_container ?container. ' + filter;
                query += ' OPTIONAL{?post sioc:has_creator ?user.} OPTIONAL{?post sioc:created_at  ?created_at.} OPTIONAL{?post sioc:content ?comment.} OPTIONAL{?post sioc:note ?note.} ';
		query += 'OPTIONAL{?post sioc:has_reply ?reply_post.} OPTIONAL{?user sioc:id ?user_id; foaf:name ?firstName; foaf:familyName ?lastName; sioc:avatar ?avatar.}  ';
                query += 'OPTIONAL{?insertion  lodep:contained ?post. ';
                query += '?insertion a prv:Insertion} ';
                query += 'OPTIONAL{?deletion  lodep:contained ?post. ';
                query += '?deletion  a prv:Deletion} ';
                query += 'OPTIONAL{?substitution lodep:contained ?post. ';
                query += '?substitution a prv:Substitution} ';
                query += 'OPTIONAL{?post lodep:amended_at ?amended_at.} ';
                query += 'OPTIONAL{?post lodep:num_like ?num_like; lodep:num_dislike ?num_dislike.}} ORDER BY ASC(?created_at)';
            var queryUrl = Config.sparqlEndPoint + "?query=" + encodeURIComponent(query) + "&format=json&callback=JSON_CALLBACK";
            return $http.jsonp(queryUrl);
        },
        getNbComments: function (container) {
            if (container.indexOf(Config.siteName) !== 0) {
                container = Config.siteName + container;
            }             
            var genericContainerURI = container.substring(0, container.length - 4);
            var filter = Config.createLanguageFilterContainer(genericContainerURI, Config.listLanguages);
            var query = 'SELECT DISTINCT COUNT(*) as ?nbComments WHERE { ?post sioc:has_container ?container. ' + filter + '}';
            var queryUrl = Config.sparqlEndPoint + "?query=" + encodeURIComponent(query) + "&format=json&callback=JSON_CALLBACK";
            return $http.jsonp(queryUrl);
        },
        filterComments: function (container, onlyMyComments, userUri, textSearch, dateFrom, dateTo, author, sort, positive, neutral, negative, amendments, language, hashtags) {
            if (container.indexOf(Config.siteName) !== 0) {
                container = Config.siteName + container;
            }
            var tabAuthor = author.trim().split(' ');
            var genericContainerURI = container.substring(0, container.length - 4);
            var filter = Config.createLanguageFilterContainer(genericContainerURI, Config.listLanguages, onlyMyComments, userUri);
                var query = 'SELECT DISTINCT ?post ?user ?user_id ?created_at ?amended_at ?comment ?note ?firstName ?lastName ?avatar  LANG(?comment) as ?lang COUNT(distinct ?reply_post) as ?totalReplies ?num_like ?num_dislike COUNT(distinct ?insertion) as ?num_insertion COUNT(distinct ?deletion) as ?num_deletion COUNT(distinct ?substitution) as ?num_substitution  (COUNT(distinct ?reply_post) + ?num_like + ?num_dislike) as ?popular (?num_like - ?num_dislike) as ?valued ';
                query += 'WHERE { ?post sioc:has_container ?container. ' + filter;
                query += 'OPTIONAL{?post sioc:has_creator ?user.} OPTIONAL{?post sioc:created_at ?created_at.} ';
                query += 'FILTER(xsd:integer(?created_at) >= \'' + dateFrom + '\'^^xsd:integer AND ';
                query += 'xsd:integer(?created_at) <= \'' + dateTo + '\'^^xsd:integer) OPTIONAL{?post sioc:content ?comment.} ';
                query += 'FILTER(REGEX(lcase(?comment),  lcase(\'' + textSearch + '\')))';
                if(language !== 'all') {
                    query += ' FILTER(REGEX(lcase(LANG(?comment)),  lcase(\'' + language + '\')))';
                }
                if(hashtags !== '') {
                    query += ' FILTER(';
                    hashtags = hashtags.replace(" ","");
                    var splitHashtags = hashtags.split(';').filter(Boolean);
                    for(var i = 0; i < splitHashtags.length; i++){
                        if(i !== 0){
                            query += ' OR ';
                        }
                        var hashtag = splitHashtags[i].trim();
                        if (hashtag.substring(0,1) !== '#'){
                            hashtag = '#' + hashtag;
                        }
                        query += 'REGEX(lcase(?comment), lcase(\'' + hashtag + '(\u0020|$)\'))';
                    }
                    query += ')';
                }
                query += ' ?post sioc:note ?note. ';
                if(positive && neutral && !negative){
                    query += 'FILTER(REGEX(?note,  \'yes\') OR REGEX(?note,  \'mixed\')) ';
                }
                if(positive && !neutral && negative){
                    query += 'FILTER(REGEX(?note,  \'yes\') OR REGEX(?note,  \'no\')) ';
                }
                if(!positive && neutral && negative){
                    query += 'FILTER(REGEX(?note,  \'mixed\') OR REGEX(?note,  \'no\')) ';
                }
                if(positive && !neutral && !negative){
                    query += 'FILTER(REGEX(?note,  \'yes\')) ';
                }
                if(!positive && neutral && !negative){
                    query += 'FILTER(REGEX(?note,  \'mixed\')) ';
                }
                if(!positive && !neutral && negative){
                    query += 'FILTER(REGEX(?note,  \'no\')) ';
                }
                if(!positive && !neutral && !negative){
                    query += 'FILTER(REGEX(?note,  \'all\')) ';
                }
		query += 'OPTIONAL{?post sioc:has_reply ?reply_post.} OPTIONAL{?user sioc:id ?user_id; foaf:name ?firstName; foaf:familyName ?lastName; sioc:avatar ?avatar.} ';
                for(var i = 0; i < tabAuthor.length; i++){
                    query += 'FILTER(REGEX(lcase(?firstName),  lcase(\'' + tabAuthor[i] + '\')) OR REGEX(lcase(?lastName),  lcase(\'' + tabAuthor[i] + '\'))) ';    
                }
                query += 'OPTIONAL{?insertion  lodep:contained ?post. ';
                query += '?insertion a prv:Insertion} ';
                query += 'OPTIONAL{?deletion  lodep:contained ?post. ';
                query += '?deletion  a prv:Deletion} ';
                query += 'OPTIONAL{?substitution lodep:contained ?post. ';
                query += '?substitution a prv:Substitution} ';
                query += 'OPTIONAL{?post lodep:amended_at ?amended_at.} ';
                query += 'OPTIONAL{?post lodep:num_like ?num_like; lodep:num_dislike ?num_dislike.}';
                if (amendments){
                    query += '. ?post ^lodep:contained ?amendment';
                }
                query += '.} ORDER BY ';
                if(sort === 'popular') {
                    query += 'ASC(?popular)';
                } else if (sort === 'good') {
                    query += 'ASC(?valued)';
                } else if (sort === 'bad') {
                    query += 'DESC(?valued)';
                } else {
                    query += 'ASC(?created_at)';
                }
            var queryUrl = Config.sparqlEndPoint + "?query=" + encodeURIComponent(query) + "&format=json&callback=JSON_CALLBACK";
            return $http.jsonp(queryUrl);
        },
        getReplies: function (comment) {
            var query = "SELECT ?post ?user_id ?firstName ?lastName ?creator ?avatar ?comment ?created_at ?amended_at ?num_like ?num_dislike LANG(?comment) as ?lang \
                        WHERE { \
                        <" + comment.post.value + "> sioc:has_reply ?post.\
                         ?post sioc:created_at  ?created_at;\
                         sioc:content ?comment; \
                         sioc:has_creator ?creator.\
                        ?creator sioc:avatar ?avatar;\
                         sioc:id ?user_id;\
                         foaf:name ?firstName;\
                         foaf:familyName ?lastName. OPTIONAL{?post lodep:num_like ?num_like; lodep:num_dislike ?num_dislike. OPTIONAL{?post lodep:amended_at ?amended_at.}}\
                } ORDER BY ASC(?created_at)";
            var queryUrl = Config.sparqlEndPoint + "?query=" + encodeURIComponent(query) + "&format=json&callback=JSON_CALLBACK";
            return $http.jsonp(queryUrl);
        },
        getAmendments: function(comment) {
            var query = "SELECT ?ammend ?user ?post ?timestamp ?container ?begin_position ?end_position ?deleted_content ?new_content ?post \
                         WHERE{ \
                                ?ammend lodep:contained <" + comment.post.value + ">. \
				OPTIONAL {?ammend prv:has_creator ?user . }\
				OPTIONAL {?ammend prv:created_at ?timestamp . } \
				OPTIONAL {?ammend prv:begin_position ?begin_position .} \
				OPTIONAL {?ammend lodep:modifies ?container.}\
				OPTIONAL {?ammend prv:new_content ?new_content .} \
				OPTIONAL {?ammend prv:deleted_content ?deleted_content.} \
				OPTIONAL {?ammend prv:end_position ?end_position.}\
				OPTIONAL {?ammend lodep:contained ?post.}\
                               }";
            var queryUrl = Config.sparqlEndPoint + "?query=" + encodeURIComponent(query) + "&format=json&callback=JSON_CALLBACK";
            return $http.jsonp(queryUrl);
        },
        editComment: function(data) {
            return $http({
                method: 'POST',
                url: '/comment-edit',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(data)
            });
        },
        deleteComment: function(data) {
            return $http({
                method: 'POST',
                url: '/comment-delete',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(data)
            });
        },
        likeDislikeComment: function(data) {
            return $http({
                method: 'POST',
                url: '/comment-like-dislike',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(data)
            });
        },
        isLikeDislike: function(data) {
            return $http({
                method: 'POST',
                url: '/is-like-dislike',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(data)
            });
        },
        editReply: function(data) {
            return $http({
                method: 'POST',
                url: '/reply-edit',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(data)
            });
        },
        deleteReply: function(data) {
            return $http({
                method: 'POST',
                url: '/reply-delete',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(data)
            });
        },
        getLanguageComment: function (data) {
            return $http({
                method: 'POST',
                url: 'detect-language',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(data)
            });
        },
        getTranslation: function (data) {
            return $http({
                method: 'POST',
                url: 'translated-text',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(data)
            });
        },
        backTranslation: function (data) {
            return $http({
                method: 'POST',
                url: '/back-translation',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(data)
            });
        }
    };
});