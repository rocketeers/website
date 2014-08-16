module.exports = {
	deploy: {
		command: [
			'git push',
			'php vendor/bin/rocketeer deploy --verbose'
		].join('&&')
	},

	phar: {
		command: [
			'cd vendor/anahkiasen/rocketeer',
			'composer install',
			'php bin/compile',
			'mv bin/rocketeer.phar ../../../public/versions/rocketeer.phar',
		].join('&&'),
	},

	api: {
		command: [
			'vendor/bin/phpdoc -t api -d vendor/anahkiasen/rocketeer/src',
		].join('&&'),
	}
};

