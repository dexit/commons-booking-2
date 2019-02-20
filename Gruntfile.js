'use strict';
module.exports = function (grunt) {
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    compass: {
      admin: {
        options: {
          sassDir: 'admin/assets/sass',
          cssDir: 'admin/assets/css',
          environment: 'production',
          relativeAssets: true
        }
      },
      public: {
        options: {
          sassDir: 'public/assets/sass',
          cssDir: 'public/assets/css',
          environment: 'production',
          relativeAssets: true
        }
      },
      framework: {
        options: {
          sassDir: 'framework/assets/sass',
          cssDir: 'framework/assets/css',
          environment: 'production',
          relativeAssets: true
        }
      },
      adminDev: {
        options: {
          environment: 'development',
          debugInfo: true,
          noLineComments: false,
          sassDir: 'admin/assets/sass',
          cssDir: 'admin/assets/css',
          outputStyle: 'expanded',
          relativeAssets: true,
          sourcemap: true
        }
      },
      publicDev: {
        options: {
          environment: 'development',
          debugInfo: true,
          noLineComments: false,
          sassDir: 'public/assets/sass',
          cssDir: 'public/assets/css',
          outputStyle: 'expanded',
          relativeAssets: true,
          sourcemap: true
        }
      },
      frameworkDev: {
        options: {
          environment: 'development',
          debugInfo: true,
          noLineComments: false,
          sassDir: 'framework/assets/sass',
          cssDir: 'framework/assets/css',
          outputStyle: 'expanded',
          relativeAssets: true,
          sourcemap: true
        }
      }
    },
    // check our JS
    jshint: {
      options: {
        "bitwise": true,
        "browser": true,
        "curly": true,
        "eqeqeq": true,
        "eqnull": true,
        "esnext": true,
        "immed": true,
        "jquery": true,
        "latedef": true,
        "newcap": true,
        "noarg": true,
        "node": true,
        "strict": false,
        "trailing": true,
        "undef": true,
        "globals": {
          "jQuery": true,
          "alert": true,
          "cb2" : true,
          "tippy" : true
        }
      },
      all: [
        'gruntfile.js',
        'public/assets/js/*.js',
        '!public/assets/js/*.min.js'
      ]

    },

    // concat and minify our JS
    uglify: {
      dev: {
        options: {
          beautify: true,
          mangle:false
        },
        files: {
          'public/assets/js/public.min.js': [
            /* add path to js dependencies (ie in node_modules) here */
            'node_modules/tippy.js/dist/tippy.all.js',
            'public/assets/js/public.js'
          ]
        }
      },
      dist: {
        files: {
          'public/assets/js/public.min.js': [
            /* add path to js dependencies (ie in node_modules) here */
            'node_modules/tippy.js/dist/tippy.all.js',
            'public/assets/js/public.js'
          ]
        }
      }
    },
    watch: {
      compass: {
        files: [
          'admin/assets/sass/**/*.scss',
          'public/assets/sass/**/*.scss',
          'framework/assets/sass/**/*.scss',
        ],
        tasks: [
					'compass:adminDev', 'compass:publicDev', 'compass:frameworkDev'
        ]
      },
      js: {
        files: [
          '<%= jshint.all %>'
        ],
        tasks: [
          'uglify:dev'
        ]
      }
    }
  });
  // Load tasks
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-contrib-compass');
  grunt.loadNpmTasks('grunt-contrib-jshint');
  grunt.loadNpmTasks('grunt-contrib-uglify');
  // Register tasks
  grunt.registerTask('default', [
    'compass:admin', 'compass:public',
  ]);
  grunt.registerTask('dev', [
    'watch'
  ]);
  grunt.registerTask('dist', [
    'compass:admin',
    'compass:public',
    'compass:framework',
    'jshint',
    'uglify:dist'
  ]);
};
