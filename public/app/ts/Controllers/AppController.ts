module Rocketeer {
	export class AppController {

		/**
		 * The current page
		 *
		 * @type {string}
		 */
		page = 'README';

		/**
		 * The available categories
		 *
		 * @type {any[]}
		 */
		categories = [
			{
				label: 'Introduction',
				pages: {
					'Introduction'     : 'README',
					'What\'s Rocketeer': 'docs/I-Introduction/Whats-Rocketeer',
					'Getting Started'  : 'docs/I-Introduction/Getting-started',
				}
			},
			{
				label: 'Concepts',
				pages: {
					'Connections and Stages': 'docs/II-Concepts/Connections-Stages',
					'Events'                : 'docs/II-Concepts/Events',
					'Plugins'               : 'docs/II-Concepts/Plugins',
					'Tasks'                 : 'docs/II-Concepts/Tasks',
				}
			},
			{
				label: 'Help',
				pages: {
					'Changelog'      : 'CHANGELOG',
					'Troubleshooting': 'docs/III-Further/Troubleshooting',
				}
			}
		];

		/**
		 * The subcategories
		 *
		 * @type {Array}
		 */
		subcategories = [];

		/**
		 * @param $scope
		 * @param $location
		 *
		 * @ngInject
		 */
		constructor(public $scope, public $location) {
			$scope.controller = this;
			$scope.$location = $location;

			// Set defautl page
			var page = $location.path() || 'README';
			$location.path(page);

			$scope.categories = this.categories;
			$scope.subcategories = this.subcategories;
		}

		/**
		 * Return an object, unsorted
		 *
		 * @param object
		 *
		 * @returns {string[]}
		 */
		unsorted(object) {
			return Object.keys(object);
		}

		/**
		 * Highlight codeblocks in the page
		 */
		highlight() {
			// Bind categories to sidebar
			this.$scope.subcategories = [];
			$('main section').find('h1, h2, h3, h4').each((key, header: HTMLElement) => {
				this.$scope.subcategories.push({
					label : header.innerHTML,
					anchor: header.id,
					size  : header.tagName.toLowerCase(),
				});
			});

			// Highlight content
			Prism.highlightAll();

			// Update URL
			this.$location.path(this.$scope.page);
		}

	}
}
