<?php
require __DIR__.'/../vendor/autoload.php';

use Rocketeer\Facades\Rocketeer;
use Rocketeer\Website\Deployment\RocketeerPhar;
use Rocketeer\Website\Deployment\SatellitePhar;

Rocketeer::configure('dependencies', ['shared_dependencies' => true]);

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
	'phpdoc',
));
