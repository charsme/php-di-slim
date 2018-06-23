<?php declare(strict_types = 1);

namespace Resilient\Slim;

use function DI\get;
use function DI\create;
use function DI\autowire;
use Psr\Container\ContainerInterface;
use Interop\Container\ContainerInterface as ContainerInteropInterface;
use DI\ContainerBuilder;
use DI\Container;
use Slim\Router;
use Slim\Interfaces\RouterInterface;
use Slim\Http\Environment;
use Slim\Http\Response;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Handlers\PhpError;
use Slim\Handlers\Error;
use Slim\Handlers\NotFound;
use Slim\Handlers\NotAllowed;
use Invoker\InvokerInterface;
use Invoker\Invoker;
use Invoker\CallableResolver as BaseCallableResolver;
use Invoker\ParameterResolver\ResolverChain;
use Invoker\ParameterResolver\AssociativeArrayResolver;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Invoker\ParameterResolver\DefaultValueResolver;
use Resilient\Slim\ControllerInvoker;
use Resilient\Slim\CallableResolver;
use Doctrine\Common\Cache\Cache as CacheInterface;

trait SlimAble
{
    /**
     * settings
     * Ensure settings params are met in full
     *
     * @param array $settings
     * @return array
     */
    private function settings(array $settings = []) : array
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
     * dependencies
     * slim base dependencies register, overideable
     *
     * @param array $dependencies
     * @return array
     */
    private function dependencies(array $dependencies = [], array $settings = []) : array
    {
        return \array_merge([
            ContainerInteropInterface::class => get(Container::class),
            Router::class => autowire(Router::class)->method('setCacheFile', $settings['routerCacheFile']),
            RouterInterface::class => get(Router::class),
            'router' => get(Router::class),
            Environment::class => create(Environment::class)->constructor($_SERVER),
            'environment' => get(Environment::class),
            'request' => function (ContainerInterface $container) {
                return Request::createFromEnvironment($container->get('environment'));
            },
            
            'response' => function () use ($settings) {
                return (
                    new Response(
                        200,
                        new Headers(['Content-Type' => 'text/html; charset=UTF-8'])
                    )
                )->withProtocolVersion($settings['httpVersion']);
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
            'foundHandler' => autowire(ControllerInvoker::class),
            BaseCallableResolver::class => autowire(BaseCallableResolver::class),
            'callableResolver' => autowire(CallableResolver::class),
            'phpErrorHandler' => create(PhpError::class)->constructor($settings['displayErrorDetails'])->lazy(),
            'errorHandler' => create(Error::class)->constructor($settings['displayErrorDetails'])->lazy(),
            'notFoundHandler' => create(NotFound::class)->lazy(),
            'notAllowedHandler' => create(NotAllowed::class)->lazy()
        ], $dependencies);
    }

    /**
     * setCaching
     *
     * @param ContainerBuilder $builder
     * @param array $caching MUST have this keys for aplicable setting 'path' => 'folder for proxies writing', 'cacheHandler' => 'cache object that an instance of Doctrine\Common\Cache\Cache'
     * @return ContainerBuilder
     */
    private function setCaching(ContainerBuilder $builder, array $caching = []) : ContainerBuilder
    {
        if (isset($caching['cacheHandler']) && $caching['cacheHandler'] instanceof CacheInterface) {
            $builder->setDefinitionCache($caching['cacheHandler']);
        }

        if (isset($caching['path'])) {
            $builder->writeProxiesToFile(true, $caching['path']);
        }

        return $builder;
    }
}
