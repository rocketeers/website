<?php
use Rocketeer\Facades\Rocketeer;
use Rocketeer\Website\Deployment\RocketeerPhar;
use Rocketeer\Website\Deployment\SatellitePhar;

// Tasks
//////////////////////////////////////////////////////////////////////

Rocketeer::task(
	'grunt',
	'node_modules/.bin/grunt production --force',
	'Build the assets and archives'
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
	RocketeerPhar::class,
	SatellitePhar::class,
	'phpdoc',
));
