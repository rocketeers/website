<?php
namespace Rocketeer\Website\Services;

use Illuminate\Support\Str;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class PharGenerator
{
    /**
     * Path to the manifest
     */
    const MANIFEST = 'public/versions/manifest.json';

    /**
     * The path to the repository
     *
     * @type string
     */
    protected $source;

    /**
     * The folder where PHARs will go
     *
     * @type string
     */
    protected $destination;

    /**
     * The name of the repository
     *
     * @type string
     */
    protected $name;

    /**
     * @type OutputInterface
     */
    protected $output;

    /**
     * Whether to force creation of PHARs
     *
     * @type boolean
     */
    protected $force = false;

    /**
     * @param string $name
     * @param string $source
     * @param string $destination
     */
    public function __construct($name, $source, $destination)
    {
        $this->source      = $source;
        $this->destination = $destination;
        $this->name        = $name;
        $this->output      = new NullOutput();
    }

    /**
     * Generate the Phars for a repository
     */
    public function generatePhars()
    {
        $this->resetManifest();

        // Update repository
        $this->output->writeln('<comment>Updating repository</comment>');
        $this->executeCommands(array(
            'cd '.$this->source,
            'git checkout master',
            'git fetch -pt',
            'git reset --hard',
            'git pull',
        ));

        $this->output->writeln('<comment>Generating archives...</comment>');
        $tags = $this->getAvailableVersions();
        foreach ($tags as $tag => $sha1) {
            $this->generatePhar($tag, $sha1);
        }

        $this->output->writeln('<comment>Generating current version archive</comment>');
        $this->copyLatestArchive($tags);
    }

    //////////////////////////////////////////////////////////////////////
    ////////////////////////////// OPTIONS ///////////////////////////////
    //////////////////////////////////////////////////////////////////////

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @param boolean $force
     */
    public function setForce($force)
    {
        $this->force = $force;
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
            'cd '.$this->source,
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
     * Generate the archive for a version
     *
     * @param string $tag
     * @param string $sha1
     */
    protected function generatePhar($tag, $sha1)
    {
        $handle      = $this->name.'/'.$tag;
        $source      = $this->source;
        $isBranchTag = in_array($tag, ['master', 'develop']);
        $destination = $this->getPharDestination(str_replace('/', '-', $tag));
        $basename    = basename($destination);

        // Update manifest
        $this->updateManifest($tag, $sha1, $basename);

        // Cancel if already compiled
        if (file_exists($destination) && !$isBranchTag && !$this->force) {
            $this->output->writeln("[$handle] Archive exists already, skipping");

            return;
        }

        $this->output->writeln("<comment>[$handle] Preparing release</comment>");
        $this->executeCommands(array(
            'cd '.$source,
            'git reset --hard',
            'git checkout '.trim($tag, ' *'),
            'git reset --hard',
            'git clean -df',
        ));

        $this->output->writeln("<comment>[$handle] Updating repository</comment>");
        $commands = $isBranchTag ? [
            'cd '.$source,
            'git pull',
            'composer update'
        ] : [
            'cd '.$source,
            'composer update'
        ];
        $this->executeCommands($commands);

        $this->output->writeln("<comment>[$handle] Compiling</comment>");
        $compiler = file_exists($source.'/box.json') ? '../../vendor/bin/box build -v' : 'php '.$source.'/bin/compile';
        $this->executeCommands(array(
            'cd '.$source,
            $compiler,
        ));

        $this->output->writeln("<comment>[$handle] Moving archive</comment>");
        $this->executeCommands(array(
            'cd '.$source,
            'mv '.$source.'/bin/'.$this->name.'.phar '.$destination,
        ));
    }

    /**
     * Copy the latest version as universal phar
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
            return $this->output->writeln('<error>Unable to create latest version archive</error>');
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
            $this->output->writeln('<fg=magenta>$ '.$command.'</fg=magenta>');
        }

        // Merge and execute
        $commands = implode(' && ', $commands);
        exec($commands, $output);

        foreach ($output as $line) {
            $this->output->writeln('-- '.$line);
        }

        return $output;
    }

    /**
     * @param string|null $version
     *
     * @return string
     */
    protected function getPharDestination($version = null)
    {
        $name = $this->name;
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
        if ($tag === 'develop') {
            return;
        }

        $manifest = file_get_contents(self::MANIFEST);
        $manifest = json_decode($manifest, true);

        $manifest[] = array(
            'name'    => $basename,
            'sha1'    => $sha1,
            'url'     => 'http://rocketeer.autopergamene.eu/versions/'.$basename,
            'version' => $tag,
        );

        $manifest = json_encode($manifest, JSON_PRETTY_PRINT);
        file_put_contents(self::MANIFEST, $manifest);
    }
}
