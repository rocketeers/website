module.exports = {
	md: {
		files: {
			'contents.md': [
				'rocketeer/README.md',
				'wiki/I-Introduction/Whats-Rocketeer.md',
				'wiki/I-Introduction/Getting-started.md',
				'wiki/II-Concepts/Tasks.md',
				'wiki/II-Concepts/Connections-Stages.md',
				'wiki/II-Concepts/Events.md',
				'wiki/II-Concepts/Plugins.md',
				'rocketeer/CHANGELOG.md',
				'wiki/III-Further/Troubleshooting.md',
			],
		},
		options: {
			separator: "\n\n[/section]\n[section]\n\n"
		},
	},
	css: {
		files: {
			'<%= paths.compiled.css %>/styles.css': [
				'<%= paths.components.bootstrap.css %>',
				'<%= components %>/prism/themes/prism-okaidia.css',
				'<%= paths.original.css %>/*'
			],
		},
	},
	js: {
		files: {
			'<%= paths.compiled.js %>/scripts.js': [
				'<%= paths.components.jquery %>',
				'<%= components %>/prism/prism.js',
				'<%= components %>/prism/components/prism-php.js',
				'<%= components %>/smooth-scroller/dist/smooth-scroller.bower.js',
				'<%= components %>/toc/dist/toc.bower.js',

				'<%= paths.original.js %>/**/*.js',
			],
		},
	}
};