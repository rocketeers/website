module Rocketeer {
	export class AppController {

		/**
		 * The current page
		 *
		 * @type {string}
		 */
		page;

		/**
		 * The available categories
		 *
		 * @type {any[]}
		 */
		categories;

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
			this.categories = docs;

			$scope.controller = this;
			$scope.$location = $location;

			// Set default page
			var page = $location.path() || 'docs/rocketeer/README';
			$location.path(page);

			$scope.year = new Date();
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

			// Transform tables
			$('table').addClass('table table-bordered');

			// Highlight content
			Prism.highlightAll();

			// Update URL
			this.$location.path(this.$scope.page);
		}

	}
}
