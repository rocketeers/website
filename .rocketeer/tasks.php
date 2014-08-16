<?php
use Rocketeer\Facades\Rocketeer;

Rocketeer::task('grunt', 'node_modules/.bin/grunt production --force', 'Build the assets and archives');

Rocketeer::listenTo('deploy.before-symlink', 'grunt');
