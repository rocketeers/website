module.exports = function(grunt) {

	////////////////////////////////////////////////////////////////////
	/////////////////////////////// COMMANDS ///////////////////////////
	////////////////////////////////////////////////////////////////////

	grunt.registerTask('default', 'Build assets for local', [
		'concurrent:build',
	]);

	grunt.registerTask('rebuild', 'Rebuild all assets from scratch', [
		'concurrent:clean',
		'concurrent:build',
	]);

	grunt.registerTask('production', 'Build assets for production', [
		'tsd',
		'rebuild',
		'default',
		'ngtemplates',
		'ngAnnotate',
		'useminPrepare',
		'concat',
		'minify',
		'usemin',
	]);

	// Flow
	////////////////////////////////////////////////////////////////////

	grunt.registerTask('minify', 'Minify assets', [
		'newer:cssmin',
		'newer:uglify',
	]);

	grunt.registerTask('lint', 'Lint the files', [
		'phplint',
		'tslint',
		'scsslint',
		'csslint',
		'csscss',
	]);

	// By filetype
	////////////////////////////////////////////////////////////////////

	grunt.registerTask('md', 'Build contents', [
		'newer:markdown',
		'newer:prettify',
		'newer:ngtemplates',
	]);

	grunt.registerTask('js', 'Build scripts', [
		'newer:typescript',
	]);

	grunt.registerTask('css', 'Build stylesheets', [
		'newer:compass:compile',
		'newer:autoprefixer',
	]);

}
