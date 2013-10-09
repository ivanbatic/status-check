app.directive('textareaAutoresize', function() {
    return function(scope, element) {
        var lastLine, rows = element.attr("rows");
        element.bind('keyup', function() {
            lastLine = element.val().split("\n").length;
            if (lastLine >= rows) {
                rows = lastLine + 1;
                element.attr("rows", rows);
            }
        });
    };
});