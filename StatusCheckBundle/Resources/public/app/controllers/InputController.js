function InputController($scope, streamingXhr) {
    // Number of entries in the textarea
    $scope.entries = 0;
    // Textarea content
    $scope.input = '';
    // Input limit
    $scope.limit = 1000;
    // Message below input field
    $scope.inputStatus = '';

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
        // Store active page in case it changes during the request
        var activePage = $scope.$parent.activePage;
        // Early break if input is invalid
        if (validateInput(formData) !== true) return;

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
}