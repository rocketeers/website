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
		'useminPrepare',
		'clean',
		'md',
		'copy',
		'concat',
		'minify',
		'usemin',
		'shell:phar',
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
		'scsslint',
		'csslint',
		'csscss',
	]);

	// By filetype
	////////////////////////////////////////////////////////////////////

	grunt.registerTask('md', 'Build contents', [
		'newer:concat:md',
		'markdown',
		'newer:prettify',
	]);

	grunt.registerTask('js', 'Build scripts', [
	]);

	grunt.registerTask('css', 'Build stylesheets', [
		'newer:compass:compile',
		'newer:autoprefixer',
	]);

}
