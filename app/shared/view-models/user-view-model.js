var config = require("../../shared/config");
var fetchModule = require("fetch");
var Observable = require("data/observable").Observable;

function User(info) {
    info = info || {};
    var viewModel = new Observable({
        phone: info.phone || "",
        password: info.password || ""
    });

    viewModel.login = function() {
        return fetchModule.fetch(config.apiUrl + "?f=login", {
            method: "POST",
            body: JSON.stringify({
                phone: viewModel.get("phone"),
                password: viewModel.get("password")
            }),
            headers: {
                "Content-Type": "application/json"
            }
        })
        .then(handleErrors)
        .then(function(response){
            return response.json();
        })
        .then(function(data){
            //if (!data.access_token) throw true;
            config.token = data.access_token;
        })
    };

    return viewModel;
}

function handleErrors(response) {
    if (!response.ok) {
        console.log(JSON.stringify(response));
        throw Error(response.statusText);
    }
    return response;
}

module.exports = User;