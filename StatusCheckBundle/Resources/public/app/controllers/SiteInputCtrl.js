function SiteInputCtrl($scope, streamingXhr) {
    $scope.input = '';
    $scope.hostForm = null;
    $scope.inputStatus = '';
    $scope.entries = 0;

    $scope.activePage = 0;
    $scope.pages = [{
            records: [],
            progress: 0
        }];
    $scope.inputs = [''];


    $scope.requests = {};

    // Array of states that are considered final
    var terminalStates = [
        'success', 'done', 'broken_pipe', 'connection_failed', 'invalid_host', 'stream_error'
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

    $scope.submitForm = function() {
        var formData = parseInput($scope.input);
        var activePage = $scope.activePage;

        if (validateInput(formData) !== true) {
            return;
        }

        $scope.requests = [];
        var stream = streamingXhr.create('check-status', 'POST');
        stream.setData({hosts: formData});

        if ($scope.pages.length == 0) {
            $scope.pages.push({records: [], progress: 0});
        }
        $scope.pages[activePage] = {records: [], progress: 0};

        stream.registerCallback(3, function(response) {
            try {
                var split = response.split('\n');
                for (var i = 1; i < split.length; i++) {
                    var r = JSON.parse(split[i]);
                    // Add to table
                    insertRecord(r, activePage);
                    $scope.$apply();
                }
            } catch (e) {
            }
        });
        stream.fire();

    }

    /**
     * Adds a record and updates the table
     * @param object newRecord
     * @param int page index
     */
    function insertRecord(newRecord, page) {
        var updated = false;
        for (var i = 0; i < $scope.pages[page].records.length; i++) {
            if ($scope.pages[page].records[i].id == newRecord.id) {
                $scope.pages[page].records[i] = newRecord;
                updated = true;
                break;
            }
        }

        if (!updated) {
            if (newRecord.parent_id) {
                for (var i = 0; i < $scope.pages[page].records.length; i++) {
                    if ($scope.pages[page].records[i].id == newRecord.parent_id) {
                        $scope.pages[page].records.splice(i + 1, 0, newRecord);
                        break;
                    }
                }
            } else {
                $scope.pages[page].records.push(newRecord);
            }
        }
    }

    /**
     * Filters the input content to remove non-urls
     */
    $scope.filterForm = function() {
        var regexp = /\b(?:(?:https?|ftp|file):\/\/|www\.|ftp\.)[-A-Z0-9+&@#/%=~_|$?!:,.]*[A-Z0-9+&@#/%=~_|$]/gi;
        var parsed = $scope.input ? $scope.input.match(regexp) || [] : [];
        $scope.input = parsed.join("\n");
    }

    /** 
     * Watches for input changes in order to count entries and validate their number
     */
    $scope.$watch('input', function(newInput) {
        $scope.entries = parseInput($scope.input).length;
        var v = validateInput(parseInput($scope.input));
        $scope.inputStatus = (v === true ? '' : v);
    }, true);

    /**
     * Adds a new page
     */
    $scope.newPage = function() {
        $scope.pages.push({records: [], progress: 0});
        $scope.activePage = $scope.pages.length - 1;
    }

    /**
     * Sets an active page
     * @param int page index
     */
    $scope.setActivePage = function(page) {
        $scope.activePage = page;
    }

    /**
     * Removes a page
     * @param int page index
     */
    $scope.closePage = function(page) {
        $scope.setActivePage(0);
        $scope.pages.splice(page, 1);
        setTimeout(function() {
            $scope.$apply();
        });
    }

    /**
     * Returns a class object with progress bar percentage
     * @returns style object
     */
    $scope.getProgressPercentage = function() {
        var stats = getProgressStats();
        var def = {width: '0%'};

        if ($scope.pages.length == 0) {
            return def;
        }

        var pc = (stats.finished / stats.total) * 100;
        return {width: pc + '%'};
    }

    $scope.getProgressText = function() {
        var stats = getProgressStats();
        return stats.total > 0 ? stats.finished + '/' + stats.total : '';
    }

    function getProgressStats() {
        var total = $scope.pages[$scope.activePage].records.length;
        var finished = 0;
        for (var i = 0; i < total; i++) {
            if (terminalStates.indexOf($scope.pages[$scope.activePage].records[i].status) > -1) {
                finished++;
            }
        }
        return {
            total: total,
            finished: finished
        };

    }

    /**
     * Returns a class for a progress bar
     * @returns string class
     */
    $scope.getProgressClass = function() {
        var page = $scope.activePage;
        var pc = parseInt($scope.getProgressPercentage().width);
        var cls = pc < 100 ? 'progress-striped active' : '';
        return cls;
    }

    /**
     * Creates the text portion of a progress bar
     * @returns string
     */
    $scope.getProgressClass = function() {
        var parsed = $

        var page = $scope.activePage;

        var pc = parseInt($scope.getProgressPercentage().width);
        var cls = pc < 100 ? 'progress-striped active' : '';
        return cls;
    }

    /**
     * Checks if there are more than 1000 entries
     * @param array data
     * @returns {String|Boolean}
     */
    function validateInput(data) {
        if (data.length > 1000) {
            return "Please, no more than 1000.";
        } else {
            return true;
        }
    }

    /**
     * Parse input from textarea value to an array
     * @param string input
     * @returns array
     */
    function parseInput(input) {
        var counted = [], trimmed, split = input ? input.split("\n") : [];
        for (var i = 0; i < split.length; i++) {
            trimmed = $.trim(split[i]);
            if (trimmed.length > 0) {
                counted.push(trimmed);
            }
        }
        return counted;
    }

}