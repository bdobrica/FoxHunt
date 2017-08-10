var config = require("../shared/config");
var fetchModule = require("fetch");
var geoLocation = require("nativescript-geolocation");
var timer = require ('timer');

function foxHuntTimer () {
    var id;

    this.start = function () {
        this.id = timer.setInterval (this.does, config.timedPosition);
    }

    this.stop = function () {
        timer.clearInterval (this.id);
    }

    this.does = function () {
        if (config.currentUser.userId == 0) return;
        console.log ("get location ... (timed)");
        geoLocation.getCurrentLocation({desiredAccuracy:3})
        .then(function(location){
            console.log("location: " + location.latitude + "," + location.longitude);
            return fetchModule.fetch(config.apiUrl + "?f=position", {
                method: "POST",
                body: JSON.stringify({
                    user_id: config.currentUser.userId,
                    token: config.currentUser.accessToken,
                    lat: location.latitude,
                    lng: location.longitude
                }),
                headers: {
                    "Content-Type": "application/json"
                }
            });
        })
        .catch(function(err){
            console.log('Error: ' + err.message);
        })
    }

    return this;
}

module.exports = foxHuntTimer;