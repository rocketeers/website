module.exports = {

	// Flows
	//////////////////////////////////////////////////////////////////////

	// Cleans up compiled files
	clean: ['clean:local', 'clean:front', 'compass:clean'],

	// Build the assets
	build: ['css', 'js', 'md', 'copy'],

	// Compression
	//////////////////////////////////////////////////////////////////////

	// Compresses images
	images: ['newer:svgmin', 'newer:tinypng'],

};
