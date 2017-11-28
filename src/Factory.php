<?php declare(strict_types = 1);

namespace Resilient\Slim;

use DI\ContainerBuilder;
use Slim\App as SlimApp;
use Resilient\Slim\SlimAble;

final class Factory
{
    use SlimAble;

    /**
     * create a new App instance with mapped config
     *
     * @param array $config MAY contains these keys 'settings' for slim app setting, 'dependencies' for customclass dependencies, 'caching' for cache options
     * @return SlimApp
     */
    public function create(array $config) : SlimApp
    {
        return $this->createDefined($config['settings'] ?: [], $config['dependencies'] ?: [], $config['caching'] ?: []);
    }

    public function createDefined(array $settings, array $dependencies, array $caching) : SlimApp
    {
        $settings = $this->settings($settings);
        $dependencies = $this->dependencies($dependencies, $settings);

        $container = $this->setCaching(
            (new ContainerBuilder())->addDefinitions(\array_merge(['settings' => $settings], $dependencies)),
            $caching
        )->build();

        return new SlimApp($container);
    }
}
