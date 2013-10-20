function PageController($scope) {


    /**
     * Adds a new page
     */
    $scope.createPage = function() {
        $scope.$parent.pages.push({records: [], progress: 0});
        $scope.$parent.activePage = $scope.$parent.pages.length - 1;
    }

    /**
     * Sets an active page
     * @param int page index
     */
    $scope.setActivePage = function(page) {
        $scope.$parent.activePage = page;
    }

    /**
     * Removes a page
     * @param int page index
     */
    $scope.closePage = function(page) {
        $scope.setActivePage(0);
        $scope.$parent.pages.splice(page, 1);
        setTimeout(function() {
            $scope.$apply();
        });
    }
}