var AppController = function ($scope) {
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

	$scope.unsorted = function(object) {
		return Object.keys(object);
	}

	$scope.highlight = function() {
		Prism.highlightAll();
	};
};
