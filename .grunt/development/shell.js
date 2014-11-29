module.exports = {
	phar: {
		command: [
			// Generate Rocketeer PHAR
			'cd docs/rocketeer',
			'composer install',
			'php bin/compile',
			'mv bin/rocketeer.phar ../../public/versions/rocketeer.phar',

			// Generate Satellite PHAR
			'cd docs/satellite',
			'composer install',
			'php bin/compile',
			'mv bin/satellite.phar ../../public/versions/satellite.phar',
		].join('&&'),
	},

	api: {
		command: [
			'vendor/bin/phpdoc -t public/api -d docs/rocketeer/src',
		].join('&&'),
	}
};

