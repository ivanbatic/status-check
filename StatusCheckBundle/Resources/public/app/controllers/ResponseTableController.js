function ResponseTableController($scope) {

    // Array of states that are considered final
    var terminalStates = [
        'broken_pipe',
        'connection_failed',
        'done',
        'invalid_host',
        'stream_error',
        'success',
        'failed'
    ];

    /**
     * Maps states to bootstrap classes
     * @param string request status
     * @returns string
     */
    $scope.tableClass = function(status) {
        switch (status) {
            case 'success':
            case 'done':
                return 'success';
            case 'broken_pipe':
            case 'connection_failed':
            case 'invalid_host':
            case 'failed':
            case 'stream_error':
                return 'danger';
            case 'socket_open':
            case 'started':
            case 'request_sent':
            case 'in_progress':
                return 'warning';
            default:
                return '';
        }
    }

    /**
     * Returns a text that should be displayed in progress bar
     * @returns string text
     */
    $scope.getProgressText = function() {
        var stats = getProgressStats();
        return stats.total > 0 ? stats.finished + '/' + stats.total : '';
    }

    /**
     * Returns a class for a progress bar
     * @returns string class
     */
    $scope.getProgressClass = function() {
        var page = $scope.$parent.activePage;
        var pc = parseInt($scope.getProgressPercentage().width);
        var cls = pc < 100 ? 'progress-striped active' : '';
        return cls;
    }

    /**
     * Returns a class object with progress bar percentage
     * @returns style object
     */
    $scope.getProgressPercentage = function() {
        var stats = getProgressStats();
        var def = {width: '0%'};

        if ($scope.$parent.pages.length === 0) return def;

        var pc = (stats.finished / stats.total) * 100;
        return {width: pc + '%'};
    }

    /**
     * Retrieves stats about an active page 
     * @returns object {total, finished}
     */
    function getProgressStats() {
        var page = $scope.$parent.activePage;
        var records = $scope.$parent.pages[page].records;
        var total = records.length;
        var finished = 0;
        for (var i = 0; i < total; i++) {
            if (terminalStates.indexOf(records[i].status) > -1) finished++;
        }
        return {
            total: total,
            finished: finished
        };
    }
}