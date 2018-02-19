angular.module('userService', [])

.factory('User', function (Config, $http) {

    return {
        selectUser: function (id) {
            var query = "SELECT ?user_id ?avatar ?role ?user_group ?user_name ?family_name ?nationality ?mail ";
                query += "WHERE{ ?user a sioc:UserAccount; ";
                query += "foaf:mbox ?mail; ";
                query += "sioc:id ?user_id; ";
                query += "sioc:name ?label; "; 
                query += "sioc:avatar ?avatar; ";
                query += "sioc:has_function ?role; ";
                query += "sioc:member_of ?user_group; ";				
                query += "foaf:name ?user_name; ";
                query += "foaf:familyName ?family_name; ";
                query += "lodep:nationality ?nationality; ";
                query += "sioc:id '" + id + "' }";
            var queryUrl = Config.sparqlEndPoint + "?query=" + encodeURIComponent(query) + "&format=json&callback=JSON_CALLBACK";
            return $http.jsonp(queryUrl);
        },
        nbCommentsUser: function (id) {
            var query = "SELECT COUNT(*) as ?total WHERE { ?post sioc:has_container ?container. ";
            query += "FILTER (?user=<" + Config.siteName + "/user/id_user_" + id + ">) ?post sioc:has_creator ?user.}";
            var queryUrl = Config.sparqlEndPoint + "?query=" + encodeURIComponent(query) + "&format=json&callback=JSON_CALLBACK";
            return $http.jsonp(queryUrl);
        },
        updateUser: function (user) {
            var data = {
                id : user.user_id.value,
                firstName : user.user_name.value,
                lastName : user.family_name.value,
                group : user.user_group.value,
                nationality : user.nationality.value
            };
            return $http({
                method: 'POST',
                url: '/update-user',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(data)
            });
        },
        changePassword: function (user) {
            var data = {
                id : user.user_id.value,
                currentPassword : user.passwordCurrent,
                newPassword : user.passwordNew,
                confirmPassword : user.passwordConfirm
            };
            return $http({
                method: 'POST',
                url: '/change-password',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(data)
            });
        }
    };
});