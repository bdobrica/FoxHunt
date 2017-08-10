var config = require("../../shared/config");
var observableModule = require("data/observable");
var ObservableArray = require("data/observable-array").ObservableArray;
var GeoUnitsArray = require("../../shared/geounits");
var GeoLocation = require("nativescript-geolocation");

var db = require("../../shared/couchbase");

var GeoList = new ObservableArray(GeoUnitsArray);

var debug = false;

config.parentUnitList = [];

function GeoUnits () {
    var viewModel = new observableModule.fromObject({GeoList: GeoList});

    viewModel.tap = function(args){
        var selectedGeoUnit = {
            locationId: args.view.bindingContext.id,
            sirutaCode: args.view.bindingContext.cs,
            locLatitude: args.view.bindingContext.lat,
            locLongitude: args.view.bindingContext.lng,
            locName: args.view.bindingContext.name,
            imagesTaken: 0
            };
        config.selectedGeoUnit = selectedGeoUnit;

        if (debug) console.log (config.selectedGeoUnit.locName);

        var searchArray = GeoUnitsArray;
        for (var c = 0; c < config.parentUnitList.length; c++)
            searchArray = searchArray[config.parentUnitList[c]].children;
        for (var c = 0; c < searchArray.length; c++) {
            if (debug) console.log (searchArray[c].name);
            if (searchArray[c].selected) {
                delete (searchArray[c].selected);
                if (debug) console.log ("deleted index: " + c);
                }
        }

        args.view.bindingContext.selected = 1;

        if (args.view.bindingContext.pathTo) {
            if (debug) console.log (args.view.bindingContext.pathTo);
            config.parentUnitList = [
                args.view.bindingContext.pathTo.a,
                args.view.bindingContext.pathTo.b
                ];
            delete args.view.bindingContext.pathTo;
        }

        if (args.view.bindingContext.children.length == 0) {
            if (debug) console.log ('no children');
            config.geoUnitList = GeoList;
            if (config.parentUnitList.length > 1) {
                config.parentUnitListIds = [];
                config.parentUnitListIds.push(GeoUnitsArray[config.parentUnitList[0]].id);
                config.parentUnitListIds.push(GeoUnitsArray[config.parentUnitList[0]].children[config.parentUnitList[1]].id);
            }
            throw true;
        }

        var children = args.view.bindingContext.children;

        config.parentUnitList.push(args.index);
        if (debug) console.log ("added : " + args.index);
        if (debug) console.log(JSON.stringify(config.parentUnitList));
        while (GeoList.length) GeoList.pop();
        //
        GeoList.push(children);
    };

    viewModel.search = function(args, str){
        var searchArray = GeoUnitsArray;
        var a, b, c;
        var found = [];

        str = str.toLowerCase ();

        if (debug) console.log (str);

        for (a = 0; a < searchArray.length; a++) {
            if (debug) console.log (searchArray[a].name);
            for (b = 0; b < searchArray[a].children.length; b++) {
                for (c = 0; c < searchArray[a].children[b].children.length; c++) {
                    if (searchArray[a].children[b].children[c].name.toLowerCase().indexOf(str) > -1)
                        found.push({"a":a,"b":b,"c":c});
                }
            }
        }

        if (found.length < 1) {
            if (debug) console.log ("could not find!");
            throw true;
        }

        while (GeoList.length) GeoList.pop();
        for (a = 0; a < found.length; a++) {
            var item = searchArray[found[a].a].children[found[a].b].children[found[a].c];
            item.pathTo = found[a];
            GeoList.push(item);
            }

        if (debug) console.log (found);
    };

    viewModel.locate = function(args){
        args.object.className = "info fa";
        GeoLocation.getCurrentLocation({desiredAccuracy:3,maximumAge:2000,timeout:20000}).then(function(location){
            while (config.parentUnitList.length) config.parentUnitList.pop();

            var min_dist;
            var min_index;
            var searchArray = GeoUnitsArray;
            do {
                min_dist = 32400.00;
                min_index = 0;
                for (var c = 0; c < searchArray.length; c++) {
                    var cur_dist = (searchArray[c].lat - location.latitude)*(searchArray[c].lat - location.latitude)
                            + (searchArray[c].lng - location.longitude)*(searchArray[c].lng - location.longitude);
                    if (min_dist > cur_dist) {
                        min_dist = cur_dist;
                        min_index = c;
                        }
                    }

                if (searchArray[min_index].children.length > 0) {
                    config.parentUnitList.push(min_index);
                    searchArray[min_index].selected = 1;
                }
                searchArray = searchArray[min_index].children;
            }
            while (searchArray.length > 0);

            searchArray = GeoUnitsArray;
            for (c = 0; c < config.parentUnitList.length; c++)
                searchArray = searchArray[config.parentUnitList[c]].children;

            if (debug) console.log(JSON.stringify(config.parentUnitList));
            if (debug) console.log("min_index - ", min_index);
            searchArray[min_index].selected = 1;

            while (GeoList.length) GeoList.pop();
            GeoList.push (searchArray);
        })
        .catch(function(err){
            if (debug) console.log("Error: " + err.message);
        });
    };
    
    viewModel.back = function(args){
        if (debug) console.log ("back to list");
        if (config.parentUnitList.length == 0)
            throw true;
        if (debug) console.log(JSON.stringify(config.parentUnitList));
        var index = config.parentUnitList.pop();
        if (debug) console.log(JSON.stringify(config.parentUnitList));
        var searchArray = GeoUnitsArray;

        if (config.selectedGeoUnit.imagesTaken > 0) {
            if (config.parentUnitList.length > 0) {
                console.log ("has county ...");
                GeoUnitsArray[config.parentUnitList[0]].p += config.selectedGeoUnit.imagesTaken;
                GeoUnitsArray[config.parentUnitList[0]].children[index].p += config.selectedGeoUnit.imagesTaken;
            }
            config.selectedGeoUnit.imagesTaken = 0;
        }

        for (var c = 0; c < config.parentUnitList.length; c++)
            searchArray = searchArray[config.parentUnitList[c]].children;

        if (debug) console.log ("children: " + searchArray[index].children.length);
        for (var c = 0; c < searchArray[index].children.length; c++)
            if (searchArray[index].children[c].selected) {
                if (debug) console.log ("delete index ", index, " - ", c);
                delete searchArray[index].children[c].selected;
            }
        //children[index].selected = 1;
        while (GeoList.length) GeoList.pop();
        GeoList.push (searchArray);
    };

    return viewModel;
}

module.exports = GeoUnits;