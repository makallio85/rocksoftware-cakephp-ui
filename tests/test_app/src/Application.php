<?php
declare(strict_types=1);

namespace TestApp;

use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\BaseApplication;
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;
use RockSoftware\Ui\UiPlugin;

/**
 * A consuming application: an error handler, the plugin's assets, routing and
 * CSRF, as an application template ships them.
 */
class Application extends BaseApplication
{
    /**
     * @return void
     */
    public function bootstrap(): void
    {
        $this->addPlugin(UiPlugin::class);
    }

    /**
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The queue.
     * @return \Cake\Http\MiddlewareQueue
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        return $middlewareQueue
            ->add(new ErrorHandlerMiddleware([], $this))
            ->add(new AssetMiddleware())
            ->add(new RoutingMiddleware($this))
            ->add(new CsrfProtectionMiddleware());
    }

    /**
     * @param \Cake\Routing\RouteBuilder $routes The builder.
     * @return void
     */
    public function routes(RouteBuilder $routes): void
    {
        $routes->setRouteClass(DashedRoute::class);
        $routes->connect('/', ['controller' => 'Dashboard', 'action' => 'index']);
        $routes->fallbacks();
    }
}
