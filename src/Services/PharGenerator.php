<?php

namespace Rocketeer\Website\Services;

use Illuminate\Support\Str;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class PharGenerator
{
    /**
     * Path to the manifest.
     */
    const MANIFEST = 'public/versions/manifest.json';

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
     * @var OutputInterface
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
        $this->output = new NullOutput();
    }

    /**
     * Generate the Phars for a repository.
     */
    public function generatePhars()
    {
        if (ini_get('phar.readonly') === '1') {
            $this->output->writeln('<error>Need to set phar.readonly to true</error>');

            return;
        }

        $this->resetManifest();

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
            if (Str::contains($tag, ['feature/'])) {
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
        $isBranchTag = in_array($tag, ['master', 'develop'], true);
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
            $this->output->writeln("<comment>[$handle] Preparing release</comment>");
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

        $this->output->writeln("<comment>[$handle] Updating repository</comment>");
        $commands = $isBranchTag ? [
            'cd '.$this->source,
            'git pull',
            'composer install',
        ] : [
            'cd '.$this->source,
            'composer install',
        ];
        $this->executeCommands($commands);

        $this->output->writeln("<comment>[$handle] Compiling</comment>");
        $this->executeCommands([
            'cd '.$this->source,
            $compilationMethod,
        ]);

        $this->output->writeln("<comment>[$handle] Moving archive</comment>");
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
        $name .= $version && $version !== 'master' ? '-'.$version : null;

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
        file_put_contents(self::MANIFEST, '[]');
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
        if ($tag === 'develop' || $tag === 'master') {
            return;
        }

        $manifest = file_get_contents(self::MANIFEST);
        $manifest = json_decode($manifest, true);

        $manifest[] = [
            'name' => $basename,
            'sha1' => $sha1,
            'url' => 'http://rocketeer.autopergamene.eu/versions/'.$basename,
            'version' => $tag,
        ];

        $manifest = json_encode($manifest, JSON_PRETTY_PRINT);
        file_put_contents(self::MANIFEST, $manifest);
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
}
