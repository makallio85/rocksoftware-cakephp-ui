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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RockSoftware\Settings\SettingsPlugin;
use RockSoftware\Ui\UiPlugin;

/**
 * A consuming application: an error handler, the plugin's assets, routing and
 * CSRF, as an application template ships them, behind a strict
 * Content-Security-Policy (no inline script, no inline style), so a test and
 * the sandbox fail when the frame or a component needs either.
 */
class Application extends BaseApplication
{
    /** The policy every response carries. */
    public const CONTENT_SECURITY_POLICY = "default-src 'self'; script-src 'self'; style-src 'self'; "
        . "img-src 'self' data:; font-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'";

    /**
     * @return void
     */
    public function bootstrap(): void
    {
        $this->addPlugin(SettingsPlugin::class);
        $this->addPlugin(UiPlugin::class);
    }

    /**
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The queue.
     * @return \Cake\Http\MiddlewareQueue
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        return $middlewareQueue
            ->add(function (ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
                return $handler->handle($request)
                    ->withHeader('Content-Security-Policy', self::CONTENT_SECURITY_POLICY);
            })
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
