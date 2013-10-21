app.factory('StreamingXhr', function() {
    var srvc = {
        create: function(url, method, async) {
            return new Request(url, method, async);
        }
    }

    var Request = function(url, method, async) {
        var self = this;
        var read = 0;
        var state = 0;
        var incoming = '';
        var xhr = new XMLHttpRequest();
        var data = '';
        var method = method || 'GET';
        var async = async === false ? false : true;
        var callbacks = {};



        this.setData = function(record) {
            data = $.param(record);
        }

        this.registerCallback = function(state, callback) {
            if (typeof callback === 'function') {
                callbacks[state] = callback;
            }
        }

        this.fire = function() {
            xhr.open(method, url, async);

            if (method == 'POST') {
                xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            }
            console.log("Sending data", data);
            xhr.send(data);
        }

        xhr.onreadystatechange = function() {
            if (typeof callbacks[xhr.readyState] === 'function') {
                callbacks[xhr.readyState](xhr.responseText.substr(read));
            }
            read = xhr.responseText.length;
        }


    }
    return srvc;
});