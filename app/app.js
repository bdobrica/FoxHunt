var applicationModule = require("application");
var foxHuntTimer = require ("./shared/timer.js");

var fht = new foxHuntTimer ();
fht.start ();

applicationModule.start({ moduleName: "views/login/login" });
