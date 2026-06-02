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

    /**
     * @throws \Exception
     */
    public function dispatch(string $method, string $uri, $request)
    {
        $uri = parse_url($uri, PHP_URL_PATH);

        $apiPos = strpos($uri, '/api/');
        if ($apiPos !== false) {
            $uri = substr($uri, $apiPos);
        }

        $pathMatched = false;

        foreach ($this->routes as $route) {
            $pattern = '#^' . $route['pattern'] . '$#';

            if (preg_match($pattern, $uri, $matches)) {
                $pathMatched = true;

                if ($route['method'] === $method) {
                    array_shift($matches);
                    return call_user_func_array($route['handler'], [$request, ...$matches]);
                }
            }
        }

        if ($pathMatched) {
            throw new \Exception('Method not allowed', 405);
        }

        throw new \Exception("Not found", 404);
    }
}

