module.exports = {
	options: {
		blockReplacements: {
			css: function (block) {
				return '<link rel="stylesheet" href="public/' +block.dest+ '">';
			},
			js: function (block) {
				return '<script src="public/' +block.dest+ '"></script>';
			},
		},
	},

	html: 'index.html',
};
