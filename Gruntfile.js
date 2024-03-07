module.exports = function(grunt) {
	require('dotenv').config();
	const DEBUG = parseInt(process.env.DEBUG) || false;
	var fs = require('fs'),
		path = require('path'),
		chalk = require('chalk'),
		PACK = grunt.file.readJSON('package.json'),
		uniqid = function () {
			if(DEBUG){
				var md5 = require('md5');
				result = md5((new Date()).getTime()).toString();
				grunt.verbose.writeln("Generate hash: " + chalk.cyan(result) + " >>> OK");
				return result;
			}
			return `v${PACK.version}`;
		};
	
	String.prototype.hashCode = function() {
		var hash = 0, i, chr;
		if (this.length === 0) return hash;
		for (i = 0; i < this.length; i++) {
			chr   = this.charCodeAt(i);
			hash  = ((hash << 5) - hash) + chr;
			hash |= 0; // Convert to 32bit integer
		}
		return hash;
	};
	
	var gc = {
		assets: "",
		gosave: "",
		default: [
			"clean:all",
			"concat",
			"uglify",
			"less",
			"autoprefixer",
			"group_css_media_queries",
			"cssmin",
		]
	};
	require('load-grunt-tasks')(grunt);
	require('time-grunt')(grunt);
	grunt.initConfig({
		globalConfig : gc,
		pkg : grunt.file.readJSON('package.json'),
		clean: {
			options: {
				force: true
			},
			all: [
				'test/',
				'tests/'
			]
		},
		concat: {
			options: {
				separator: "\n",
			},
			appjs: {
				src: [
					"bower_components/fancybox/src/js/core.js",
					// обработка ссылок на видео
					// YouTube, RUTUBE, Viemo
					'media.js',
					"bower_components/fancybox/src/js/guestures.js",
					"bower_components/fancybox/src/js/slideshow.js",
					"bower_components/fancybox/src/js/fullscreen.js",
					"bower_components/fancybox/src/js/thumbs.js",
					"bower_components/fancybox/src/js/hash.js",
					"bower_components/fancybox/src/js/wheel.js",
				],
				dest: 'test/js/jquery.fancybox.js'
			},
			main: {
				src: [
					'main.js'
				],
				dest: 'test/js/main.js'
			}
		},
		uglify: {
			options: {
				sourceMap: false,
				compress: {
					drop_console: false
	  			},
	  			output: {
					ascii_only: true
				}
			},
			app: {
				files: [
					{
						expand: true,
						flatten : true,
						src: [
							'test/js/jquery.fancybox.js',
							'test/js/main.js'
						],
						dest: __dirname,
						filter: 'isFile',
						rename: function (dst, src) {
							return path.join(dst, src.replace('.js', '.min.js'));
						}
					}
				]
			}
		},
		less: {
			css: {
				options : {
					compress: false,
					ieCompat: false,
				},
				files : {
					'test/css/main.css' : [
						'main.less'
					]
				}
			}
		},
		autoprefixer:{
			options: {
				browsers: [
					"last 5 version"
				],
				cascade: true
			},
			css: {
				files: {
					'test/css/prefix.main.css' : [
						'test/css/main.css'
					]
				}
			}
		},
		group_css_media_queries: {
			group: {
				files: {
					'main.css': ['test/css/prefix.main.css']
				}
			}
		},
		cssmin: {
			options: {
				mergeIntoShorthands: false,
				roundingPrecision: -1
			},
			minify: {
				files: {
					'main.min.css' : ['main.css']
				}
			}
		}
	});
	grunt.registerTask('default',	gc.default);
};