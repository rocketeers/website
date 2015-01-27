module.exports = {
	options: {
		template: '.grunt/markdown.html',

	},

	dist: {
		files: [
			{
				expand: true,
				src   : ['docs/**/*.md', '!docs/rocketeer/vendor/**/*'],
				dest  : '<%= paths.original.templates %>',
				ext   : '.html',
			}
		],
	}
};
