<?php

/**
 * A simple URL router (borrowed from other private projects)
 */
class Router {
        const ROUTE_METHOD = 0;
        const ROUTE_PATH = 1;
        const ROUTE_CALLBACK = 2;

        protected $basePath;
        protected $routes = [];

        /**
         * Constructs the router, with an optional basePath to remove from the routed paths
         */
        public function __construct($path = null) {
                $this->basePath = $path;
        }

        /**
         * Alias to new Router(...)
         */
        public static function new() {
                return new static(...func_get_args());
        }


        /**
         * Dispatches a request, optionally taking the $path to dispatch to instead of REQUEST_URI
         */
        public function dispatch($path = null, $method = null) {
                if($path === null) {
                        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
                }

                if($this->basePath !== null) {
                        if(strpos($path, $this->basePath) !== 0) {
                                return false;
                        }

                        $path = substr($path, strlen($this->basePath));
                }

                $method = strtoupper($method ?: $_SERVER['REQUEST_METHOD']);

                foreach($this->routes as $r) {
                        $rmethod = $r[self::ROUTE_METHOD];
                        $params = [];

                        if($rmethod === '*' || in_array($method, $rmethod)) {
                                $match = preg_match_all($r[self::ROUTE_PATH], $path, $params, PREG_SET_ORDER);

                                if($match === false) {
                                        throw new \Exception('Regex failed for route '.$path.': '.preg_last_error(), 500);
                                }
                                else if($match > 0) {
                                        array_splice($params[0], 0, 1, $rmethod === '*' ? [$method] : null);

                                        if(call_user_func_array($r[self::ROUTE_CALLBACK], $params[0]) !== false) {
                                                return true;
                                        }
                                }
                        }
                }

                return false;
        }


        /**
         * Adds a route that dispatches a whole path to another function, supplied with a new router to start the
         * dispatching process again.
         * 
         * @param string $path Same regex as Router::map
         * @param callable $callback A callback function that will be called with a router as the first argument, and extra
         *                           capture blocks as its arguments. Cancels the dispatching if FALSE is returned.
         */
        public function subRouter($path, $callback) {
                return $this->map('*', $path, function($method, $subpath) use($callback) {
                        $router = new Router();
                        $args = func_get_args();
                        array_splice($args, 0, 2, [$router]);

                        if(call_user_func_array($callback, $args) === false) {
                                return false;
                        }
                        else {
                                return $router->dispatch($subpath, $method);
                        }
                });
        }


        /**
         * Adds a route.
         * 
         * @param mixed $method The name of an HTTP method ('GET'), an array of HTTP methods, or '*' to catch all requests
         * @param string $path A regex that matches the path, without the delimiters
         * @param callable $callback Callback function supported by call_user_func_array(), with all captured blocks passed-
         *                           in as function arguments. Cancels the dispatching if FALSE is returned.
         */
        public function map($method, $path, $callback) {
                if($method === '*') {}
                else if(is_string($method)) {
                        $method = [strtoupper($method)];
                }
                else {
                        $method = array_map('strtoupper', $method);
                }

                $this->routes[] = [$method, '`'.$path.'`', $callback];

                return $this;
        }


        /**
         * Adds multiple routes
         * 
         * @see Router::map For the order of the arguments.
         */
        public function mapMany(array $routes) {
                foreach($routes as $r) {
                        call_user_func_array([$this, 'map'], $r);
                }
        }


        /**
         * Matches any HTTP verb
         * @see Router::map
         */
        public function any($path, $callback) {
                return $this->map('*', $path, $callback);
        }

        /**
         * Matches GET requests
         * @see Router::map
         */
        public function get($path, $callback) {
                return $this->map(['GET'], $path, $callback);
        }

        /**
         * Matches POST requests
         * @see Router::map
         */
        public function post($path, $callback) {
                return $this->map(['POST'], $path, $callback);
        }

        /**
         * Matches PUT requests
         * @see Router::map
         */
        public function put($path, $callback) {
                return $this->map(['PUT'], $path, $callback);
        }

        /**
         * Matches PATCH requests
         * @see Router::map
         */
        public function patch($path, $callback) {
                return $this->map(['PATCH'], $path, $callback);
        }

        /**
         * Matches DELETE requests
         * @see Router::map
         */
        public function delete($path, $callback) {
                return $this->map(['DELETE'], $path, $callback);
        }
}
