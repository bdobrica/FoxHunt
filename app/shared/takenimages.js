var couchbaseModule = require ("nativescript-couchbase");
var takenImagesDB = new couchbaseModule.Couchbase("fox-hunt-taken-images");

takenImagesDB.createView("takenimages", "3", function (document, emitter){
    emitter.emit(document._id, {_id: document._id, id: document.id, p: document.p});
});

module.exports = takenImagesDB;