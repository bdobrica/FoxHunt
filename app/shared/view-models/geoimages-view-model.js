var config = require("../../shared/config");
var observableModule = require("data/observable");
var ObservableArray = require("data/observable-array").ObservableArray;
var GeoLocation = require("nativescript-geolocation");

var dialogsModule = require ("ui/dialogs");

var camera = require("nativescript-camera");
var imageModule = require("ui/image");
var fileSystem = require("file-system");
var imageSource = require("image-source");
//var permissions = require("nativescript-permissions");
var bghttp = require("nativescript-background-http");
var session = bghttp.session("image-upload");
var enums = require("ui/enums");

var db = require("../../shared/couchbase");
var ti = require("../../shared/takenimages");

var takenImagesRaw = ti.executeQuery("takenimages");
var takenImages = [];
for (var c = 0; c < takenImagesRaw.length; c++) {
    takenImages[takenImagesRaw[c].id] = takenImagesRaw[c];
}

if (debug) console.log(JSON.stringify(takenImagesRaw));

var ImgList = new ObservableArray(db.executeQuery("geoimages"));

var debug = true;

function GeoImages () {
    var viewModel = new observableModule.fromObject({
        ImgList: ImgList,
        locName: config.selectedGeoUnit.locName,
        gpsLatitude: 0.00,
        gpsLongitude: 0.00
    });

    viewModel.position = function(){
        if (debug) console.log("get position");
        GeoLocation.getCurrentLocation({desiredAccuracy:3})
        .then(function(location){
            viewModel.set("gpsLatitude", location.latitude);
            viewModel.set("gpsLongitude", location.longitude);
            if (debug) console.log("location: " + location.latitude + "," + location.longitude);
        })
        .catch(function(err){
            if (debug) console.log('Error: ' + err.message);
        })
    };

    viewModel.photo = function(){
        var options = { width: 480, height: 480, keepAspectRatio: true, saveToGallery: true };
        viewModel.position();
        camera.requestPermissions();
   
        camera.takePicture (options)
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

                        config.selectedGeoUnit.imagesTaken ++;

                        while (ImgList.length) ImgList.pop();
                        ImgList.push(db.executeQuery("geoimages"));

                        if (debug) console.log ("taken - ", path);
//                        foxUploadImages(path);

                        var localImage = imageSource.fromFile(path);
                    })
                    .catch(function(err){
                        if (debug) console.log('Error: ' + err.message);
                    })
            })
            .catch(function(err){
                if (debug) console.log('Error: ' + err.message);
            });

    };

    viewModel.upload = function(){
        var result = viewModel.get("ImgList").pop();
        if (!result) {
            dialogsModule.alert ("There are no images in queue!");
            return;
        }
        if (debug) console.log(JSON.stringify(result));

        var parameters = {
            geounit: result.geoUnit.locationId,
            lat: result.latitude,
            lng: result.longitude,
            user_id: config.currentUser.userId,
            token: config.currentUser.accessToken
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
            if (debug) console.log(JSON.stringify(e));
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
        if (debug) console.log ("pause");


    };

    viewModel.count = function(locationId, imagesTaken){
        var p;

        if (takenImages[locationId]) {
            var doc = {
                id: locationId,
                p: parseInt(takenImages[locationId].p) + imagesTaken
            };
            var did = takenImages[locationId]._id;
            if (debug) console.log("update document: " + did);

            ti.updateDocument(did, doc);
            p = takenImages[locationId].p = doc.p;
        }
        else {
            var doc = {
                id: locationId,
                p: imagesTaken
                };
            var did = ti.createDocument(doc);
            if (debug) console.log("create document: " + did);
            doc._id = did;
            p = doc.p;
            takenImages[locationId] = doc;
        }
        return p;
    };

    viewModel.back = function(){
        for (var c = 0; c < config.geoUnitList.length; c++) {
            var item = config.geoUnitList.getItem(c);

            if ((config.selectedGeoUnit.locationId == item.id) && config.selectedGeoUnit.imagesTaken > 0) {
                item.p = viewModel.count (config.selectedGeoUnit.locationId, config.selectedGeoUnit.imagesTaken);
                config.geoUnitList.setItem(c, item);

                if (debug) console.log (JSON.stringify(config.parentUnitListIds));
                if (config.parentUnitListIds.length > 1) {
                    viewModel.count (config.parentUnitListIds[0], config.selectedGeoUnit.imagesTaken);
                    viewModel.count (config.parentUnitListIds[1], config.selectedGeoUnit.imagesTaken);
                }

                //config.selectedGeoUnit.imagesTaken = 0;
            }
        }
        throw true;
    };

    return viewModel;
}

module.exports = GeoImages;