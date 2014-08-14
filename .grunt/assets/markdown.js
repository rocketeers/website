module.exports = {
	options: {
		template: '.grunt/markdown.html',

	},

	dist: {
		files: [
			{
				expand: true,
				src   : 'wiki/**/*.md',
				dest  : '<%= paths.original.templates %>',
				ext   : '.html',
			},
			{
				flatten: true,
				expand: true,
				src   : 'vendor/anahkiasen/rocketeer/**/*.md',
				dest  : '<%= paths.original.templates %>',
				ext   : '.html',
			}
		],
	}
};
