<?php
namespace Rocketeer\Website;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class DocumentationGatherer
{
	/**
	 * @type array
	 */
	protected $documentation = [];

	/**
	 * @return array
	 */
	public function getDocumentation()
	{
		// Gather files
		$files = new Finder();
		$files = $files->in(__DIR__.'/../docs/*')->name('*.md')->files();
		foreach ($files as $file) {
			$this->addPage($file);
		}

		// Add custom pages
		$this->addCustomPages();

		return array_values($this->documentation);
	}

	/**
	 * @param SplFileInfo $file
	 */
	protected function addPage(SplFileInfo $file)
	{
		$folder   = $file->getPath();
		$folder   = basename($folder);
		$category = preg_replace('/[IV]+-(.+)/', '$1', $folder);

		// Create documentation entry the first time
		if (!isset($this->documentation[$category])) {
			$this->documentation[$category] = array(
				'label' => $category,
				'pages' => [],
			);
		}

		// Build handle and label
		$name   = $file->getBasename('.md');
		$handle = str_replace('-', ' ', $name);

		$this->documentation[$category]['pages'][$handle] = 'docs/'.$folder.'/'.$name;
	}

	/**
	 * Add the custom pages to the documentations
	 */
	protected function addCustomPages()
	{
		$this->documentation['Introduction']['pages'] = ['Introduction' => 'README'] + $this->documentation['Introduction']['pages'];
		$this->documentation['Help']['pages']         = ['Changelog' => 'CHANGELOG'] + $this->documentation['Help']['pages'];
	}
}
