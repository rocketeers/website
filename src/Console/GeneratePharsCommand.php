<?php

namespace Rocketeer\Website\Console;

use Illuminate\Console\Command;
use Rocketeer\Website\Services\PharGenerator;
use Symfony\Component\Console\Input\InputOption;

class GeneratePharsCommand extends Command
{
    /**
     * @var string
     */
    protected $name = 'phars';

    /**
     * @var string
     */
    protected $description = 'Generate the Rocketeer PHARs';

    /**
     * Where the Rocketeer files are.
     *
     * @var string
     */
    protected $sources;

    /**
     * Where the generated PHARs are.
     *
     * @var string
     */
    protected $destination;

    /**
     * Setup the command.
     */
    public function __construct()
    {
        parent::__construct();

        $this->destination = realpath(__DIR__.'/../../public/versions');
        $this->sources = [
            'rocketeer' => realpath(__DIR__.'/../../docs/rocketeer'),
            // 'satellite' => realpath(__DIR__.'/../../docs/satellite'),
        ];
    }

    /**
     * Execute the command.
     */
    public function fire()
    {
        foreach ($this->sources as $name => $source) {
            $generator = new PharGenerator($name, $source, $this->destination);
            $generator->setOutput($this->output);
            $generator->setForce($this->option('force'));
            $generator->generatePhars();
        }
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return [
            ['force', 'F', InputOption::VALUE_NONE, 'Force the recompilation of all PHARs'],
        ];
    }
}
