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
			'php bin/compile',
			'mv bin/rocketeer.phar ../../../output/rocketeer.phar',
		].join('&&'),
	},

	api: {
		command: [
			'vendor/bin/phpdoc -t api -d vendor/anahkiasen/rocketeer/src',
		].join('&&'),
	}
};
