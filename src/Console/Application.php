<?php

namespace Rocketeer\Website\Console;

class Application extends \Symfony\Component\Console\Application
{
    /**
     * Setup the application.
     */
    public function __construct()
    {
        parent::__construct('Rocketeer website');

        $this->addCommands([
            new GeneratePharsCommand(),
        ]);
    }
}
