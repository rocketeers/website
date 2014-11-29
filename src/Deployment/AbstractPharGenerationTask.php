<?php
namespace Rocketeer\Website\Deployment;

use Rocketeer\Abstracts\AbstractTask;

class AbstractPharGenerationTask extends AbstractTask
{
	/**
	 * The name of the repository
	 *
	 * @type string
	 */
	protected $repository;

	/**
	 * Run the task
	 *
	 * @return string
	 */
	public function execute()
	{
		$phar = $this->repository.'.phar';

		return $this->runForCurrentRelease(array(
			'cd docs/'.$this->repository,
			'composer install',
			'php bin/compile',
			sprintf('mv bin/%s ../../public/versions/%s', $phar, $phar),
		));
	}
}
