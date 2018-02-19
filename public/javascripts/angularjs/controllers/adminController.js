angular.module('adminCtrl', [])

.controller('adminCtrl', function (Admin, $scope) {
    
    $scope.loadAdmin = function(){
        existGraph();
        showLinkUpdateTranslation();
    };

    function existGraph() {
        Admin.existGraph('').success(function(exist) {            
            $scope.showDeleteDoc = exist;            
        });
        Admin.existGraph('eurovoc').success(function(exist) {
            $scope.showLoadEurovoc = !exist;
        });
        Admin.existGraph('lang').success(function(exist) {
            $scope.showLoadLang = !exist;
        });
    };

    function showLinkUpdateTranslation() {
        Admin.showLinkUpdateTranslation().success(function(data) {
            $scope.showUpdateTranslation = data;
        });
    };
    
});