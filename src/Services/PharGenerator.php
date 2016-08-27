<?php

namespace Rocketeer\Website\Services;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PharGenerator
{
    /**
     * Path to the manifest.
     */
    const MANIFEST = 'manifest.json';

    /**
     * The folder in which to prepare the archives.
     *
     * @var string
     */
    protected $tmp;

    /**
     * The path to the repository.
     *
     * @var string
     */
    protected $source;

    /**
     * The folder where PHARs will go.
     *
     * @var string
     */
    protected $destination;

    /**
     * The name of the repository.
     *
     * @var string
     */
    protected $name;

    /**
     * @var SymfonyStyle
     */
    protected $output;

    /**
     * Whether to force creation of PHARs.
     *
     * @var bool
     */
    protected $force = false;

    /**
     * @param string $name
     * @param string $source
     * @param string $destination
     */
    public function __construct($name, $source, $destination)
    {
        $this->tmp = realpath(__DIR__.'/../../public/tmp');
        $this->source = $source;
        $this->destination = $destination;
        $this->name = $name;

        $this->setOutput(new NullOutput());
    }

    /**
     * Generate the Phars for a repository.
     */
    public function generatePhars()
    {
        if (ini_get('phar.readonly') === '1') {
            $this->output->error('Need to set phar.readonly to true');

            return;
        }

        $this->resetManifest();

        $this->output->title('Generating archives...');
        $tags = $this->getAvailableVersions();
        foreach ($tags as $tag => $sha1) {
            $this->generatePhar($tag, $sha1);
        }

        $this->output->section('Generating current version archive');
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
        $this->output = new SymfonyStyle(new ArrayInput([]), $output);
    }

    /**
     * @param bool $force
     */
    public function setForce($force)
    {
        $this->force = $force;
    }

    //////////////////////////////////////////////////////////////////////
    ////////////////////////////// VERSIONS //////////////////////////////
    //////////////////////////////////////////////////////////////////////

    /**
     * Get the available Rocketeer versions.
     *
     * @return string[]
     */
    protected function getAvailableVersions()
    {
        // Update repository
        $this->executeCommands([
            'cd '.$this->source,
            'git checkout develop',
            'git checkout master',
            'git fetch -pt',
            'git pull',
        ]);

        // Get available tags
        $versions = [];
        $tags = (array) $this->executeCommands([
            'cd '.$this->source,
            'git show-ref --tags --heads',
        ]);

        foreach ($tags as $tag) {
            $tag = explode(' ', $tag);
            $sha1 = $tag[0];
            $tag = preg_replace('#refs/(tags|remotes|heads)(/origin)?/(.+)#', '$3', $tag[1]);
            if (strpos($tag, 'feature/') !== false) {
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
     * Generate the archive for a version.
     *
     * @param string $tag
     * @param string $sha1
     */
    protected function generatePhar($tag, $sha1)
    {
        $handle = $this->name.'/'.$tag;
        $isBranchTag = $this->isBranchTag($tag);
        if (version_compare($tag, '1.0.0', 'lt') && !$isBranchTag) {
            $this->output->writeln("[$handle] No PHAR for this version");

            return;
        }

        // Update manifest
        $destination = $this->getPharDestination(str_replace('/', '-', $tag));
        $basename = basename($destination);
        $this->updateManifest($tag, $sha1, $basename);

        // Cancel if already compiled
        if (file_exists($destination) && !$isBranchTag && !$this->force) {
            $this->output->writeln("[$handle] Archive exists already, skipping");

            return;
        }

        // Create folder if necessary
        $branch = trim($tag, ' *');
        $this->source = $this->tmp.DIRECTORY_SEPARATOR.$branch;
        if (!is_dir($this->source)) {
            $this->output->section("[$handle] Preparing release");
            $this->executeCommands([
                'cd '.$this->tmp,
                'git clone -b '.$branch.' git@github.com:rocketeers/rocketeer.git '.$branch,
            ]);
        }

        $compilationMethod = $this->getCompilationMethod();
        if (!$compilationMethod) {
            $this->output->writeln("[$handle] Can't build PHAR for current version");

            return;
        }

        $this->output->section("[$handle] Updating repository");
        $this->executeCommands($isBranchTag ? [
            'cd '.$this->source,
            'git pull',
            'composer update',
        ] : [
            'cd '.$this->source,
            'composer update',
        ]);

        $this->output->section("[$handle] Compiling");
        $this->executeCommands([
            'cd '.$this->source,
            $compilationMethod,
        ]);

        $this->output->section("[$handle] Moving archive");
        $this->executeCommands([
            'cd '.$this->source,
            'mv '.$this->source.'/bin/'.$this->name.'.phar '.$destination,
        ]);
    }

    /**
     * Copy the latest version as universal phar.
     *
     * @param array $tags
     *
     * @return int
     */
    protected function copyLatestArchive($tags)
    {
        $versions = array_keys($tags);
        $latest = end($versions);
        $latest = $this->getPharDestination($latest);
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

        return $this->destination.'/'.$name.'.phar';
    }

    //////////////////////////////////////////////////////////////////////
    ////////////////////////////// MANIFEST //////////////////////////////
    //////////////////////////////////////////////////////////////////////

    /**
     * Reset the contents of the manifest.
     */
    protected function resetManifest()
    {
        file_put_contents($this->destination.'/'.self::MANIFEST, '[]');
    }

    /**
     * Update the Box manifest.
     *
     * @param string $tag
     * @param string $sha1
     * @param string $basename
     */
    protected function updateManifest($tag, $sha1, $basename)
    {
        if ($this->isBranchTag($tag)) {
            return;
        }

        $manifest = file_get_contents($this->destination.'/'.self::MANIFEST);
        $manifest = json_decode($manifest, true);

        $manifest[] = [
            'name' => $basename,
            'sha1' => $sha1,
            'url' => 'http://rocketeer.autopergamene.eu/versions/'.$basename,
            'version' => $tag,
        ];

        $manifest = json_encode($manifest, JSON_PRETTY_PRINT);
        file_put_contents($this->destination.'/'.self::MANIFEST, $manifest);
    }

    /**
     * @return string
     */
    protected function getCompilationMethod()
    {
        if (file_exists($this->source.'/box.json')) {
            return '../../../vendor/bin/box build -v';
        }

        if (file_exists($this->source.'/bin/compile')) {
            return 'php '.$this->source.'/bin/compile';
        }

        return false;
    }

    //////////////////////////////////////////////////////////////////////
    ////////////////////////////// HELPERS ///////////////////////////////
    //////////////////////////////////////////////////////////////////////

    /**
     * @param string $tag
     *
     * @return bool
     */
    protected function isBranchTag(string $tag): bool
    {
        return in_array($tag, ['master', 'develop'], true);
    }
}
