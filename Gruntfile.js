/* jshint node:true */
/* global module */
module.exports = function( grunt ) {
	require( 'matchdep' ).filterDev( ['grunt-*', '!grunt-legacy-util'] ).forEach( grunt.loadNpmTasks );
	grunt.util = require( 'grunt-legacy-util' );

	grunt.initConfig( {
		pkg: grunt.file.readJSON( 'package.json' ),
		jshint: {
			options: grunt.file.readJSON( '.jshintrc' ),
			grunt: {
				src: ['Gruntfile.js']
			},
			all: ['Gruntfile.js', 'js/*.js']
		},
		checktextdomain: {
			options: {
				correct_domain: false,
				text_domain: ['entrepot','default'],
				keywords: [
					'__:1,2d',
					'_e:1,2d',
					'_x:1,2c,3d',
					'_n:1,2,4d',
					'_ex:1,2c,3d',
					'_nx:1,2,4c,5d',
					'esc_attr__:1,2d',
					'esc_attr_e:1,2d',
					'esc_attr_x:1,2c,3d',
					'esc_html__:1,2d',
					'esc_html_e:1,2d',
					'esc_html_x:1,2c,3d',
					'_n_noop:1,2,3d',
					'_nx_noop:1,2,3c,4d'
				]
			},
			files: {
				src: ['**/*.php', '!**/node_modules/**'],
				expand: true
			}
		},
		clean: {
			all: ['assets/*.min.css', 'js/*.min.js', 'assets/entrepot.min.json'],
			entrepot: 'assets/entrepot.min.json'
		},
		makepot: {
			target: {
				options: {
					domainPath: '/languages',
					exclude: ['/node_modules'],
					mainFile: 'entrepot.php',
					potFilename: 'entrepot.pot',
					processPot: function( pot ) {
						pot.headers['last-translator']      = 'imath <contact@imathi.eu>';
						pot.headers['language-team']        = 'FRENCH <contact@imathi.eu>';
						pot.headers['report-msgid-bugs-to'] = 'https://github.com/imath/entrepot/issues';
						return pot;
					},
					type: 'wp-plugin'
				}
			}
		},
		uglify: {
			minify: {
				extDot: 'last',
				expand: true,
				ext: '.min.js',
				src: ['js/*.js', '!*.min.js']
			},
			options: {
				banner: '/*! <%= pkg.name %> - v<%= pkg.version %> - ' +
				'<%= grunt.template.today("UTC:yyyy-mm-dd h:MM:ss TT Z") %> - ' +
				'https://imathi.eu/tag/entrepot */\n'
			}
		},
		cssmin: {
			minify: {
				extDot: 'last',
				expand: true,
				ext: '.min.css',
				src: ['assets/*.css', '!*.min.css'],
				options: {
					banner: '/*! <%= pkg.name %> - v<%= pkg.version %> - ' +
					'<%= grunt.template.today("UTC:yyyy-mm-dd h:MM:ss TT Z") %> - ' +
					'https://imathi.eu/tag/entrepot */'
				}
			}
		},
		jsvalidate:{
			src: ['js/*.js'],
			options:{
				globals: {},
				esprimaOptions:{},
				verbose: false
			}
		},
		phpunit: {
			'default': {
				cmd: 'phpunit',
				args: ['-c', 'phpunit.xml.dist']
			},
			'multisite': {
				cmd: 'phpunit',
				args: ['-c', 'tests/phpunit/multisite.xml']
			}
		},
		minjson: {
			compile: {
				files: {
					'assets/entrepot.min.json': 'repositories/*.json'
				}
			}
		},
		compress: {
			main: {
				options: {
					archive: '<%= pkg.name %>.zip'
				},
				files: [{
					expand: true,
					src: [
						'**/*',
						'!node_modules/**',
						'!npm-debug.log',
						'!tests/**',
						'!.editorconfig',
						'!.git/**',
						'!.gitignore',
						'!.gitattributes',
						'!grunt/**',
						'!.jshintrc',
						'!.jshintignore',
						'!.travis.yml',
						'!Gruntfile.js',
						'!package.json',
						'!phpunit.xml.dist',
						'!CONTRIBUTING.md',
						'!CODE_OF_CONDUCT.md',
						'!icon.png',
						'!repositories/**',
						'!suspended/**'
					],
					dest: './'
				}]
			}
		}
	} );

	/**
	 * Register tasks.
	 */
	grunt.registerMultiTask( 'phpunit', 'Runs PHPUnit tests, including the multisite tests.', function() {
		grunt.util.spawn( {
			args: this.data.args,
			cmd:  this.data.cmd,
			opts: { stdio: 'inherit' }
		}, this.async() );
	} );

	grunt.registerTask( 'test', ['clean:entrepot', 'minjson', 'phpunit'] );

	grunt.registerTask( 'jstest', ['jsvalidate', 'jshint'] );

	grunt.registerTask( 'shrink', ['cssmin', 'uglify', 'minjson'] );

	grunt.registerTask( 'release', ['checktextdomain', 'makepot', 'clean', 'jstest', 'shrink', 'compress'] );

	// Travis CI Tasks.
	grunt.registerTask( 'travis:build', ['jstest', 'checktextdomain', 'clean:entrepot', 'minjson', 'phpunit'] );

	// Default task.
	grunt.registerTask( 'default', ['commit'] );
};
