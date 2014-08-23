module.exports = {
	options: {
		template: '.grunt/markdown.html',

	},

	dist: {
		files: [
			{
				flatten: true,
				expand: true,
				src   : 'docs/docs/**/*.md',
				dest  : '<%= paths.original.templates %>',
				ext   : '.html',
			}
		],
	}
};
