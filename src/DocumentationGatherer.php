<?php
namespace Rocketeer\Website;

use Symfony\Component\Finder\Finder;

class DocumentationGatherer
{
	/**
	 * @return array
	 */
	public function getDocumentation()
	{
		$documentation = [];

		// Gather files
		$files = new Finder();
		$files = $files->in(__DIR__.'/../docs/*')->name('*.md')->files();

		/** @type \SplFileObject[] $files */
		foreach ($files as $file) {
			$folder   = $file->getPath();
			$folder   = basename($folder);
			$category = preg_replace('/[IV]+-(.+)/', '$1', $folder);

			// Create documentation entry the first time
			if (!isset($documentation[$category])) {
				$documentation[$category] = array(
					'label' => $category,
					'pages' => [],
				);
			}

			// Build handle and label
			$name   = $file->getBasename('.md');
			$handle = str_replace('-', ' ', $name);

			$documentation[$category]['pages'][$handle] = 'docs/'.$folder.'/'.$name;
		}

		// Add custom pages
		$documentation['Introduction']['pages'] = ['Introduction' => 'README'] + $documentation['Introduction']['pages'];
		$documentation['Help']['pages']         = ['Changelog' => 'CHANGELOG'] + $documentation['Help']['pages'];

		return array_values($documentation);
	}
}
