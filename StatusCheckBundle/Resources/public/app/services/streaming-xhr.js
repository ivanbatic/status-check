app.factory('streamingXhr', function() {
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


//    var srvc = {
//        xhr: xhr,
//        data: {},
//        setData: function(data) {
//            this.data = data;
//        },
//        send: function(callback) {
//            if (typeof callback === 'function') {
//                callback();
//            }
//        }
//    }
//
//
//    var read = 0;
//    var incoming = '';
//    xhr.onreadystatechange = function() {
//        if (xhr.readyState == 3) {
//            var incoming = xhr.responseText.substring(read);
//            try {
//                var split = incoming.split('\n');
//                for (i = 1; i < split.length; i++) {
//                    var r = JSON.parse(split[i]);
//                    var newLi = $('<li/>').text(r.request_url + ' - ' + r.status).appendTo(container);
//                }
//            } catch (e) {
//            }
//            read = xhr.responseText.length;
//        }
//    };
//    xhr.open('GET', '/app_dev.php/check-status', true);
//    xhr.send();
});