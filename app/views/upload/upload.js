var page;

var camera = require("nativescript-camera");
var imageModule = require("ui/image");
var fileSystem = require("file-system");
var imageSource = require("image-source");
var permissions = require("nativescript-permissions");
var bghttp = require("nativescript-background-http");
var config = require("../../shared/config");
var enums = require("ui/enums");
var db = require("../../shared/couchbase");
var frameModule = require("ui/frame");
var application = require("application");


var session = bghttp.session("image-upload");

var GeoImagesViewModel = require("../../shared/view-models/geoimages-view-model");
var geoImages = new GeoImagesViewModel();

var backEvent = function(args) {
    args.cancel = true;
    try {
        geoImages.back();
    }
    catch (error) {
    }
    frameModule.topmost().navigate("views/list/list");
};

exports.foxHuntInit = function(args){
    page = args.object;
    page.bindingContext = geoImages;
    geoImages.set('locName', config.selectedGeoUnit.locName);
    if (application.android)
        application.android.on(application.AndroidApplication.activityBackPressedEvent, backEvent);
};

exports.foxHuntTerm = function(args) {
    if (application.android)
        application.android.off(application.AndroidApplication.activityBackPressedEvent, backEvent);
};

exports.foxHuntStartUpload = function(args){
    geoImages.upload();
};

exports.foxHuntTakePhoto = function(args){
    geoImages.photo();
};