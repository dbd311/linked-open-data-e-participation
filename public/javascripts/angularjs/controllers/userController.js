angular.module('userCtrl', [])

.controller('userCtrl', function (User, Config, $scope) {
    
    $scope.loading = false;
    $scope.message = {};
    
    $scope.selectUser = function(id) {
        $scope.loadGroups();
        $scope.loadNationalities();
        User.nbCommentsUser(id).success(function(data) {
            $scope.nbComments = data.results.bindings[0].total.value;
        });
        User.selectUser(id).success(function(data) {
            $scope.user = data.results.bindings[0];
            if ($scope.user.role.value.indexOf("admin") !== -1) {
                $scope.role = 'admin';
            } else {
                $scope.role = 'citizen';
            }
            $scope.user.user_group.value = $scope.user.user_group.value.replace(Config.siteName + "/user_group/","");
            $scope.user.nationality.value = $scope.user.nationality.value.replace(Config.siteName + "/nationality/","");
            $scope.loading = true;
        });
    };
    
    $scope.same = function(idConnected,idProfile){
        if (idConnected === idProfile){
            return true;
        } else {
            return false;
        }
    };
    
    $('.editionFirstName').hide();
    $('.buttonFirstName').hide();
    $scope.editFirstName = function(){
        $('.firstName').hide();
        $('.buttonFirstName').show();
        $('.editionFirstName').show();
        $('.editionFirstName')[0].focus();
        $scope.message = {};
    };
    
    $scope.saveFirstName = function(event){
        if (event.type === 'click' || (event.type === 'keyup' && event.keyCode === 13)) {
            if($scope.user.user_name.value !== ''){
                User.updateUser($scope.user).success(function() {
                    $('.editionFirstName').hide();
                    $('.buttonFirstName').hide();
                    $('.firstName').show();
                    $scope.message.updated = true;
                    $scope.cssBox = 'alert-success';
                });
            }
        }
    };
    
    $('.editionLastName').hide();
    $('.buttonLastName').hide();
    $scope.editLastName = function(){
        $('.lastName').hide();
        $('.buttonLastName').show();
        $('.editionLastName').show();
        $('.editionLastName')[0].focus();
        $scope.message = {};
    };
    
    $scope.saveLastName = function(event){
        if (event.type === 'click' || (event.type === 'keyup' && event.keyCode === 13)) {
            if($scope.user.family_name.value !== ''){
                User.updateUser($scope.user).success(function() {
                    $('.editionLastName').hide();
                    $('.buttonLastName').hide();
                    $('.lastName').show();
                    $scope.message.updated = true;
                    $scope.cssBox = 'alert-success';
                });
            }
        }
    };
    
    $(".editionGroup").keydown(function (e) {
        if (e.which === 40) {
            e.preventDefault();
            if ($scope.listGroups.length > count + 1) {
                count = count + 1;
                $(".group-option")[count].focus();
            }
        }
        if (e.which === 38) {
            e.preventDefault();
            if (count > 0) {
                count = count - 1;
                $(".group-option")[count].focus();
            }
        }
    });

    $(window).click(function() {
        $('#suggestions').hide();
    });
    
    var allGroups = [];
    $scope.loadGroups = function(){
        Config.loadGroups().success(function(data) {
            allGroups = data;
        });
    };
    
    $scope.listGroups = [];
    function getSuggestions() {
        if ($scope.user.user_group.value !== '') {
            $('#suggestions').show();
            $scope.listGroups = [];
            var cpt = 0;
            for(var i = 0; i < allGroups.length; i++){
                if (allGroups[i].name.toUpperCase().indexOf($scope.user.user_group.value.toUpperCase()) === 0 && cpt < 6) {
                    cpt++;
                    $scope.listGroups.push(allGroups[i].name);
                }
            }
            count = - 1;
        }
    };
    
    $scope.addGroup = function (group) {
        $scope.user.user_group.value = group;
        $(".group-input").focus();
        $scope.listGroups = [];
        $('#suggestions').hide();
        $scope.saveGroup('addGroup');
    };
    
    $('.editionGroup').hide();
    $('.buttonGroup').hide();
    $scope.editGroup = function(){
        $('.group').hide();
        $('.buttonGroup').show();
        $('.editionGroup').show();
        $('.editionGroup')[0].focus();
        $scope.message = {};
    };
    
    $scope.saveGroup = function(event){
        if (event === 'addGroup' || event.type === 'click' || (event.type === 'keyup' && event.keyCode === 13)) {
            User.updateUser($scope.user).success(function() {
                $('.editionGroup').hide();
                $('.buttonGroup').hide();
                $('.group').show();
                $scope.listGroups = [];
                $scope.message.updated = true;
                $scope.cssBox = 'alert-success';
            });
        } else {
            getSuggestions();
        }
    };
    
    $scope.loadNationalities = function(){
        Config.loadNationalities().success(function(data) {
            $scope.listNationalities = data;
        });
    };
    
    $('.editionNationality').hide();
    $scope.editNationality = function(){
        $('.nationality').hide();
        $('.editionNationality').show();
        $('.editionNationality')[0].focus();
        $scope.message = {};
    };
    
    $scope.saveNationality = function(){
        User.updateUser($scope.user).success(function() {
            $('.editionNationality').hide();
            $('.nationality').show();
            $scope.message.updated = true;
            $scope.cssBox = 'alert-success';
        });
    };
    
    $scope.changePassword = function(event){
        if (event.type === 'click' || (event.type === 'keyup' && event.keyCode === 13)) {
            User.changePassword($scope.user).success(function(result) {
                $scope.message = {};
                if (result === "0"){
                    $scope.message.wrongPassword = true;
                    $scope.cssBox = 'alert-danger';
                } else if (result === "1") {
                    $scope.message.badPassword = true;
                    $scope.cssBox = 'alert-danger';
                } else if (result === "2") {
                    $scope.message.shortPassword = true;
                    $scope.cssBox = 'alert-danger';
                } else {
                    $scope.message.updatePassword = true;
                    $scope.cssBox = 'alert-success';
                }
                $scope.user.passwordCurrent = '';
                $scope.user.passwordNew = '';
                $scope.user.passwordConfirm = '';
            });
        }
    };
    
    $scope.cleanBox = function(){
        $scope.message = {};
    };
});