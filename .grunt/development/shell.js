module.exports = {
	phar: {
		command: [
			'cd docs/rocketeer',
			'composer install',
			'php bin/compile',
			'mv bin/rocketeer.phar ../../../public/versions/rocketeer.phar',
		].join('&&'),
	},

	api: {
		command: [
			'vendor/bin/phpdoc -t public/api -d docs/rocketeer/src',
		].join('&&'),
	}
};

