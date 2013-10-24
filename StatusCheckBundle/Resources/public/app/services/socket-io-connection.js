app.factory('SocketIOConnection', function() {
    var srvc = {
        // You can autoconnect if you pass an url, 
        // but then you shouldn't depend connection events
        create: function(url) {
            var conn = new SocketIOConnection();
            return url ? conn.connect(url) : conn;
        }
    }

    function SocketIOConnection() {
        // Init
        var self = this;
        var socket;
        // Available Socket.io events
        var events = [
            'connect',
            'connecting',
            'disconnect',
            'connect_failed',
            'error',
            'message',
            'anything',
            'reconnect_failed',
            'reconnect',
            'reconnecting',
            'data_update'
        ];

        // Callback functions for events
        var callbacks = {};

        this.connect = function(url) {
            // Connect to the socket
            try {
            socket = io.connect(url);
        } catch (ex){
                console.log("Throwing");
                return;
            throw ex;
        }

            // Distribute events and bind callbacks
            for (var i in events) {
                (function(e) {
                    socket.on(e, function(data) {
                        if (typeof callbacks[e] === 'function') callbacks[e](data);
                    });
                })(events[i]);
            }
            return self;
        }

        /**
         * Assigns a callback to a socket event
         * @param string event Event name
         * @param function|null callback Callback function or null
         */
        this.setEventCallback = function(event, callback) {
            // Early break if event is invalid
            if (events.indexOf(event) === -1) throw 'Invalid callback event';

            callbacks[event] = callback;
            return self;
        }
    }

    return srvc;
});