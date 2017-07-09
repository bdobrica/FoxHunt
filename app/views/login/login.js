var page;
var UserViewModel = require("../../shared/view-models/user-view-model");
var dialogsModule = require("ui/dialogs");
var frameModule = require("ui/frame");
var user = new UserViewModel();

exports.foxHuntInit = function (args) {
    page = args.object;
    page.bindingContext = user;
};
exports.foxHuntSignIn = function () {
    user.login()
        .catch(function(error){
            console.log(error);
            dialogsModule.alert({
                message: "Unfortunately we could not find your account.",
                okButtonText: "OK"
            })
            return Promise.reject();
        })
        .then(function(){
            console.log("next view");
            frameModule.topmost().navigate("views/list/list");
        });
};