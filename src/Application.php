<?php

namespace Rocketeer\Website;

use Illuminate\Container\Container;
use Twig_Environment;
use Twig_Loader_Filesystem;

/**
 * @property DocumentationGatherer docs
 *
 * @author Maxime Fabre <ehtnam6@gmail.com>
 */
class Application extends Container
{
    /**
     * Build the application.
     */
    public function __construct()
    {
        // Define paths
        $root = __DIR__.'/..';

        // Create configuration
        $this['config'] = [
            'paths.views' => $root.'/resources/views',
            'paths.cache' => $root.'/cache',
        ];

        $this->registerThirdParty();
    }

    /**
     * Get an instance from the container.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this[$name];
    }

    /**
     * Register the application's services.
     */
    protected function registerThirdParty()
    {
        $this->singleton('view', function ($app) {
            $loader = new Twig_Loader_Filesystem($app['config']['paths.views']);
            $twig = new Twig_Environment($loader, [
                'cache' => $app['config']['paths.cache'],
                'auto_reload' => true,
                'debug' => true,
            ]);

            return $twig;
        });
    }
}
