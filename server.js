var express = require('express');
var app = express();
var server = require('http').createServer(app);
var io = require('socket.io')(server);
var socketioJwt = require('socketio-jwt');
require('dotenv').config({
    path: '.env'
});

const EVENT_CHANGE_DEVICE_STATE = "change_device_state";
const EVENT_GET_STATUS = "get_status";
const EVENT_DEVICE_STATE = "device_state";

// Let express show auth.html to client
app.use(express.static(__dirname + '/static'));
app.get('/', function (req, res, next) {
    res.sendFile(__dirname + '/static/auth.html');
});
app.get('/partner', function (req, res, next) {
    res.sendFile(__dirname + '/static/partner.html');
});
app.get('/device', function (req, res, next) {
    res.sendFile(__dirname + '/static/device.html');
});

var partners = [];

var devices = [];

// Accept connection and authorize token
io.on('connection', socketioJwt.authorize({
    secret: process.env.JWT_SECRET,
    timeout: 15000
}));
// When authenticated, send back name + email over socket
io.on('authenticated', function (socket) {
    console.log(socket.decoded_token);
    getRole(socket);
    socket.emit('info', socket.decoded_token);
});

function getRole(socket) {
    switch (socket.decoded_token.guard.toLowerCase()) {
        case "partner":
            return new partner(socket);
        case "device":
            return new device(socket);
        case "user":
            return new user(socket);
    }
}

// Handling each role
function partner(socket) {
    this._socket = socket;

    this._socket.join("partner_room_" + socket.decoded_token.id);

    io.to("partner_room_" + socket.decoded_token.id)
        .emit("device_list", getAvailableDevices(socket.decoded_token.stores, devices));

    this._socket.on(EVENT_CHANGE_DEVICE_STATE, function (data) {
        io.sockets.emit("change_device_" + data.device_id + "_state", data.data);
    });

    this._socket.on(EVENT_GET_STATUS, function (data) {
        data = data.data;
        io.sockets.emit("get_status_" + data.device_id, "");
    });
}

function device(socket) {
    this._socket = socket;

    var device = devices.find(function (device) {
        return device.device_id === socket.decoded_token.id
    });

    if (!device) {
        devices.push({
            device_id: socket.decoded_token.id,
            store_id: socket.decoded_token.store_id
        })
    }

    this._socket.join("store_room_" + socket.decoded_token.store_id);

    this._socket.on(EVENT_DEVICE_STATE, function (data) {
        var preData = typeof data.data == 'undefined' ? data : data.data;
        var a = preData.indexOf('*');
        var dataJson;
        var dataEmit;
        console.log(preData, a);
        if (a!=-1 && preData.indexOf('*', a + 1)!=-1) {
            console.log('****');
            preData = preData.replace(/\*/g, '"');
            try {
                dataJson = JSON.parse('{' + preData + '}');
                dataEmit = dataJson;
                console.log('data parse');
                console.log(dataJson);
            } catch(err) {
                dataEmit = preData;
                console.log(err);
            }
        } else {
            dataEmit = preData;
        }
        io.sockets.emit("device_" + data.device_id + "_state", dataEmit);
    });
}

function user(socket) {
    this._socket = socket;
    this._socket.join("user_room");
}

function getAvailableDevices(stores, devices) {
    var availableDevices = devices.filter(function (device) {
        return stores.indexOf(device.store_id) > -1;
    });
    return availableDevices;
}
// Start Node server at port 3000
server.listen(3000);