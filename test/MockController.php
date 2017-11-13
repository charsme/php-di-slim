<?php

namespace Resilient\Test;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Resilient\Test\MockClassA;

class MockController
{
    protected $object;

    public function __construct(MockClassA $object)
    {
        $this->object = $object;
    }

    public function __invoke(ResponseInterface $response)
    {
        return $response->getBody()->write("Resolved");
    }

    public function withArgs(ServerRequestInterface $request, ResponseInterface $response, string $id)
    {
        return $response->getBody()->write($id);
    }

    public function injection(ServerRequestInterface $request, ResponseInterface $response)
    {
        return $response->getBody()->write($this->object->sayHi());
    }
}
