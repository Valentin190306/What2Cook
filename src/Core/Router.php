<?php

namespace App\Core;

class Router
{
    protected array $routes = [];

    public function add(string $method, string $path, string $handler): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }

    public function dispatch(string $uri, string $method): void
    {
        $uri = parse_url($uri, PHP_URL_PATH);
        
        foreach ($this->routes as $route) {
            $pattern = $this->convertPathToRegex($route['path']);
            if ($route['method'] === $method && preg_match($pattern, $uri, $matches)) {
                $this->executeHandler($route['handler'], array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));
                return;
            }
        }

        $this->abort(404);
    }

    protected function convertPathToRegex(string $path): string
    {
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-zA-Z0-9_-]+)', $path);
        return "#^" . $pattern . "$#";
    }

    protected function executeHandler(string $handler, array $params): void
    {
        [$controllerName, $method] = explode('@', $handler);
        $controllerClass = "App\\Controllers\\" . $controllerName;

        if (class_exists($controllerClass)) {
            $controller = new $controllerClass();
            if (method_exists($controller, $method)) {
                call_user_func_array([$controller, $method], $params);
            } else {
                $this->abort(500, "Method $method not found in $controllerClass");
            }
        } else {
            $this->abort(500, "Controller $controllerClass not found");
        }
    }

    protected function abort(int $code, string $message = ''): void
    {
        http_response_code($code);
        if ($message) {
            echo json_encode(['error' => $message]);
        } else {
            echo json_encode(['error' => "Error $code"]);
        }
        exit;
    }
}
