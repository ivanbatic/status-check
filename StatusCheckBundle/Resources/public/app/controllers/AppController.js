function AppController($scope) {
    // Active page index
    $scope.activePage = 0;
    // Array of pages
    $scope.pages = [{
            records: [],
            progress: 0
        }];

    /**
     * Adds/Updates a record
     * @param object newRecord
     * @param int page index
     */
    $scope.updateRecord = function(record, page) {
        // Init
        var updated = false;

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
    }

}