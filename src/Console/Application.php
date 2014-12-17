<?php
namespace Rocketeer\Website\Console;

use Illuminate\Container\Container;
use Rocketeer\Website\Application as RocketeerWebsite;

class Application extends \Illuminate\Console\Application
{
	/**
	 * Setup the application
	 */
	public function __construct()
	{
		parent::__construct('Rocketeer website');

		// Register services
		$this->laravel = new RocketeerWebsite();

		$this->resolveCommands(array(
			'Rocketeer\Website\Console\GeneratePharsCommand',
		));
	}
}
