function InputController($scope, StreamingXhr, $http) {
    // Number of entries in the textarea
    $scope.entries = 0;
    // Textarea content
    $scope.input = '';
    // Input limit
    $scope.limit = 1000;
    // Message below input field
    $scope.inputStatus = '';

    $scope.closePageButton = {
        default: 'Close Page',
        closing: 'Closing...',
        text: 'Close Page'
    };
    $scope.startButton = {
        default: 'Start the Awesomness',
        clearing: 'Clearing page...',
        sending: 'Sending a new request...',
        text: 'Start the Awesomness'
    }

    /**
     * Filters the input content to remove non-url entries
     */
    $scope.filterInput = function() {
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

    /**
     * Checks if there are more than 1000 entries
     * @param array data
     * @returns {String|Boolean}
     */
    function validateInput(data) {
        return data.length > $scope.limit
                ? ('Please, no more than ' + $scope.limit + '.')
                : true;
    }


    /**
     * Sends the xhr request
     * @returns void
     */
    $scope.submitForm = function() {
        var formData = parseInput($scope.input);
        // Early break if input is invalid
        if (validateInput(formData) !== true) return;
        // Store active page in case it changes during the request
        var activePage = $scope.$parent.activePage;

        // Prepare a request object
        var stream = streamingXhr.create('check-status', 'POST');
        stream.setData({hosts: formData});

        // If there are no active pages, create a new one
        if ($scope.$parent.pages.length === 0) $scope.$parent.pages.push({
                records: [],
                progress: 0
            });

        // Reset the active page data
        $scope.$parent.pages[activePage] = {records: [], progress: 0};

        // Assign a function that will be excecuted whenever a response comes in
        stream.registerCallback(3, function(response) {
            try {
                var record, split = response.split('\n');
                for (var i = 1; i < split.length; i++) {
                    record = JSON.parse(split[i]);
                    $scope.updateRecord(record, activePage);
                    $scope.$apply();
                }
            } catch (e) {
            }
        });

        // Send the request
        stream.fire();
    }

    /**
     * Submit the form, mongo style
     * First, the active page needs to be closed,
     * when it is, then we can send a new request 
     */
    $scope.submitToMongo = function() {
        console.log("Submitting");
        var formData = parseInput($scope.input);
        // Early break if input is invalid
        if (validateInput(formData) !== true) return;
        var page = $scope.$parent.activePage;

        $scope.startButton.text = $scope.startButton.clearing;
        
        closeMongoPage(page, function() {
            $scope.startButton.text = $scope.startButton.sending;
            $scope.pages.splice(page, 1, {records: [], progress: 0});
            $http({
                method: 'post',
                url: 'check-status',
                data: $.param({hosts: formData, page_index: page}),
                responseType: 'json',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).success(function(response) {
                $scope.startButton.text = $scope.startButton.default;
                for (var i = 0; i < response.length; i++) {
                    $scope.updateRecord(response[i], page);
                }
            });
        });
    }


    function closeMongoPage(pageIndex, callback) {
        $http({
            method: 'post',
            url: 'check-status/close-page',
            data: $.param({page_index: pageIndex}),
            responseType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function(response) {
            setTimeout(function() {
                $scope.$apply();
            });
            if (typeof callback === 'function') {
                callback(response);
            }
        });
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
        $scope.closePageButton.text = $scope.closePageButton.closing;
        closeMongoPage(page, function() {
            $scope.pages.splice(page, 1);
            $scope.setActivePage(0);
            $scope.closePageButton.text = $scope.closePageButton.default;
        });
    }
}