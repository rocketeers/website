<?php

use Rocketeer\Website\Application;
use Rocketeer\Website\DocumentationGatherer;

include __DIR__.'/../vendor/autoload.php';

$app = new Application();
$docs = new DocumentationGatherer();
$assets = json_decode(file_get_contents(__DIR__.'/builds/manifest.json'), true);

return $app->view->display('home.twig', [
    'docs' => $docs->getDocumentation(),
    'assets' => $assets,
]);
