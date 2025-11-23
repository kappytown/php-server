<?php

    /**
     * Main Server File
     * 
     * This file initializes the application, sets up the database connection,
     * and routes incoming requests to the appropriate controllers.
     */
    require_once __DIR__ . '/constants.php';
    require_once __DIR__ . '/conf/bootstrap.php';
    require_once __DIR__ . '/lib/Router.php';
    require_once __DIR__ . '/lib/Request.php';
    require_once __DIR__ . '/lib/Response.php';
    require_once __DIR__ . '/exceptions/ApiException.php';
    require_once __DIR__ . '/database/DatabaseFactory.php';

    class Server {
        
        /**
         * @var Router
         */
        private $router;
        private $config;

        /**
         * Server constructor
         * 
         */
        public function __construct() {
            $this->router = new Router();
            $this->setupRoutes();
            $this->config = $this->loadConfig();
        }

        /**
         * Loads the configuration file which contains database configuration
         * 
         */
        private function loadConfig() {
            return parse_ini_file(realpath(SERVER_DIR . 'api/v1/config.ini'));
        }

        /**
         * Setup all server routes
         */
        private function setupRoutes() {
            // Session (User) Routes
            $this->router->get(API_PATH . '/auth/session', 'UserController', 'session');
            $this->router->delete(API_PATH . '/auth/session', 'UserController', 'session');

            // Auth (User) Routes
            $this->router->post(API_PATH . '/auth/login', 'UserController', 'login');
            $this->router->post(API_PATH . '/auth/logout', 'UserController', 'logout');

            // User Routes
            $this->router->post(API_PATH . '/user', 'UserController', 'create');
            $this->router->get(API_PATH . '/user/:userId', 'UserController', 'read');
            $this->router->put(API_PATH . '/user/:userId', 'UserController', 'update');
            $this->router->delete(API_PATH . '/user/:userId', 'UserController', 'delete');
            $this->router->post(API_PATH . '/user/sendMail', 'UserController', 'sendMail');

            // Product Routes
            $this->router->get(API_PATH . '/products/categories', 'ProductsController', 'readCategories');
            $this->router->get(API_PATH . '/products/category/:name', 'ProductsController', 'readCategory');
            $this->router->get(API_PATH . '/products', 'ProductsController', 'readAll');
            $this->router->get(API_PATH . '/products/:id', 'ProductsController', 'read');
            $this->router->put(API_PATH . '/products/:id', 'ProductsController', 'update');
            $this->router->delete(API_PATH . '/products/:id', 'ProductsController', 'delete');

            // Order Routes
            $this->router->get(API_PATH . '/orders/statuses', 'OrdersController', 'readStatuses');
            $this->router->get(API_PATH . '/orders/status/:name', 'OrdersController', 'readStatus');
            $this->router->get(API_PATH . '/orders', 'OrdersController', 'readAll');
            $this->router->get(API_PATH . '/orders/:id', 'OrdersController', 'read');

            // Report Routes
            $this->router->get(API_PATH . '/report/:reportId', 'ReportController', 'index');
        }

        /**
         * Parses the request to find the matching route and instantiate it
         * 
         */
        public function handleRequest() {
            // Get request information
            $requestUri = $_SERVER['REQUEST_URI'];
            $method     = $_SERVER['REQUEST_METHOD'];
            
            // Parse URL
            $parsedUrl  = parse_url($requestUri);
            $pathname   = $parsedUrl['path'];

            // Create request and response objects
            $request    = new Request();
            $response   = new Response();

            try {
                // Parse the request body
                $request->parseBody();
                
                // Find matching route
                $route = $this->router->match($method, $pathname);

                if (!$route) {
                    $response->status(404)->json([
                        'error' => 'Route not found',
                        'path'  => $pathname
                    ]);
                    return;
                }

                // Get the database instance to inject in the controller class
                $db = DatabaseFactory::getInstance('mysql', $this->config);

                // Dynamically load the controller
                $controllerFile = CONTROLLERS_DIR . $route['controller'] . '.php';
                
                // Check if the controller exists
                if (!file_exists($controllerFile)) {
                    $response->status(500)->json(['error' => 'Controller file not found']);
                    return;
                }

                require_once $controllerFile;
                
                $controllerClass    = $route['controller'];
                $controller         = new $controllerClass($db, $request, $response);

                // Check if method exists
                if (!method_exists($controller, $route['action'])) {
                    $response->status(500)->json(['error' => 'Controller action not found']);
                    return;
                }

                // Set route parameters
                $request->setParams($route['params']);

                // Execute controller action
                $controller->{$route['action']}();

            } catch (ApiException $e) {
                // Handle ApiException instances with proper status codes
                error_log('API Error: ' . $e->getMessage());
                
                $errorResponse = [
                    'error'     => $e->getName(),
                    'status'    => $e->getCode() ?: 500
                ];

                // Add message in development mode
                if (DEVELOPMENT_ENVIRONMENT === true) {
                    $errorResponse['message'] = $e->getMessage();
                }

                $response->status($e->getCode())->json($errorResponse);

            } catch (Exception $e) {
                // Handle unexpected errors
                error_log('Server Error: ' . $e->getMessage());
                
                $errorResponse = [
                    'error'     => 'Internal Server Error',
                    'status'    => 500
                ];

                // Add message in development mode
                if (DEVELOPMENT_ENVIRONMENT === true) {
                    $errorResponse['message'] = $e->getMessage();
                }

                $response->status(500)->json($errorResponse);
            
            } catch (Error $e) {
                error_log('Unknow Error: ' . $e->getMessage());

                $response->status(500)->json([
                    'error'     => 'Internal Server Error',
                    'status'    => 500
                ]);
            }
        }

        /**
         * Start the application (handle the current request)
         * 
         */
        public function start() {
            $this->handleRequest();
        }
    }

    // Auto-start the server when this file is accessed
    $app = new Server();
    $app->start();
