module.exports = function(grunt) {

	////////////////////////////////////////////////////////////////////
	/////////////////////////////// COMMANDS ///////////////////////////
	////////////////////////////////////////////////////////////////////

	grunt.registerTask('default', 'Build assets for local', [
		'css',
		'js',
		'md',
		'copy',
	]);

	grunt.registerTask('rebuild', 'Rebuild all assets from scratch', [
		'clean',
		'compass:clean',
		'default',
	]);

	grunt.registerTask('production', 'Build assets for production', [
		'tsd',
		'clean',
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

	grunt.registerTask('images', 'Recompress images', [
		'newer:svgmin',
		'newer:tinypng',
	]);

	grunt.registerTask('lint', 'Lint the files', [
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
		'typescript',
	]);

	grunt.registerTask('css', 'Build stylesheets', [
		'newer:compass:compile',
		'newer:autoprefixer',
	]);

}
