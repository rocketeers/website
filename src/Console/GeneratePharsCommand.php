<?php

namespace Rocketeer\Website\Console;

use Rocketeer\Website\Services\PharGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GeneratePharsCommand extends Command
{
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
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('phars')
             ->setDescription('Generate the Rocketeer PHARs')
             ->addOption('force', 'F', InputOption::VALUE_NONE, 'Force the recompilation of all PHARs');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->sources as $name => $source) {
            $generator = new PharGenerator($name, $source, $this->destination);
            $generator->setOutput($output);
            $generator->setForce($input->getOption('force'));
            $generator->generatePhars();
        }
    }
}
