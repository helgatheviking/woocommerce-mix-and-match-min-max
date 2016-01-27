/*global module:false*/
/*global require:false*/
/*jshint -W097*/
"use strict";

module.exports = function(grunt) {
 
    // load all grunt tasks
    require('matchdep').filterDev('grunt-*').forEach(grunt.loadNpmTasks);
 
    grunt.initConfig({

        pkg: grunt.file.readJSON('package.json'),
 
        // watch for changes and run js
        watch: {
            sass: {
                files: [
                    'js/add-to-cart-mnm-mix-max.js'
                ],
                tasks: ['uglify']
            }
        },

        // javascript linting with jshint
        jshint: {
            options: {
                jshintrc: '.jshintrc',
                force: true
            },
            all: [
                'Gruntfile.js'
            ]
        },    

        // uglify to concat, minify, and make source maps
        uglify: {
            options: {
                compress: {
                    global_defs: {
                        "EO_SCRIPT_DEBUG": false
                    },
                    dead_code: true
                    },
                banner: '/*! <%= pkg.title %> <%= pkg.version %> <%= grunt.template.today("yyyy-mm-dd HH:MM") %> */\n'
            },
            build: {
                files: [{
                    expand: true,   // Enable dynamic expansion.
                    src: ['js/*.js', '!js/*.min.js'], // Actual pattern(s) to match.
                    ext: '.min.js',   // Dest filepaths will have this extension.
                }]
            }
        }, 

        // Add text domain
        addtextdomain: {
            options: {
                textdomain: '<%= pkg.name %>',    // Project text domain.
            },
            target: {
                files: {
                    src: ['*.php', '**/*.php', '**/**/*.php', '!node_modules/**', '!build/**']
                }
            }
        },

        // Generate .pot file
        makepot: {
            target: {
                options: {
                    domainPath: '/languages', // Where to save the POT file.
                    exclude: ['build'], // List of files or directories to ignore.
                    mainFile: '<%= pkg.name %>.php', // Main project file.
                    potFilename: '<%= pkg.name %>.pot', // Name of the POT file.
                        potHeaders: {
                            poedit: true,                 // Includes common Poedit headers.
                            'x-poedit-keywordslist': true // Include a list of all possible gettext functions.
                        },
                        type: 'wp-plugin',    // Type of project (wp-plugin or wp-theme).
                        updateTimestamp: true,    // Whether the POT-Creation-Date should be updated without other changes.
                        processPot: function( pot, options ) {
                            pot.headers['report-msgid-bugs-to'] = 'https://github.com/helgatheviking/woocommerce-mix-and-match-min-max/';
                            pot.headers['last-translator'] = 'WP-Translations (http://wp-translations.org/)';
                            pot.headers['language-team'] = 'WP-Translations <wpt@wp-translations.org>';
                            pot.headers['language'] = 'en_US';
                            var translation, // Exclude meta data from pot.
                                excluded_meta = [
                                    'Plugin Name of the plugin/theme',
                                    'Plugin URI of the plugin/theme',
                                    'Author of the plugin/theme',
                                    'Author URI of the plugin/theme'
                                ];

                            for ( translation in pot.translations[''] ) {
                                if ( 'undefined' !== typeof pot.translations[''][ translation ].comments.extracted ) {
                                    if ( excluded_meta.indexOf( pot.translations[''][ translation ].comments.extracted ) >= 0 ) {
                                        console.log( 'Excluded meta: ' + pot.translations[''][ translation ].comments.extracted );
                                            delete pot.translations[''][ translation ];
                                    }
                                }
                            }

                            return pot;
                        }
                }
            }
        },

        // bump version numbers (replace with version in package.json)
        replace: {
            Version: {
                src: [
                    'readme.txt',
                    'readme.md',
                    '<%= pkg.name %>.php'
                ],
                overwrite: true,
                replacements: [
                    {
                        from: /Stable tag:.*$/m,
                        to: "Stable tag: <%= pkg.version %>"
                    },
                    { 
                        from: /Version:.*$/m,
                        to: "Version: <%= pkg.version %>"
                    },
                    { 
                        from: /public \$version = \'.*.'/m,
                        to: "public $version = '<%= pkg.version %>'"
                    }
                ]
            }
        },

        // Clean up build directory
        clean: {
            main: ['build/<%= pkg.name %>']
        },

        // Copy the theme into the build directory
        copy: {
            main: {
                src:  [
                    '**',
                    '!bin/**',
                    '!composer.json',
                    '!composer.lock', 
                    '!phpunit.xml',
                    '!node_modules/**',
                    '!build/**',
                    '!.git/**',
                    '!Gruntfile.js',
                    '!package.json',
                    '!.gitignore',
                    '!tests/**',
                    '!**/Gruntfile.js',
                    '!**/package.json',
                    '!**/README.md',
                    '!**/*~', 
                    '!assets/css/scss/**',
                    '!assets/css/*.map'
                ],
                dest: 'build/<%= pkg.name %>/'
            }
        },

        // Compress build directory into <name>.zip and <name>-<version>.zip
        compress: {
            main: {
                options: {
                    mode: 'zip',
                    archive: './build/<%= pkg.name %>-<%= pkg.version %>.zip'
                },
                expand: true,
                cwd: 'build/<%= pkg.name %>/',
                src: ['**/*'],
                dest: '<%= pkg.name %>/'
            }
        },

    });

    // Default task. - grunt watch
    grunt.registerTask( 'default', 'watch' );

    // Build task(s).
    grunt.registerTask( 'build', [ 'makepot',  'clean', 'copy', 'compress' ] );
};