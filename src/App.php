<?php declare(strict_types = 1);

namespace Resilient\Slim;

use DI\ContainerBuilder;
use DI\Container;
use Slim\App as SlimApp;
use Resilient\Slim\SlimAble;

/**
 * App
 * Derived from Slim App for dependencies injection to php-di definitions entry
 */
final class App extends SlimApp
{
    use SlimAble;

    /**
     * Define setting and dependencies for the app. Caching also configurable
     *
     * @param array $settings
     * @param array $dependencies
     * @param array $caching MUST have this keys for aplicable setting 'path' => 'folder for proxies writing', 'cacheHandler' => 'cache object that an instance of Doctrine\Common\Cache\Cache', 'namespace' => 'app cache namespace'
     */
    public function __construct(array $settings = [], array $dependencies = [], array $caching = [])
    {
        $settings = $this->settings($settings);
        $dependencies = $this->dependencies($dependencies, $settings);

        $builder = $this->setCaching(
            (new ContainerBuilder())->addDefinitions(\array_merge(['settings' => $settings], $dependencies)),
            $caching
        );

        parent::__construct($builder->build());
    }
}
