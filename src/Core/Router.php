<?php

namespace App\Core;

use App\Core\Log\LoggerInterface;

class Router
{
    protected array $routes = [];
    protected ?LoggerInterface $logger = null;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

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
        
        // Normalizar URI: quitar barra final si no es la raíz
        if ($uri !== '/' && str_ends_with($uri, '/')) {
            $uri = rtrim($uri, '/');
        }
        
        foreach ($this->routes as $route) {
            $pattern = $this->convertPathToRegex($route['path']);
            if ($route['method'] === $method && preg_match($pattern, $uri, $matches)) {
                $this->executeHandler($route['handler'], array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));
                return;
            }
        }

        $this->abort(404, "Página no encontrada: $uri");
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

        if (!class_exists($controllerClass)) {
            throw new \Exception("Controller $controllerClass not found");
        }

        $controller = new $controllerClass();

        if ($this->logger !== null && $controller instanceof \App\Core\Controller) {
            $controller->setLogger($this->logger);
        }

        if (!method_exists($controller, $method)) {
            throw new \Exception("Method $method not found in $controllerClass");
        }

        call_user_func_array([$controller, $method], $params);
    }

    protected function abort(int $code, string $message = ''): void
    {
        if ($this->logger) {
            $this->logger->error("[Router] {$message}", [
                'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
            ], $code);
        }

        if ($code === 404) {
            $controller = new \App\Controllers\ErrorController();
            $controller->notFound($message);
            return;
        }

        http_response_code($code);
        if ($message) {
            echo json_encode(['error' => $message]);
        } else {
            echo json_encode(['error' => "Error $code"]);
        }
        exit;
    }
}
