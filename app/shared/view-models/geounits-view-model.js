var config = require("../../shared/config");
var observableModule = require("data/observable");
var ObservableArray = require("data/observable-array").ObservableArray;
var GeoUnitsArray = require("../../shared/geounits");
var GeoLocation = require("nativescript-geolocation");

var GeoList = new ObservableArray(GeoUnitsArray);

function GeoUnits () {
    var viewModel = new observableModule.fromObject({GeoList: GeoList});
    viewModel.parentGeoUnits = [];

    viewModel.tap = function(args){
        var selectedGeoUnit = {
            locationId: args.view.bindingContext.id,
            sirutaCode: args.view.bindingContext.cs,
            locLatitude: args.view.bindingContext.lat,
            locLongitude: args.view.bindingContext.lng,
            locName: args.view.bindingContext.name
            };
        config.selectedGeoUnit = selectedGeoUnit;

        var searchArray = GeoUnitsArray;
        for (var c = 0; c < this.parentGeoUnits.length; c++)
            searchArray = searchArray[c].children;
        for (var c = 0; c < searchArray.length; c++)
            if (searchArray[c].selected)
                delete (searchArray[c].selected);

        args.view.bindingContext.selected = 1;

        if (args.view.bindingContext.children.length == 0) {
            throw true;
        }

        var children = args.view.bindingContext.children;

        this.parentGeoUnits.push(args.index);
        console.log(JSON.stringify(this.parentGeoUnits));
        while (GeoList.length) GeoList.pop();
        //
        GeoList.push(children);
    };

    viewModel.search = function(args, str){
    
    };

    viewModel.locate = function(args){
        GeoLocation.getCurrentLocation({desiredAccuracy:3,maximumAge:2000,timeout:20000}).then(function(location){
            while (viewModel.parentGeoUnits.length) viewModel.parentGeoUnits.pop();

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
                    viewModel.parentGeoUnits.push(min_index);
                    searchArray[min_index].selected = 1;
                }
                searchArray = searchArray[min_index].children;
            }
            while (searchArray.length > 0);

            searchArray = GeoUnitsArray;
            for (c = 0; c < viewModel.parentGeoUnits.length; c++)
                searchArray = searchArray[viewModel.parentGeoUnits[c]].children;

            console.log(JSON.stringify(viewModel.parentGeoUnits));
            console.log("min_index - ", min_index);
            searchArray[min_index].selected = 1;

            while (GeoList.length) GeoList.pop();
            GeoList.push (searchArray);
        })
        .catch(function(err){
            console.log("Error: " + err.message);
        });
    };
    
    viewModel.back = function(args){
        if (this.parentGeoUnits.length == 0)
            throw true;
        console.log(JSON.stringify(this.parentGeoUnits));
        var index = this.parentGeoUnits.pop();
        console.log(JSON.stringify(this.parentGeoUnits));
        var searchArray = GeoUnitsArray;
        for (var c = 0; c < this.parentGeoUnits.length; c++)
            searchArray = searchArray[this.parentGeoUnits[c]].children;
        for (var c = 0; c < searchArray[index].children.length; c++)
            if (searchArray[index].children[c].selected) {
                console.log ("delete index ", index, " - ", c);
                delete searchArray[index].children[c].selected;
            }
        //children[index].selected = 1;
        while (GeoList.length) GeoList.pop();
        GeoList.push (searchArray);
    };

    return viewModel;
}

module.exports = GeoUnits;