import Prism from 'prismjs';
import 'prismjs/themes/prism-okaidia.css';
import 'prismjs/components/prism-bash';
import 'prismjs/components/prism-php';
import 'prismjs/components/prism-php-extras';

export default class AppController {

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
    constructor($scope, $location) {
        this.$scope = $scope;
        this.$location = $location;

        this.categories = docs;

        $scope.controller = this;
        $scope.$location = $location;

        // Setup navigation
        $scope.year = new Date();
        $scope.categories = this.categories;
        $scope.subcategories = this.subcategories;

        // Set default page
        const page = $location.path() || 'rocketeer/README';
        $location.path(page);
        $scope.$on('$locationChangeStart', function() {
            const path = $location.path().replace(/^\/|\/$/g, '');
            $scope.contents = require(`../../../../docs/${path}.md`);
        });
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
        const titles = this.$location.path() === '/docs/rocketeer/CHANGELOG' ? 'h2' : 'h1, h2, h3, h4';

        // Bind categories to sidebar
        this.$scope.subcategories = [];
        $('main section').find(titles).each((key, header) => {
            this.$scope.subcategories.push({
                label: $(header).text().replace(/ - [0-9-]{10}/, ''),
                anchor: header.id,
                size: header.tagName.toLowerCase(),
            });
        });

        // Transform tables
        $('table').addClass('table table-bordered');

        // Transform download links
        $('a[href="http://rocketeer.autopergamene.eu/versions/rocketeer.phar"]').attr('target', '_blank').attr('download', '');

        // Highlight content
        Prism.highlightAll();

        // Update URL
        this.$location.path(this.$scope.page);
    }

}
