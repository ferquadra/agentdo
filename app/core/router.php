<?php
class Router
{
    private $routes = array();

    public function get($path, $handler)
    {
        $this->map('GET', $path, $handler);
    }

    public function post($path, $handler)
    {
        $this->map('POST', $path, $handler);
    }

    public function dispatch(Request $request)
    {
        $path = $request->path();
        $method = $request->method();
        if ($method === 'HEAD') {
            $method = 'GET';
        }

        for ($i = 0; $i < count($this->routes); $i++) {
            $route = $this->routes[$i];
            if ($route['method'] !== $method) {
                continue;
            }
            $params = $this->match($route['path'], $path);
            if ($params !== false) {
                $this->call($route['handler'], $request, $params);
                return;
            }
        }

        http_response_code(404);
        View::render('home/404', array(
            'title' => 'No encontrado',
            'page' => '404',
        ));
    }

    private function map($method, $path, $handler)
    {
        if ($path !== '/') {
            $path = '/' . trim($path, '/');
        }
        $this->routes[] = array(
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
        );
    }

    private function match($pattern, $path)
    {
        if ($pattern === $path) {
            return array();
        }

        $parts = preg_split('#(\{[a-z]+\})#', $pattern, -1, PREG_SPLIT_DELIM_CAPTURE);
        $regex = '';
        for ($i = 0; $i < count($parts); $i++) {
            $part = $parts[$i];
            if ($part === '') {
                continue;
            }
            if (preg_match('#^\{([a-z]+)\}$#', $part, $m)) {
                $regex .= '(?P<' . $m[1] . '>[a-z0-9]+)';
            } else {
                $regex .= preg_quote($part, '#');
            }
        }
        $regex = '#^' . $regex . '$#';
        if (!preg_match($regex, $path, $m)) {
            return false;
        }
        $params = array();
        foreach ($m as $k => $v) {
            if (!is_int($k)) {
                $params[$k] = $v;
            }
        }
        return $params;
    }

    private function call($handler, Request $request, $params)
    {
        $parts = explode('@', $handler);
        $class = $parts[0];
        $action = $parts[1];
        $controller = new $class();
        $controller->$action($request, $params);
    }
}
