<?php

use Rocketeer\Website\Application;
use Rocketeer\Website\DocumentationGatherer;

include __DIR__.'/../vendor/autoload.php';

$app = new Application();
$docs = new DocumentationGatherer();

return $app->view->display('home.twig', [
    'docs' => $docs->getDocumentation(),
]);
