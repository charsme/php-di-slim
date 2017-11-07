<?php
namespace Resilient\Slim;

use Slim\Interfaces\CallableResolverInterface;
use Invoker\CallableResolver as BaseCallableResolver;

/**
 * Resolve middleware and route callables using PHP-DI.
 */
class CallableResolver implements CallableResolverInterface
{
    /**
     * @var \Invoker\CallableResolver
     */
    private $callableResolver;
    public function __construct(BaseCallableResolver $callableResolver)
    {
        $this->callableResolver = $callableResolver;
    }
    /**
     * {@inheritdoc}
     */
    public function resolve($toResolve)
    {
        return $this->callableResolver->resolve($toResolve);
    }
}
