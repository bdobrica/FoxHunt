var couchbaseModule = require ("nativescript-couchbase");
var foxHuntDB = new couchbaseModule.Couchbase("fox-hunt-database");

foxHuntDB.createView("geoimages", "1", function (document, emitter){
    if (document.status == "taken")
        emitter.emit(document._id, document);
});

module.exports = foxHuntDB;