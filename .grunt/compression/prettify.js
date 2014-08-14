module.exports = {
	options: {
		indent          : 2,
		condense        : false,
		indent_char     : '	',
		wrap_line_length: 78,
		brace_style     : 'expand',
		unformatted     : ['strong', 'em', 'a', 'code', 'pre']
	},

	dist: {
		files: [{
			expand : true,
			cwd    : '<%= app %>/md',
			src    : '**/*.html',
			dest   : '<%= app %>/md',
			ext    : '.html'
		}]
	}
};
