<?php
namespace Rocketeer\Website\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
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
            // 'satellite' => realpath(__DIR__.'/../../docs/satellite'),
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
        $versions = [];
        $tags     = (array) $this->executeCommands([
            'cd '.$this->sources[$this->current],
            'git show-ref --tags --heads'
        ]);
        foreach ($tags as $tag) {
            $tag  = explode(' ', $tag);
            $sha1 = $tag[0];
            $tag  = preg_replace('#refs/(tags|remotes|heads)(/origin)?/(.+)#', '$3', $tag[1]);
            if (Str::contains($tag, ['feature/', 'master'])) {
                continue;
            }

            $versions[$tag] = $sha1;
        }

        return $versions;
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
        foreach ($tags as $tag => $sha1) {
            $this->generatePhar($tag, $sha1);
        }

        $this->comment('Generating current version archive');
        $this->copyLatestArchive($tags);
    }

    /**
     * Generate the archive for a version
     *
     * @param string $tag
     * @param string $sha1
     */
    protected function generatePhar($tag, $sha1)
    {
        $handle      = $this->current.'/'.$tag;
        $source      = $this->sources[$this->current];
        $isBranchTag = in_array($tag, ['master', 'develop']);
        $destination = $this->getPharDestination(str_replace('/', '-', $tag));
        $basename    = basename($destination);

        // Update manifest
        $this->updateManifest($tag, $sha1, $basename);

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
        $compiler = file_exists($source.'/box.json') ? '../../vendor/bin/box build -v' : 'php '.$source.'/bin/compile';
        $this->executeCommands(array(
            'cd '.$source,
            $compiler,
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
        $versions = array_keys($tags);
        $latest   = end($versions);
        $latest   = $this->getPharDestination($latest);
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

        foreach ($output as $line) {
            $this->line('-- '.$line);
        }

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
     * Update the Box manifest
     *
     * @param string $tag
     * @param string $sha1
     * @param string $basename
     */
    protected function updateManifest($tag, $sha1, $basename)
    {
        $manifest = file_get_contents(self::MANIFEST);
        $manifest = json_decode($manifest, true);

        $manifest[] = array(
            'name'    => $basename,
            'sha1'    => $sha1,
            'url'     => 'http://rocketeer.autopergamene.eu/versions/'.$basename,
            'version' => $tag === 'develop' ? '3.0-dev' : $tag,
        );

        $manifest = json_encode($manifest, JSON_PRETTY_PRINT);
        file_put_contents(self::MANIFEST, $manifest);
    }
}
