<?php

namespace Rocketeer\Website\Deployment;

use Rocketeer\Abstracts\AbstractTask;

abstract class AbstractPharGenerationTask extends AbstractTask
{
    /**
     * The name of the repository.
     *
     * @var string
     */
    protected $repository;

    /**
     * @var string
     */
    protected $name = 'PharGeneration';

    /**
     * @var string
     */
    protected $description = 'Generates the PHAR of a repository';

    /**
     * Run the task.
     *
     * @return string
     */
    public function execute()
    {
        $phar = $this->repository.'.phar';

        return $this->runForCurrentRelease([
            'cd docs/'.$this->repository,
            'composer install',
            'php bin/compile',
            sprintf('mv bin/%s ../../public/versions/%s', $phar, $phar),
        ]);
    }
}
