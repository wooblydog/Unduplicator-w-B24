<?php
namespace App;

class Router
{
    private array $routes = [];

    public function addRoute(string $method, string $pattern, callable $handler): void
    {
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'handler' => $handler
        ];
    }

    public function dispatch(string $method, string $uri)
    {
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['pattern'], $uri, $matches)) {
                array_shift($matches);
                return call_user_func_array($route['handler'], $matches);
            }
            if ($route['pattern'] === null) {
                throw new \Exception ('Not found', 404);
            }
            if ($route['method'] === "POST") {
                throw new \Exception ('Method not allowed', 405);
            }
        }
        throw new \Exception ('Not found', 404);
    }
}

