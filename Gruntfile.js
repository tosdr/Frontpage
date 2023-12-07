module.exports = function (grunt) {

  grunt.initConfig({
    sass: {
      dist: {
        files: [{
          expand: true,
          cwd: 'public/assets/css/src',
          src: ['*.scss'],
          dest: '/tmp/compile',
          ext: '.css'
        }]
      }
    },
    cssmin: {
      options: {
        level: 2,
        mergeIntoShorthands: false,
        roundingPrecision: -1
      },
      target: {
        files: {
          'public/assets/css/dist/release.css': ['/tmp/compile/*.css', 'public/assets/css/thirdparty/**/*.css']
        }
      }
    }
  });
  grunt.loadNpmTasks('grunt-contrib-cssmin');
  grunt.loadNpmTasks('grunt-contrib-sass');
  

  grunt.registerTask('default', ['sass', 'cssmin']);

};