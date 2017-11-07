var express = require('express');
var app = express();
var server = require('http').createServer(app);
var io = require('socket.io')(server);
var socketioJwt = require('socketio-jwt');
require('dotenv').config({ path: '.env' });

var temp="";
var humid="";
var ec="";
var lux="";
var ph="";
var cnt=0;

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
        case "partner": return new partner(socket);
        case "device": return new device(socket);
        case "user": return new user(socket);
    }
}

// Handling each role
function partner(socket) {
    this._socket = socket;

    this._socket.join("partner_room_" + socket.decoded_token.id);

    io.to("partner_room_" + socket.decoded_token.id)
        .emit("device_list", getAvailableDevices(socket.decoded_token.stores, devices));

    this._socket.on("change_device_state", function (data) {
        //data nhận từ partner sau đó emit sang device
        console.log("Từ partner nè:");
        console.log(data.data);
        io.sockets.emit("change_device_" + data.device_id + "_state", data.data);
    });

    this._socket.on("get_device_state", function(data){
        console.log(data);
        io.sockets.emit("get_"+ data + "_state","");
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

    this._socket.on("device_state", function (data) {
        //data nhận từ device sau đó emit sang partner
        var index = data.data;
        var check = false;
        if(index.length < 10) {
            check = true;
        } else {
            check = false
        }
        if(check){
            var dataJson;
            var dataEmit;
            if(index.charAt(0)=='1'){
                cnt++;
                temp="";
                for(var i=2; i<=index.length; i++){
                    temp += index.charAt(i);
                }
            } else if(index.charAt(0)== '2'){
                cnt++;
                humid="";
                for(var i=2; i<=index.length; i++){
                    humid += index.charAt(i);
                }
            } else if(index.charAt(0)== '3'){
                cnt++;
                ec="";
                for(var i=2; i<=index.length; i++){
                    ec += index.charAt(i);
                }
            } else if(index.charAt(0)== '4'){
                cnt++;
                lux="";
                for(var i=2; i<=index.length; i++){
                    lux += index.charAt(i);
                }
            } else if(index.charAt(0)== '5'){
                cnt++;
                ph="";
                for(var i=2; i<=index.length; i++){
                    ph += index.charAt(i);
                }
            }
            if(cnt%5==0){

                var emitString = "";
                emitString += "*TEMPERATURE*: *";
                emitString += temp;
                emitString += "*, *HUMIDITY*: *";
                emitString += humid;
                emitString += "*, *EC*: *";
                emitString += ec;
                emitString += "*, *LUX*: *";
                emitString += lux;
                emitString += "*, *pH*: *";
                emitString += ph;
                emitString += "*"
                console.log(emitString);
                console.log("cnt nè: "+cnt)
                var emitData  = "";
                emitData += "{";
                for(var i=0; i<=emitString.length; i++){

                    if(emitString.charAt(i)=='*'){
                        emitData += '"';
                    } else {
                        emitData += emitString.charAt(i);
                    }

                }
                emitData += "}";
                console.log(emitData);

                try {
                    dataJson = JSON.parse(emitData);
                    dataEmit = dataJson;
                    console.log('data parse');
                    console.log(dataJson);
                } catch(err) {
                    console.log(err);
                }
                io.sockets.emit("device_" + data.device_id + "_state", dataEmit);
            }
        } else {
            console.log(index);
            var emitData  = "";
            emitData += "{";
            for(var i=0; i<=index.length; i++){

                if(index.charAt(i)=='*'){
                    emitData += '"';
                } else {
                    emitData += index.charAt(i);
                }

            }
            emitData += "}";
             console.log(emitData);

                try {
                    dataJson = JSON.parse( emitData);
                    dataEmit = dataJson;
                    console.log('data parse');
                    console.log(dataJson);
                } catch(err) {

                    console.log(err);
                }
            io.sockets.emit("device_" + data.device_id + "_state", dataEmit);
        }
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
