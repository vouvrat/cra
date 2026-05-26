<?php namespace Core;

class Router {
    private array $routes = [];

    public function get(string $path, string $handler): void  { $this->add('GET',  $path, $handler); }
    public function post(string $path, string $handler): void { $this->add('POST', $path, $handler); }

    private function add(string $method, string $path, string $handler): void {
        $pattern = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $path) . '$#';
        $this->routes[] = compact('method', 'pattern', 'handler');
    }

    public function dispatch(): void {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = trim($_GET['route'] ?? '', '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;
            if (!preg_match($route['pattern'], $uri, $m)) continue;

            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            [$class, $action] = explode('@', $route['handler']);
            $ctrl = 'Controllers\\' . $class;
            (new $ctrl())->$action(...array_values($params));
            return;
        }

        http_response_code(404);
        echo '<h1>404</h1>';
    }
}
