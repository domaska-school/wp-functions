var fs = require('fs');
var UglifyJS = require("uglify-js");

fs.writeFileSync('main.min.js', UglifyJS.minify({
	"main.js": fs.readFileSync("main.js", "utf8")
}, {
	output: {
		ascii_only: true
	}
}).code, "utf8");