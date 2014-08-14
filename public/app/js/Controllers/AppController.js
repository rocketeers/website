var AppController = function ($scope, $location) {

	$scope.page = 'README';
	$scope.categories = [
		{
			label: 'Introduction',
			pages: {
				'Introduction'     : 'README',
				'What\'s Rocketeer': 'wiki/I-Introduction/Whats-Rocketeer',
				'Getting Started'  : 'wiki/I-Introduction/Getting-started',
			}
		},
		{
			label: 'Concepts',
			pages: {
				'Connections and Stages': 'wiki/II-Concepts/Connections-Stages',
				'Events'                : 'wiki/II-Concepts/Events',
				'Plugins'               : 'wiki/II-Concepts/Plugins',
				'Tasks'                 : 'wiki/II-Concepts/Tasks',
			}
		},
		{
			label: 'Help',
			pages: {
				'Changelog'      : 'CHANGELOG',
				'Troubleshooting': 'wiki/Troubleshooting',
			}
		}
	];
	$scope.subcategories = [];

	$scope.unsorted = function (object) {
		return Object.keys(object);
	}

	$scope.highlight = function () {
		// Bind categories to sidebar
		$scope.subcategories = [];
		$('main section').find('h1, h2, h3, h4').each(function (key, header) {
			$scope.subcategories.push({
				label : header.innerHTML,
				anchor: header.id,
				size  : header.tagName.toLowerCase(),
			});
		});

		// Highlight content
		Prism.highlightAll();

		// Update URL
		$location.path($scope.page);
	};
};
