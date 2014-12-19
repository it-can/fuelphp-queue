// # Gruntfile for easy task management.
//
// ### _Automize common tasks._
//
// # Available tasks
//
// ## Less compiling.
//
// Compiles less files to css files. There are 3 commands available:
//
// 1. "grunt less:development" Compiles without version. This requires that writeVersion has already been run before.
// 2. "grunt less:production" Compiles with version.
// 3. "grunt buildLess" First write version.txt then less compiling.
// 
//
// ## Sync files to your dev server.
//
// Dev servers are more powerfull so running for example
// unittest on dev servers is by far faster then on your
// local.
//
// Create an .syncrc file containing the path to your dev server.
// Example: /home/burhan/.gvfs/SFTP for deploy on 192.168.1.45/var/www/direct-result/public/app/
//
// Typical usage:
//
// 1. "grunt" on your local (runs the default concurrent:watchLocal task).
// 2. "grunt watchQuality" on your dev server.
//
// ___
//
// **Author:** AB Zainuddin
//
// **Email:** burhan@codeyellow.nl
//
// **Website:** http://www.codeyellow.nl
//
// **License:** Copyright 2013 Code Yellow B.V.
//
// **Date:** 2013/12/07
// ___
module.exports = function (grunt) {
    'use strict';

    grunt.initConfig({
        exec: {
            unitTest: {
                command: function (type) {
                    return 'php vendor/phpunit/phpunit/phpunit.php --group=queue tests/*';
                },
            },
            phpcs: {
                command: function () {
                    return 'vendor/bin/phpcs src --ignore=Overview/* --standard=psr1,psr2';
                },
            },
            phpmd: {
                command: function(type) {
                    return "php vendor/phpmd/phpmd/src/bin/phpmd src text codesize,unusedcode,naming,design,controversial --exclude=fuel/vendor/CodeYellow/Queue/Overview/";
                }
            },
            phpdoc: {
                command: function(type) {
                    return "php vendor/phpdocumentor/phpdocumentor/bin/phpdoc.php -t docs -d src"
                }
            }
        }

    });


    grunt.loadNpmTasks('grunt-exec');

    grunt.registerTask('release',['exec:phpcs', 'exec:phpmd', 'exec:phpdoc']);
};