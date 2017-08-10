var page;
var UserViewModel = require("../../shared/view-models/user-view-model");
var dialogsModule = require("ui/dialogs");
var frameModule = require("ui/frame");
var applicationSettings = require("application-settings");
var user = new UserViewModel({
    username: applicationSettings.getString("username", ""),
    password: applicationSettings.getString("password", "")
});

var debug = false;

exports.foxHuntInit = function (args) {
    page = args.object;
    page.bindingContext = user;
};
exports.foxHuntSignIn = function () {
    user.login().then(function(){
        frameModule.topmost().navigate("views/list/list");
    })
    .catch(function(error){
        //if (debug) 
            console.log(error.message);
        dialogsModule.alert("Unfortunately we could not find your account.");
    });
};