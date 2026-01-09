<?php
// /home/beni/projectku/kantor/api/core/Router.php

class Router {
    private $routes = [];

    public function add($method, $path, $handler) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path, // Regex pattern or exact string
            'handler' => $handler
        ];
    }

    public function dispatch($method, $uri) {
        // Strip query string and trim slashes
        $uriPath = parse_url($uri, PHP_URL_PATH);
        
        // Remove base path from URI if needed. Assuming /api is the base or mapped to root.
        // If hosted at /api/..., we need to be careful. 
        // Let's assume the RewriteRule sends everything to index.php or we parse intelligently.
        // For simplicity, we'll try to match the end of the string.
        
        foreach ($this->routes as $route) {
            if ($route['method'] === $method) {
                // regex match
                $pattern = "#^" . $route['path'] . "$#";
                if (preg_match($pattern, $uriPath, $matches)) {
                    array_shift($matches); // remove full match
                    
                    // Handler format: "Controller@method"
                    list($controllerName, $methodName) = explode('@', $route['handler']);
                    
                    require_once __DIR__ . '/../controllers/' . $controllerName . '.php';
                    $controller = new $controllerName();
                    
                    call_user_func_array([$controller, $methodName], $matches);
                    return;
                }
            }
        }

        Response::error("Endpoint Not Found", 404);
    }
}
