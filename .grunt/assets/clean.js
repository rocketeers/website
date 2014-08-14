module.exports = {
	front: [
		'node_modules/grunt-newer/.cache',
		'<%= builds %>',
	],
	local: [
		'<%= paths.original.fonts %>',
		'<%= paths.original.css %>',
	],
};

