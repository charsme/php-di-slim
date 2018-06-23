<?php
declare(strict_types=1);

namespace Resilient\Test;

use PHPUnit\Framework\TestCase;
use Resilient\Test\MockSlim;
use Slim\App;
use DI\Container;
use phpDocumentor\Reflection\Types\Void_;
use Resilient\Test\MockController;
use Psr\Http\Message\ResponseInterface;
use Resilient\Test\MockClassA;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Interop\Container\ContainerInterface as ContainerInteropInterface;
use Resilient\Slim\CallableResolver;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Http\Environment;
use function DI\factory;
use function DI\create;

class FactoryTest extends TestCase
{
    protected $app;

    public function setUp()
    {
        $this->app = (new \Resilient\Slim\Factory())->createDefined([
            'displayErrorDetails' => true,
        ], [
            MockClassA::class => create(MockClassA::class),
            'environment' => function () {
                return Environment::mock();
            }
        ], []);
    }

    public function testApp():void
    {
        $this->assertInstanceOf(App::class, $this->app);
        $this->assertInstanceOf(Container::class, $this->app->getContainer());
        return;
    }

    public function testContainer():void
    {
        $app = $this->app;
        $container = $app->getContainer();
        $this->assertInstanceOf(PsrContainerInterface::class, $container);
        return;
    }

    public function testRoutesCallback():void
    {
        $app = clone $this->app;
        $container = $app->getContainer();

        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            return $response->getBody()->write('Resolved');
        });

        $response = $app->run(true);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('Resolved', (string) $response->getBody());

        return;
    }

    public function testController():void
    {
        $app = clone $this->app;
        $container = $app->getContainer();

        $app->get('/', MockController::class);

        $response = $app->run(true);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('Resolved', (string) $response->getBody());

        return;
    }

    public function testControllerWithArgs():void
    {
        $app = new \Resilient\Slim\App([
            'displayErrorDetails' => true,
        ], [
            MockClassA::class => create(MockClassA::class),
            'environment' => function () {
                return Environment::mock(['REQUEST_URI' => '/1234.html']);
            }
        ]);

        $container = $app->getContainer();

        $app->get('/{id}.html', MockController::class . '::withArgs');

        $response = $app->run(true);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('1234', (string) $response->getBody());

        return;
    }

    public function testControllerInjection():void
    {
        $app = clone $this->app;
        $container = $app->getContainer();

        $app->get('/', MockController::class . '::injection');

        $response = $app->run(true);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(MockClassA::class, (string) $response->getBody());

        return;
    }
}
