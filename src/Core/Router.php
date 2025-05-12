<?php
namespace App\Core;

class Router
{
    public function dispatch(): void
    {
        $route = $_GET['q'] ?? 'home/index';
        [$controllerName, $action] = array_pad(explode('/', $route, 2), 2, 'index');

        $class = 'App\\Controller\\' . ucfirst($controllerName) . 'Controller';
        if (!class_exists($class)) {
            http_response_code(404);
            echo 'Controller not found';
            return;
        }
        $controller = new $class();
        if (!method_exists($controller, $action)) {
            http_response_code(404);
            echo 'Action not found';
            return;
        }
        $controller->$action();
    }
}
