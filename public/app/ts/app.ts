declare var Prism: {
	highlightAll();
};

declare var docs;

// Application
//////////////////////////////////////////////////////////////////////

var app = angular.module('rocketeer', [
	'duScroll',
	'mgcrea.ngStrap',
]);

// Controllers
//////////////////////////////////////////////////////////////////////

angular.module('rocketeer').controller('AppController', Rocketeer.AppController);
