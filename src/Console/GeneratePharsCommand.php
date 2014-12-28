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
	protected $rocketeer;

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

		$this->rocketeer = realpath(__DIR__.'/../../docs/rocketeer');
		$this->phars     = realpath(__DIR__.'/../../public/versions');
	}

	/**
	 * Execute the command
	 */
	public function fire()
	{
		// Update repository
		$this->comment('Updating repository');
		$this->executeCommands(array(
			'cd '.$this->rocketeer,
			'git checkout master',
			'git fetch -pt',
			'git reset --hard',
			'git pull',
		));

		$this->comment('Generating archives...');
		$tags           = $this->getAvailableVersions();
		$this->progress = $this->getProgressBar($tags);
		foreach ($tags as $tag) {
			$this->generatePhar($tag);
			$this->progress->advance();
		}
		$this->progress->finish();

		$this->comment('Generating current version archive');
		$this->copyLatestArchive($tags);
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
		$tags = (array) $this->executeCommands(['cd '.$this->rocketeer, 'git tag -l']);

		// Get available branches
		$branches = (array) $this->executeCommands(['cd '.$this->rocketeer, 'git branch -al']);

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
	 * Generate the archive for a version
	 *
	 * @param string $tag
	 */
	protected function generatePhar($tag)
	{
		$destination = $this->phars.'/rocketeer-'.str_replace('/', '-', $tag).'.phar';
		$this->progress->setMessage("[$tag] Checking for archive existence");
		if (file_exists($destination) && !in_array($tag, ['master', 'develop']) && !$this->option('force')) {
			return;
		}

		$this->progress->setMessage("[$tag] Preparing release");
		$this->executeCommands(array(
			'cd '.$this->rocketeer,
			'git reset --hard',
			'git checkout '.trim($tag, ' *'),
			'composer update',
		));

		$this->progress->setMessage("[$tag] Compiling");
		$this->executeCommands(array(
			'cd '.$this->rocketeer,
			'php '.$this->rocketeer.'/bin/compile',
		));

		$this->progress->setMessage("[$tag] Moving archive");
		$this->executeCommands(array(
			'cd '.$this->rocketeer,
			'mv '.$this->rocketeer.'/bin/rocketeer.phar '.$destination,
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
		$latest = $this->phars.'/rocketeer-'.$latest.'.phar';
		if (!file_exists($latest)) {
			return $this->error('Unable to create latest version archive');
		}

		$this->executeCommands(['cp '.$latest.' '.$this->phars.'/rocketeer.phar']);
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
}
