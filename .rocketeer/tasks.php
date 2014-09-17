<?php
use Rocketeer\Facades\Rocketeer;

// Tasks
//////////////////////////////////////////////////////////////////////

Rocketeer::task(
	'grunt',
	'node_modules/.bin/grunt production --force',
	'Build the assets and archives'
);

Rocketeer::task(
	'phar',
	array(
		'cd docs/rocketeer',
		'composer install',
		'php bin/compile',
		'mv bin/rocketeer.phar ../../public/versions/rocketeer.phar',
	),
	'Generates the final PHAR'
);

Rocketeer::task(
	'phpdoc',
	'vendor/bin/phpdoc -t public/api -d docs/rocketeer/src',
	'Generates the API documentation'
);

// Events
//////////////////////////////////////////////////////////////////////

Rocketeer::listenTo('deploy.before-symlink', array(
	'grunt',
	'phar',
	'phpdoc',
));
