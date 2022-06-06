<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library;

use Tripsy\Library\Exceptions\ConfigException;
use Tripsy\Library\Standard\ObjectTools;
use Tripsy\Library\Standard\StringTools;

class Routing
{
    private Config $cfg;

    private array $routes = [];
    private string $site_pattern;

    private const DEFAULT_CONTROLLER = 'Base';
    private const DEFAULT_METHOD = 'page404';
    private const REQUEST_METHODS = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];

    private string $controller;
    private string $method;
    private string $permission = '';
    private array $param = [];

    /**
     * @param Config $cfg
     * @throws ConfigException
     * @throws ConfigException
     */
    public function __construct(Config $cfg)
    {
        $this->cfg = $cfg;

        $this->site_pattern = $this->cfg->get('site.pattern');

        $route_files = glob($this->cfg->get('path.route') . '/*.php');

        foreach ($route_files as $file_path) {
            $file_name = pathinfo($file_path, PATHINFO_FILENAME);
            $file_data = require $file_path;

            $this->import($file_name, $file_data);
        }
    }

    /**
     * @param string $pattern
     * @param array $routes
     * @return void
     * @throws ConfigException
     * @throws ConfigException
     */
    public function import(string $pattern, array $routes): void
    {
        foreach ($routes as $name => $data) {
            if (empty($data['url']) === true) {
                throw new ConfigException('Route key `url` is not set (eg: ' . $name . ')');
            }

            if (empty($data['match']) === true) {
                throw new ConfigException('Route key `match` is not set (eg: ' . $name . ')');
            }

            $routeData = [];
            $routeData['url'] = $data['url'];
            $routeData['match'] = $data['match'];

            if (empty($data['controller']) === false) {
                $routeData['controller'] = $data['controller'];
            }

            if (empty($data['method']) === false) {
                $routeData['method'] = $data['method'];
            }

            if (empty($data['permission']) === false) {
                $routeData['permission'] = $data['permission'];
            }

            if (empty($data['request']) === false) {
                $routeData['request'] = array_map('trim', explode(',', $data['request']));
            }

            if (empty($data['param']) === false) {
                $routeData['param'] = array_map('trim', explode(',', $data['param']));
            }

            $this->add($pattern, $name, $routeData);
        }
    }

    /**
     * @param string $pattern
     * @param string $name
     * @param array $data
     * @return void
     * @throws ConfigException
     */
    public function add(string $pattern, string $name, array $data): void
    {
        $data = ObjectTools::data($data, [
            'url' => 'string',
            'match' => 'string',
            'controller' => '?string',
            'method' => '?string',
            'permission' => '?string',
            'request' => '?array',
            'param' => '?array',
        ]);

        $request = $data->get('request', ['GET']);
        $request = array_intersect($request, self::REQUEST_METHODS);

        $data->put('request', $request);

        $this->routes[$pattern][$name] = $data;
    }

    /**
     * @param string $pattern
     * @return string
     */
    private function getNamespace(string $pattern): string
    {
        return $this->cfg->get('pattern.' . $pattern . '.namespace') . '\\';
    }

    /**
     * @param string $string
     * @param string $namespace
     * @return void
     * @throws ConfigException
     */
    private function setController(string $string, string $namespace): void
    {
        $string = implode('', array_map('ucfirst', explode('_', $string)));

        $this->controller = $namespace . $string;

        if (class_exists($this->controller, true) === false) {
            throw new ConfigException('Controller class not found (eg: ' . $this->controller . ')');
        }
    }

    /**
     * @return string
     */
    public function getController(): string
    {
        return $this->controller;
    }

    /**
     * @param string $string
     * @return void
     * @throws ConfigException
     */
    private function setMethod(string $string): void
    {
        $this->method = $string;

        if (method_exists($this->getController(), $this->method) === false) {
            throw new ConfigException('Method not found (eg: ' . $this->getController() . ' -> ' . $this->method . ')');
        }
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return void
     */
    private function setPermission(): void
    {
        $this->permission = implode('.', func_get_args());
    }

    /**
     * @return string
     */
    public function getPermission(): string
    {
        return $this->permission;
    }

    /**
     * @param string $key
     * @param string $value
     * @return void
     */
    private function addParam(string $key, string $value)
    {
        $this->param[$key] = $value;
    }

    /**
     * @return array
     */
    public function getParam(): array
    {
        return $this->param;
    }

    /**
     * Return true if route is found
     *
     * @param string $request_uri
     * @return void
     * @throws ConfigException
     */
    public function resolve(string $request_uri): void
    {
        $route_key = $this->getRouteKey($this->site_pattern);

        if (empty($this->routes[$route_key])) {
            throw new ConfigException('There are not routes defined for pattern `' . $route_key . '`');
        }

        $request_method = var_server('REQUEST_METHOD');

        foreach ($this->routes[$route_key] as $route) {
            if (preg_match('/^' . $route->get('match') . '/', $request_uri, $match)) {

                if (empty($route->get('request')) === false) {
                    if (in_array($request_method, $route->get('request')) === false) {
                        continue;
                    }
                }

                if (empty($match['controller']) === false) {
                    $route->put('controller', $match['controller']);
                }

                if (empty($match['method']) === false) {
                    $route->put('method', $match['method']);
                }

                if ($route->get('permission')) {
                    $route->put('permission', StringTools::interpolate($route->get('permission'), [
                        'controller' => $route->get('controller'),
                        'method' => $route->get('method'),
                    ]));
                }

                $this->setController($route->get('controller'), $this->getNamespace($this->site_pattern));
                $this->setMethod($route->get('method'));
                $this->setPermission($route->get('permission', ''));

                if ($route->get('param')) {
                    $route_params = $route->get('param');

                    foreach ($route_params as $key) {
                        $this->addParam($key, $match[$key] ?? '');
                    }
                }

                return;
            }
        }

        $this->setController(self::DEFAULT_CONTROLLER, $this->getNamespace($this->site_pattern));
        $this->setMethod(self::DEFAULT_METHOD);
    }

    /**
     * @param string $pattern
     * @return string
     */
    public function module_link(string $pattern = ''): string
    {
        $pattern = $pattern ?: $this->cfg->get('site.pattern');

        return $this->cfg->get('link.site') . $this->cfg->get('pattern.' . $pattern . '.uri');
    }

    /**
     * @param string $route
     * @param array $data
     * @return string
     * @throws ConfigException
     */
    public function link(string $route, array $data = []): string
    {
        $has_pattern = false;

        if (str_contains($route, '.')) {
            list($pattern, $route) = explode('.', $route);

            if ($pattern == 'default') {
                $pattern = $this->cfg->get('default.pattern');
            }

            $has_pattern = true;
        } else {
            $pattern = $this->site_pattern;
        }

        $route_key = $this->getRouteKey($pattern);

        if (empty($this->routes[$route_key][$route])) {
            if ($has_pattern === false) {
                $pattern = $this->cfg->get('default.pattern');
            }
        }

        $route_key = $this->getRouteKey($pattern);

        if (empty($this->routes[$route_key][$route])) {
            throw new ConfigException('Route not defined (eg: ' . $route_key . '.' . $route . ')');
        }

        $data = array_merge($data, array(
            'module_link' => $this->module_link($pattern),
        ));

        return StringTools::interpolate($this->routes[$route_key][$route]['url'], $data);
    }

    /**
     * @param string $pattern
     * @return string
     */
    private function getRouteKey(string $pattern): string
    {
        return $pattern;
    }
}
