angular.module('registerCtrl', [])

.controller('registerCtrl', function ($scope, Config) {
    
    var allGroups = [];
    
    $(".keywords-search").keydown(function (e) {
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
        
    $scope.loadNationalities = function(){
        Config.loadNationalities().success(function(data) {
            $scope.listNationalities = data;
            $scope.selectNationality = $scope.listNationalities[0].name;
        });
    };
    
    $scope.loadGroups = function(){
        Config.loadGroups().success(function(data) {
            allGroups = data;
        });
    };
    
    $scope.listGroups = [];
    $scope.userGroup = '';
    $scope.getSuggestions = function (event) {
        if (event.keyCode !== 13 && $scope.userGroup !== '') {
            $('#suggestions').show();
            $scope.listGroups = [];
            var cpt = 0;
            for(var i = 0; i < allGroups.length; i++){
                if (allGroups[i].name.toUpperCase().indexOf($scope.userGroup.toUpperCase()) === 0 && cpt < 6) {
                    cpt++;
                    $scope.listGroups.push(allGroups[i].name);
                }
            }
            count = - 1;
        }
    };
    
    $scope.addGroup = function (group) {
        $scope.userGroup = group;
        $(".group-input").focus();
        $scope.listGroups = [];
        $('#suggestions').hide();
    };

});