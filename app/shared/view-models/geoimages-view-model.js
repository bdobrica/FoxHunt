var config = require("../../shared/config");
var observableModule = require("data/observable");
var ObservableArray = require("data/observable-array").ObservableArray;
var GeoLocation = require("nativescript-geolocation");

var camera = require("nativescript-camera");
var imageModule = require("ui/image");
var fileSystem = require("file-system");
var imageSource = require("image-source");
var permissions = require("nativescript-permissions");
var bghttp = require("nativescript-background-http");
var session = bghttp.session("image-upload");
var enums = require("ui/enums");

var db = require("../../shared/couchbase");

var ImgList = new ObservableArray(db.executeQuery("geoimages"));

function GeoImages () {
    var viewModel = new observableModule.fromObject({
        ImgList: ImgList,
        locName: config.selectedGeoUnit.locName,
        gpsLatitude: 0.00,
        gpsLongitude: 0.00
    });

    viewModel.position = function(){
        console.log("get position");
        GeoLocation.getCurrentLocation({desiredAccuracy:3})
        .then(function(location){
            viewModel.set("gpsLatitude", location.latitude);
            viewModel.set("gpsLongitude", location.longitude);
            console.log("location: " + location.latitude + "," + location.longitude);
        })
        .catch(function(err){
            console.log('Error: ' + err.message);
        })
    };

    viewModel.photo = function(){
        var options = { width: 480, height: 480, keepAspectRatio: true, saveToGallery: true };
        viewModel.position();
        camera.takePicture(options)   
            .then(function (imageAsset) {
                imageSource.fromAsset(imageAsset)
                    .then(function (image){
                        var date = new Date();
                        var stamp = Math.floor((date.getTime() - (new Date(date.getFullYear(),0,0)).getTime())/1000).toString(36);
                        var name = config.selectedGeoUnit.sirutaCode + "_" + stamp + ".jpg";
                        var path = fileSystem.path.join (fileSystem.knownFolders.documents().path, name);
                        var saved = image.saveToFile (path, enums.ImageFormat.jpeg, 85);

                        db.createDocument({
                            name: name,
                            stamp: stamp,
                            path: path,
                            latitude: viewModel.get("gpsLatitude"),
                            longitude: viewModel.get("gpsLongitude"),
                            status: "taken",
                            geoUnit: config.selectedGeoUnit
                        });

                        while (ImgList.length) ImgList.pop();
                        ImgList.push(db.executeQuery("geoimages"));

                        console.log ("taken - ", path);
//                        foxUploadImages(path);

                        var localImage = imageSource.fromFile(path);
                    })
                    .catch(function(err){
                    })
            })
            .catch(function(err){
            });
    };

    viewModel.upload = function(){
        var result = viewModel.get("ImgList").pop();
        console.log(JSON.stringify(result));

        var parameters = {
            geounit: result.geoUnit.locationId,
            lat: result.latitude,
            lng: result.longitude
            };

        var request = {
            url: config.apiUrl + '?f=upload&p=' + escape(JSON.stringify(parameters)),
            method: "POST",
            headers: {
                "Content-Type": "application/octet-stream",
                "File-Name": result.name
            }
        };

        var task = session.uploadFile(result.path, request);
        task.document = result;

        var logEvent = function(e){
            console.log(JSON.stringify(e));
        };

        var uploadComplete = function(e){
            e.object.document.status = "uploaded";
            db.updateDocument(e.object.document._id, e.object.document);
            if (viewModel.get("ImgList").length)
                viewModel.upload ();
        };

        task.on("progress", logEvent);
        task.on("error", logEvent);
        task.on("complete", uploadComplete);

        return task;
    };

    viewModel.pause = function(){

    };

    return viewModel;
}

module.exports = GeoImages;