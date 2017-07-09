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

var session = bghttp.session("image-upload");

var GeoImagesViewModel = require("../../shared/view-models/geoimages-view-model");
var geoImages = new GeoImagesViewModel();
/*
var extractImageName = function(fileUri) {
    var pattern = /[^/]*$/;
    var imageName = fileUri.match(pattern);
    return imageName;
};

var foxUploadImages = function(path){
    var imageName = extractImageName(path);
    var parameters = {};

    var request = {
        url: config.apiUrl + '?f=upload&p=' + escape(JSON.stringify(parameters)),
        method: "POST",
        headers: {
            "Content-Type": "application/octet-stream",
            "File-Name": imageName
        }
    };

    var task = session.uploadFile(path, request);

    var logEvent = function(e){
        console.log(JSON.stringify(this));
        console.log(JSON.stringify(e));
    };
    task.on("progress", logEvent);
    task.on("error", logEvent);
    task.on("complete", logEvent);

    return task;
};
*/

exports.foxHuntInit = function(args){
    page = args.object;
    page.bindingContext = geoImages;
};

exports.foxHuntStartUpload = function(args){
    geoImages.upload();
};

exports.foxHuntTakePhoto = function(args){
    geoImages.photo();
};