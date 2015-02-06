<?php
namespace Rocketeer\Website\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputOption;

class GeneratePharsCommand extends Command
{
    /**
     * Path to the manifest
     */
    const MANIFEST = 'public/versions/manifest.json';

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
        $this->resetManifest();

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
        $isBranchTag = in_array($tag, ['master', 'develop']);
        $destination = $this->getPharDestination(str_replace('/', '-', $tag));
        $basename    = basename($destination);

        // Update manifest
        $this->updateManifest($tag, $basename);

        // Cancel if already compiled
        if (file_exists($destination) && !$isBranchTag && !$this->option('force')) {
            $this->line("[$handle] Archive exists already, skipping");

            return;
        }

        $this->comment("[$handle] Preparing release");
        $this->executeCommands(array(
            'cd '.$source,
            'git reset --hard',
            'git checkout '.trim($tag, ' *'),
            'git reset --hard',
            'git clean -df',
        ));

        $this->comment("[$handle] Updating repository");
        $commands = $isBranchTag ? [
            'cd '.$source,
            'git pull',
            'composer update'
        ] : [
            'cd '.$source,
            'composer update'
        ];
        $this->executeCommands($commands);

        $this->comment("[$handle] Compiling");
        $this->executeCommands(array(
            'cd '.$source,
            'php '.$source.'/bin/compile',
        ));

        $this->comment("[$handle] Moving archive");
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
        // Display commands
        foreach ($commands as $command) {
            $this->line('<fg=magenta>$ '.$command.'</fg=magenta>');
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

    //////////////////////////////////////////////////////////////////////
    ////////////////////////////// MANIFEST //////////////////////////////
    //////////////////////////////////////////////////////////////////////

    /**
     * Reset the contents of the manifest
     */
    protected function resetManifest()
    {
        file_put_contents(self::MANIFEST, '[]');
    }

    /**
     * @param string $tag
     * @param string $basename
     */
    protected function updateManifest($tag, $basename)
    {
        $manifest = file_get_contents(self::MANIFEST);
        $manifest = json_decode($manifest, true);

        $manifest[] = array(
            'name'    => $basename,
            'sha1'    => $tag,
            'url'     => 'http://rocketeer.autopergamene.eu/versions/'.$basename,
            'version' => $tag,
        );

        $manifest = json_encode($manifest, JSON_PRETTY_PRINT);
        file_put_contents(self::MANIFEST, $manifest);
    }
}
