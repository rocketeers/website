<?php
namespace Rocketeer\Website\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputOption;

class GeneratePharsCommand extends Command
{
    /**
     * @type string
     */
    protected $name = 'phars';

    /**
     * @type string
     */
    protected $description = 'Generate the Rocketeer PHARs';

    /**
     * Where the Rocketeer files are
     *
     * @type string
     */
    protected $sources;

    /**
     * The current repository being generated
     *
     * @type string
     */
    protected $current;

    /**
     * Where the generated PHARs are
     *
     * @type string
     */
    protected $phars;

    /**
     * @type ProgressBar
     */
    protected $progress;

    /**
     * Setup the command
     */
    public function __construct()
    {
        parent::__construct();

        $this->phars   = realpath(__DIR__.'/../../public/versions');
        $this->sources = array(
            'rocketeer' => realpath(__DIR__.'/../../docs/rocketeer'),
            'satellite' => realpath(__DIR__.'/../../docs/satellite'),
        );
    }

    /**
     * Execute the command
     */
    public function fire()
    {
        foreach ($this->sources as $name => $source) {
            $this->current = $name;
            $this->generatePhars();
        }
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return array(
            ['force', 'F', InputOption::VALUE_NONE, 'Force the recompilation of all PHARs'],
        );
    }

    //////////////////////////////////////////////////////////////////////
    ////////////////////////////// VERSIONS //////////////////////////////
    //////////////////////////////////////////////////////////////////////

    /**
     * Get the available Rocketeer versions
     *
     * @return string[]
     */
    protected function getAvailableVersions()
    {
        // Get available tags
        $tags = (array) $this->executeCommands(['cd '.$this->sources[$this->current], 'git tag -l']);

        // Get available branches
        $branches = (array) $this->executeCommands(['cd '.$this->sources[$this->current], 'git branch -al']);

        // Merge
        $versions = array_merge($branches, $tags);
        $versions = array_map(function ($version) {
            $version = trim($version, ' *');
            $version = str_replace('remotes/origin/', null, $version);

            return $version;
        }, $versions);

        // Filter out the ones before a PHAR was available
        $versions = array_filter($versions, function ($version) {
            return $version && strpos($version, 'HEAD') === false && substr($version, 0, 1) !== '0';
        });

        return array_unique(array_values($versions));
    }

    //////////////////////////////////////////////////////////////////////
    ///////////////////////////// GENERATION /////////////////////////////
    //////////////////////////////////////////////////////////////////////

    /**
     * Generate the Phars for a repository
     */
    protected function generatePhars()
    {
        // Update repository
        $this->comment('Updating repository');
        $this->executeCommands(array(
            'cd '.$this->sources[$this->current],
            'git checkout master',
            'git fetch -pt',
            'git reset --hard',
            'git pull',
        ));

        $this->comment('Generating archives...');
        $tags = $this->getAvailableVersions();
        foreach ($tags as $tag) {
            $this->generatePhar($tag);
        }

        $this->comment('Generating current version archive');
        $this->copyLatestArchive($tags);
    }

    /**
     * Generate the archive for a version
     *
     * @param string $tag
     */
    protected function generatePhar($tag)
    {
        $handle      = $this->current.'/'.$tag;
        $source      = $this->sources[$this->current];
        $destination = $this->getPharDestination(str_replace('/', '-', $tag));
        if (file_exists($destination) && !in_array($tag, ['master', 'develop']) && !$this->option('force')) {
            $this->line("[$handle] Archive exists already, skipping");

            return;
        }

        $this->comment("[$handle] Preparing release");
        $this->executeCommands(array(
            'cd '.$source,
            'git reset --hard',
            'git checkout '.trim($tag, ' *'),
            'composer update',
        ));

        $this->comment("[$handle] Compiling");
        $this->executeCommands(array(
            'cd '.$source,
            'php '.$source.'/bin/compile',
        ));

        $this->success("[$handle] Moving archive");
        $this->executeCommands(array(
            'cd '.$source,
            'mv '.$source.'/bin/'.$this->current.'.phar '.$destination,
        ));
    }

    /**
     * Copy the latest version as rocketeer.phar
     *
     * @param array $tags
     *
     * @return integer
     */
    protected function copyLatestArchive($tags)
    {
        $latest = end($tags);
        $latest = $this->getPharDestination($latest);
        if (!file_exists($latest)) {
            return $this->error('Unable to create latest version archive');
        }

        $this->executeCommands(['cp '.$latest.' '.$this->getPharDestination()]);
    }

    //////////////////////////////////////////////////////////////////////
    ////////////////////////////// HELPERS ///////////////////////////////
    //////////////////////////////////////////////////////////////////////

    /**
     * @param string|array $commands
     *
     * @return string
     */
    protected function executeCommands($commands)
    {
        // Suppress output
        foreach ($commands as &$command) {
            $command .= ' 2> /dev/null';
        }

        // Merge and execute
        $commands = implode(' && ', $commands);
        exec($commands, $output);

        return $output;
    }

    /**
     * @param array $tags
     *
     * @return ProgressBar
     */
    protected function getProgressBar($tags)
    {
        $progress = new ProgressBar($this->output, count($tags));
        $progress->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% - %message%');
        $progress->start();

        return $progress;
    }

    /**
     * @param string|null $version
     *
     * @return string
     */
    protected function getPharDestination($version = null)
    {
        $name = $this->current;
        $name .= $version ? '-'.$version : null;

        return $this->phars.'/'.$name.'.phar';
    }
}
