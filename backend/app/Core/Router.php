<?php
namespace App\Core;

class Router {
    private $routes = [];

    public function get($uri, $controller) {
        $this->addRoute('GET', $uri, $controller);
    }

    public function post($uri, $controller) {
        $this->addRoute('POST', $uri, $controller);
    }

    public function patch($uri, $controller) {
        $this->addRoute('PATCH', $uri, $controller);
    }

    public function delete($uri, $controller) {
        $this->addRoute('DELETE', $uri, $controller);
    }

    private function addRoute($method, $uri, $controller) {
        // Convert route params {id} to regex
        $uri = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[a-zA-Z0-9_-]+)', $uri);
        $uri = '#^' . $uri . '$#';
        
        $this->routes[] = [
            'method' => $method,
            'uri' => $uri,
            'controller' => $controller
        ];
    }

    public function dispatch($requestMethod, $requestUri) {
        foreach ($this->routes as $route) {
            if ($route['method'] === $requestMethod && preg_match($route['uri'], $requestUri, $matches)) {
                $params = [];
                foreach ($matches as $key => $match) {
                    if (is_string($key)) {
                        $params[$key] = $match;
                    }
                }
                
                list($class, $method) = explode('@', $route['controller']);
                $class = "App\\Controllers\\" . $class;
                
                if (class_exists($class)) {
                    $controllerInstance = new $class();
                    if (method_exists($controllerInstance, $method)) {
                        call_user_func_array([$controllerInstance, $method], $params);
                        return;
                    }
                }
            }
        }

        // 404 Not Found
        http_response_code(404);
        echo json_encode(['error' => 'Not Found', 'message' => 'The requested endpoint does not exist.']);
    }
}
