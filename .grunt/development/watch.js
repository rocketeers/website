module.exports = {
	options: {
		livereload : true,
		interrupt  : true,
	},

	grunt: {
		files: ['Gruntfile.js', '<%= grunt %>/**/*'],
		tasks: 'default',
	},
	md: {
		files: ['index.template.html', 'docs/**/*.md', 'vendor/anahkiasen/rocketeer/README.md'],
		tasks: 'md',
	},
	img: {
		files: '<%= paths.original.img %>/**/*',
		tasks: 'copy',
	},
	js: {
		files: ['<%= files.ts %>', '<%= paths.original.templates %>/**/*'],
		tasks: 'js',
	},
	css: {
		files: '<%= files.sass %>',
		tasks: 'css',
	},
};