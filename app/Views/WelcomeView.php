<?php

declare(strict_types=1);

namespace App\Views;

use Pype\Http\Request;
use Pype\Routing\Router;
use Pype\View\ViewOutlet;

/** The home page: lists every route with its stages. A page outlet can ask for services. */
final class WelcomeView extends ViewOutlet
{
    public function __construct(private readonly Router $router)
    {
    }

    protected function template(): string
    {
        return 'welcome';
    }

    protected function data(mixed $data, Request $request): array
    {
        return ['routes' => $this->router->routes()];
    }
}
