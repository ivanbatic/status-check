function AppController($scope, SocketIOConnection, $http) {
    try {
        SocketIOConnection
                .create('http://localhost:5150')
                .setEventCallback('data_update', updateData);
    } catch (ex) {
        $scope.error = "Couldn't connect to the socket server, please try again later.";
    }
    // Active page index
    $scope.activePage = 0;
    $scope.error;

    // Array of pages
    $scope.pages = [{
            records: [],
            progress: 0
        }];

    /**
     * Adds/Updates a record
     * @param object newRecord
     * @param int page index
     * @deprecated Used in php version
     */
    $scope.updateRecord = function(record, page) {
        // Init
        var updated = false;

        // Normalize Mongo data
        if (record._id) record.id = record._id;

        // Check if this website is already in the list
        // If so, update the existing record
        for (var i = 0; i < $scope.pages[page].records.length; i++) {
            if ($scope.pages[page].records[i].id == record.id) {
                $scope.pages[page].records[i] = record;
                updated = true;
                break;
            }
        }

        // It's not an update, new record should be added
        if (!updated) {
            if (record.parent_id) {
                // Record is a child of another, meaning it's a redirect
                // It should be inserted at a particular place, below the parent
                // Find the parent and splice the array at that point, inserting the new record
                for (var i = 0; i < $scope.pages[page].records.length; i++) {
                    if ($scope.pages[page].records[i].id == record.parent_id) {
                        $scope.pages[page].records.splice(i + 1, 0, record);
                        break;
                    }
                }
            } else {
                // It's not a child, append it to the list
                $scope.pages[page].records.push(record);
            }
        }
    };

    function updateData(data) {
        for (var i = 0; i < data.length; i++) {
            var pageIndex = data[i].page_index;
            // Normalize mongo
            if (data[i]._id) data[i].id = data[i]._id;

            // Create missing pages
            while ($scope.pages[pageIndex] === undefined) $scope.createPage();

            var updated = false;
            // Update where neccessary, insert otherwise
            for (var j = 0; j < $scope.pages[pageIndex].records.length; j++) {
                if ($scope.pages[pageIndex].records[j].id === data[i].id) {
                    $scope.pages[pageIndex].records[j] = data[i];
                    updated = true;
                    break;
                }
            }
            if (!updated) $scope.pages[pageIndex].records.push(data[i]);
        }
        $scope.$apply();
    }

    /**
     * Adds a new page
     */
    $scope.createPage = function() {
        $scope.pages.push({records: [], progress: 0});
        $scope.activePage = $scope.pages.length - 1;
    }


}