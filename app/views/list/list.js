var page;
var GeoUnitsViewModel = require("../../shared/view-models/geounits-view-model");
var dialogsModule = require("ui/dialogs");
var frameModule = require("ui/frame");
var application = require("application");
var geoUnits = new GeoUnitsViewModel();
var debug = false;

/*var activity = applicationModule.android.startActivity ||
        applicationModule.android.foregroundActivity ||
        frameModule.topmost().android.currentActivity ||
        frameModule.topmost().android.activity;*/

var backEvent = function(args) {
    args.cancel = true;
    try {
        geoUnits.back();
    }
    catch (error) {
        frameModule.topmost().navigate("views/list/list");
        //frameModule.topmost().navigate("views/login/login");
    }
};

exports.foxHuntInit = function(args) {
    page = args.object;
    page.bindingContext = geoUnits;
    if (application.android)
        application.android.on(application.AndroidApplication.activityBackPressedEvent, backEvent);

    if (debug) console.log (geoUnits.parentGeoUnits);
};

exports.foxHuntTerm = function(args) {
    if (application.android)
        application.android.off(application.AndroidApplication.activityBackPressedEvent, backEvent);
};

exports.foxHuntSearch = function(args) {
    if (debug) console.log("Search ...");
    try {
        geoUnits.search(args, page.getViewById("search").text);
    }
    catch(error){
        dialogsModule.alert ("Could not find location!");
    }
};

exports.foxHuntLocator = function(args){
    if (debug) console.log(args.object.className);
    args.object.className = 'warning fa';
    if (debug) console.log(args.object.className);
    if (debug) console.log("Locate ...");
    try {
        geoUnits.locate(args);
    }
    catch(error) {
        btn.className = 'info fa';
    }
};

exports.foxHuntItemTap = function(args) {
    try {
        geoUnits.tap(args);
    }
    catch(error) {
        frameModule.topmost().navigate("views/upload/upload");
    }
};