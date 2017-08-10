var config = require("../../shared/config");
var fetchModule = require("fetch");
var observableModule = require("data/observable");
var applicationSettings = require ("application-settings");

var debug = false;

function User(info) {

    if (debug) console.log ("u: " + info.username + " p:" + info.password)

    info = info || {};
    var viewModel = new observableModule.fromObject({
        username: info.username || "",
        password: info.password || ""
    });

    viewModel.login = function() {
        return fetchModule.fetch(config.apiUrl + "?f=login", {
            method: "POST",
            body: JSON.stringify({
                username: viewModel.get("username"),
                password: viewModel.get("password")
            }),
            headers: {
                "Content-Type": "application/json"
            }
        })
        .then(function(response){
            if (debug) console.log ('http header');
            if (!response.ok)
                throw Error (response.statusText);
            return response;
        })
        .then(function(response){
            if (debug) console.log ("response function");
            if (debug) console.log (JSON.stringify(response));
            var r = response.json();
            if (debug) console.log (JSON.stringify(r));
            return r;
        })
        .then(function(data){
            if (debug) console.log ("data function");
            if (debug) console.log ("data ok : " + data.ok);
            if (debug) console.log ("data error : " + data.error);
            if (!data.ok || data.error) {
                if (debug) console.log ("error!!!");
                throw true;
            }
            applicationSettings.setString ("username", viewModel.get("username"));
            applicationSettings.setString ("password", viewModel.get("password"));


            if (debug) console.log (applicationSettings.getString("username"));
            if (debug) console.log (applicationSettings.getString("password"));

            config.currentUser.accessToken = data.access_token;
            config.currentUser.userId = data.user_id;
            if (debug) console.log ("token: " + config.currentUser.accessToken);
            if (debug) console.log ("user id: " + config.currentUser.userId);
            return data;
        })
    };

    return viewModel;
}

module.exports = User;