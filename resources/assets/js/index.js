import 'bootstrap-sass/assets/stylesheets/_bootstrap.scss';
import 'prismjs/themes/prism-okaidia.css';
import '../sass/styles.scss';

import angular from 'angular';
import 'angular-sanitize';
import 'angular-scroll';
import 'angular-strap';
import AppController from './Controllers/AppController';

// Application
//////////////////////////////////////////////////////////////////////

angular.module('rocketeer', [
	'ngSanitize',
	'duScroll',
	'mgcrea.ngStrap',
]);

// Controllers
//////////////////////////////////////////////////////////////////////

angular.module('rocketeer').controller('AppController', AppController);
