<?php 
	/**
	 * Router Class
	 * Handles route registration and matching
	 * 
	 * Register a route:
	 * router->get('/auth.)
	 */
	class Router {
		/**
		 * @var array
		 */
		protected $routes;

		/**
		 * Router constructor
		 */
		public function __construct() {
			$this->routes = [
				'GET'    => [],
				'POST'   => [],
				'PUT'    => [],
				'DELETE' => [],
				'PATCH'  => []
			];
		}

		/**
		 * Registers a GET route
		 * 
		 * @param string $path
		 * @param string $controller
		 * @param string $action
		 */
		public function get($path, $controller, $action) {
			$this->addRoute('GET', $path, $controller, $action);
		}

		/**
		 * Registers a POST route
		 * 
		 * @param string $path
		 * @param string $controller
		 * @param string $action
		 */
		public function post($path, $controller, $action) {
			$this->addRoute('POST', $path, $controller, $action);
		}

		/**
		 * Registers a PUT route
		 * 
		 * @param string $path
		 * @param string $controller
		 * @param string $action
		 */
		public function put($path, $controller, $action) {
			$this->addRoute('PUT', $path, $controller, $action);
		}

		/**
		 * Registers a DELETE route
		 * 
		 * @param string $path
		 * @param string $controller
		 * @param string $action
		 */
		public function delete($path, $controller, $action) {
			$this->addRoute('DELETE', $path, $controller, $action);
		}

		/**
		 * Registers a PATCH route
		 * 
		 * @param string $path
		 * @param string $controller
		 * @param string $action
		 */
		public function patch($path, $controller, $action) {
			$this->addRoute('PATCH', $path, $controller, $action);
		}

		/**
		 * Adds a route to the routes array
		 * 
		 * @param string $method
		 * @param string $path
		 * @param string $controller
		 * @param string $action
		 */
		private function addRoute($method, $path, $controller, $action) {
			$pattern 	= $this->pathToRegex($path);
			$keys 		= $this->extractParamKeys($path);

			array_push($this->routes[$method], [
				'path'       => $path,
				'pattern'    => $pattern,
				'keys'       => $keys,
				'controller' => $controller,
				'action'     => $action
			]);
		}

		/**
		 * Converts the path pattern to a regular expression
		 * /users/:userId => /^\/users\/([^\/]+)$/
		 * 
		 * @param string $path
		 * @return string
		 */
		private function pathToRegex($path) {
			$regexPath = preg_replace('/\//', '\\/', $path);
			$regexPath = preg_replace('/:(\w+)/', '([^\/]+)', $regexPath);

			return '/^' . $regexPath . '$/';
		}

		/**
		 * Extracts parameter keys from the path
		 * /users/:userId/orders/:orderId => ['userId', 'orderId']
		 * 
		 * @param string $path
		 * @return array
		 */
		private function extractParamKeys($path) {
			$keys = [];
			preg_match_all('/:(\w+)/', $path, $matches);
			
			if (!empty($matches[1])) {
				$keys = $matches[1];
			}

			return $keys;
		}

		/**
		 * Matches the request pathname to a route
		 * 
		 * @param string $method
		 * @param string $pathname
		 * @return array|null
		 */
		public function match($method, $pathname) {
			$routes = isset($this->routes[$method]) ? $this->routes[$method] : [];

			foreach ($routes as $route) {
				if (preg_match($route['pattern'], $pathname, $matches)) {
					$params = [];

					for ($i = 0; $i < count($route['keys']); $i++) {
						$params[$route['keys'][$i]] = $matches[$i + 1];
					}

					return [
						'controller' => $route['controller'],
						'action'     => $route['action'],
						'params'     => $params
					];
				}
			}

			return null;
		}
	}
