module.exports = function(grunt) {
	require('dotenv').config();
	const DEBUG = parseInt(process.env.DEBUG) || false;
	var fs = require('fs'),
		path = require('path');
	
	var gc = {
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
			},
			css: {
				src: [
					'bower_components/fancybox/src/css/*.css'
				],
				dest: 'test/css/jquery.fancybox.css'
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
							return path.normalize(path.join(dst, src.replace('.js', '.min.js')));
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
					],
					'test/css/prefix.jquery.fancybox.css' : [
						'test/css/jquery.fancybox.css'
					],
				}
			}
		},
		group_css_media_queries: {
			group: {
				files: {
					'main.css': ['test/css/prefix.main.css'],
					'jquery.fancybox.css': ['test/css/prefix.jquery.fancybox.css']
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
					'main.min.css' : ['main.css'],
					'jquery.fancybox.min.css' : ['jquery.fancybox.css']
				}
			}
		}
	});
	grunt.registerTask('default',	gc.default);
};