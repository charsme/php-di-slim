<?php

namespace Resilient\Slim;

use function DI\get;
use function DI\object;
use function DI\factory;
use Psr\Container\ContainerInterface;
use Interop\Container\ContainerInterface as ContainerInteropInterface;
use DI\ContainerBuilder;
use DI\Container;
use Slim\App as SlimApp;
use Slim\Router;
use Slim\Http\Environment;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Headers;
use Slim\Handlers\PhpError;
use Slim\Handlers\Error;
use Slim\Handlers\NotFound;
use Slim\Handlers\NotAllowed;
use Invoker\Invoker;
use Invoker\CallableResolver as BaseCallableResolver;
use Invoker\InvokerInterface;
use Invoker\ParameterResolver\ResolverChain;
use Invoker\ParameterResolver\AssociativeArrayResolver;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Invoker\ParameterResolver\DefaultValueResolver;
use Resilient\Slim\ControllerInvoker;
use Resilient\Slim\CallableResolver;
use Doctrine\Common\Cache\Cache as CacheInterface;

/**
 * App
 * Derived from Slim App for dependencies injection to php-di definitions entry
 */
final class App extends SlimApp
{
    /**
     * Define setting and dependencies for the app. Caching also configurable
     *
     * @param array $settings
     * @param array $dependencies
     * @param array $caching MUST have this keys for aplicable setting 'path' => 'folder for proxies writing', 'cacheHandler' => 'cache object that an instance of Doctrine\Common\Cache\Cache', 'namespace' => 'app cache namespace'
     */
    public function __construct(array $settings = [], array $dependencies = [], array $caching = [])
    {
        $config = ['settings' => $this->appSettings($settings)];
        $config += $this->appDependencies($dependencies);

        $builder = $this->appSetCache(
            ( new ContainerBuilder() )->addDefinitions($config),
            $caching
        );

        parent::__construct($builder->build());
    }

    /**
     * appSetCache
     *
     * @param ContainerBuilder $builder
     * @param array $caching MUST have this keys for aplicable setting 'path' => 'folder for proxies writing', 'cacheHandler' => 'cache object that an instance of Doctrine\Common\Cache\Cache'
     * @return ContainerBuilder
     */
    public function appSetCache(ContainerBuilder $builder, array $caching) : ContainerBuilder
    {
        if (isset($caching['cacheHandler']) && $caching['cacheHandler'] instanceof CacheInterface) {
            $builder->setDefinitionCache($caching['cacheHandler']);
        }

        if (isset($caching['path'])) {
            $builder->writeProxiesToFile(true, $caching['path']);
        }

        return $builder;
    }

    /**
     * appSettings
     * Ensure settings params are met in full
     *
     * @param array $settings
     * @return array
     */
    public function appSettings(array $settings = []):array
    {
        return \array_merge([
            'httpVersion' => '2.0',
            'responseChunkSize' => 4096,
            'outputBuffering' => 'append',
            'determineRouteBeforeAppMiddleware' => false,
            'displayErrorDetails' => false,
            'addContentLengthHeader' => true,
            'routerCacheFile' => false
        ], $settings);
    }

    /**
     * appDependencies
     * php-di definitions dictators
     *
     * @param array $dependencies
     * @return array
     */
    public function appDependencies(array $dependencies = []):array
    {
        return \array_merge([
            ContainerInteropInterface::class => get(Container::class),
            Router::class => object(Router::class),
            'router' => get(Router::class),
            Environment::class => object(Environment::class),
            'environment'=> get(Environment::class),
            'request' => function (ContainerInterface $container) {
                return Request::createFromEnvironment($container->get('environment'));
            },
            'response' => function (ContainerInterface $container) {
                return (
                    new Response(
                        200,
                        new Headers([
                            'Content-Type' => 'text/html; charset=UTF-8'
                            ])
                        )
                )->withProtocolVersion($container->get('settings')['httpVersion']);
            },
            InvokerInterface::class => function (ContainerInteropInterface $container) {
                return new Invoker(
                    new ResolverChain([
                        new TypeHintContainerResolver($container),
                        new AssociativeArrayResolver(),
                        new DefaultValueResolver()
                    ]),
                    $container
                );
            },
            'foundHandler' => object(ControllerInvoker::class),
            BaseCallableResolver::class => object(BaseCallableResolver::class),
            'callableResolver' => object(CallableResolver::class),
            'phpErrorHandler' => object(PhpError::class)->lazy(),
            'errorHandler' => object(Error::class)->lazy(),
            'notFoundHandler' => object(NotFound::class)->lazy(),
            'notAllowedHandler' => object(NotAllowed::class)->lazy()
        ], $dependencies);
    }
}
