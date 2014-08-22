<?php
use Rocketeer\Website\Application;

include __DIR__.'/../vendor/autoload.php';

$app = new Application;

return $app->view->display('home.twig', array(
	'docs' => $app->docs->getDocumentation(),
));
