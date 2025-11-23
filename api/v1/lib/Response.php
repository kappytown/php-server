<?php
	/**
	 * Response Class
	 * 
	 * Provides a clean interface for sending HTTP responses with proper
	 * headers, status codes, and consistent JSON formatting.
	 */
	class Response {
		
		/**
		 * @var int HTTP status code
		 */
		private $statusCode;
		
		/**
		 * @var array Response headers
		 */
		private $headers;
		
		/**
		 * @var bool Whether headers have been sent
		 */
		private $headersSent;

		/**
		 * Constructor - initializes response with CORS headers
		 */
		public function __construct() {
			$this->statusCode = 200;
			$this->headers = [
				'Content-Type' => 'application/json'
			];
			$this->headersSent = false;
			$this->_setCorsHeaders();
		}

		/**
		 * Set CORS headers to allow cross-origin requests
		 * 
		 */
		private function _setCorsHeaders() {
			// $this->setHeader('Access-Control-Allow-Credentials', 'true');
			$this->setHeader('Access-Control-Allow-Origin', '*');
			$this->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
			$this->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
		}

		/**
		 * Sets the http status code
		 * 
		 * @param int $code
		 * @return Response
		 */
		public function status($code) {
			$this->statusCode = $code;
			return $this;
		}

		/**
		 * Set a single header
		 * 
		 * @param string $name
		 * @param string $value
		 * @return Response
		 */
		public function setHeader($name, $value) {
			$this->headers[$name] = $value;
			return $this;
		}

		/**
		 * Set multiple headers
		 * 
		 * @param array $headers
		 * @return Response
		 */
		public function setHeaders($headers) {
			$this->headers = array_merge($this->headers, $headers);
			return $this;
		}

		/**
		 * Send headers
		 * 
		 */
		private function sendHeaders() {
			if ($this->headersSent || headers_sent()) {
				return;
			}

			http_response_code($this->statusCode);
			
			foreach ($this->headers as $name => $value) {
				header("$name: $value");
			}
			
			$this->headersSent = true;
		}

		/**
		 * Send JSON response
		 * 
		 * @param mixed $data
		 */
		public function json($data) {
			$this->setHeader('Content-Type', 'application/json');
			$this->send(json_encode($data));
		}

		/**
		 * Send plain text response
		 * 
		 * @param string $data
		 */
		public function text($data) {
			$this->setHeader('Content-Type', 'text/plain');
			$this->send($data);
		}

		/**
		 * Send HTML response
		 * 
		 * @param string $data
		 */
		public function html($data) {
			$this->setHeader('Content-Type', 'text/html');
			$this->send($data);
		}

		/**
		 * Send response
		 * 
		 * @param mixed $data
		 */
		public function send($data) {
			$this->sendHeaders();
			echo $data;
			exit;
		}

		/**
		 * Set a cookie
		 * 
		 * @param string $name
		 * @param string $value
		 * @param array $options
		 * @return Response
		 */
		public function cookie($name, $value, $options = []) {
			$defaults = [
				'path' 		=> '/',
				'domain' 	=> '',
				'httpOnly' 	=> true,
				'expires' 	=> 0,
				'maxAge' 	=> null,
				'secure' 	=> false,
				'sameSite' 	=> 'Lax'
			];

			$opts = array_merge($defaults, $options);

			// Calculate expiration time
			$expires = $opts['expires'];
			if ($opts['maxAge'] !== null) {
				$expires = time() + $opts['maxAge'];
			}

			// Use setcookie for proper cookie handling
			setcookie(
				$name,
				$value,
				$expires,
				$opts['path'],
				$opts['domain'],
				$opts['secure'],
				$opts['httpOnly']
			);

			return $this;
		}

		/**
		 * Clear a cookie
		 * 
		 * @param string $name
		 * @return Response
		 */
		public function clearCookie($name) {
			return $this->cookie($name, '', [ 'expires' => time() - 3600 ]);
		}

		/**
		 * Redirect to URL
		 * 
		 * @param string $url
		 * @param int $statusCode
		 */
		public function redirect($url, $statusCode = 302) {
			$this->status($statusCode)
				->setHeader('Location', $url)
				->send('');
		}

		/**
		 * Send success response
		 * 
		 * @param mixed $data
		 * @param string $message
		 */
		public function success($data, $message = '') {
			return $this->json([
				'status' 	=> 200,
				'success' 	=> true,
				'message' 	=> $message,
				'data' 		=> $data
			]);
		}

		/**
		 * Send error response
		 * 
		 * @param string $message
		 * @param int $statusCode
		 * @param mixed $details
		 */
		public function error($message, $statusCode = 400, $details = null) {
			$this->status($statusCode);
			$response = [
				'status' 	=> $statusCode,
				'success' 	=> false,
				'error' 	=> $message
			];
			
			if ($details !== null) {
				$response['details'] = $details;
			}
			
			$this->json($response);
		}

		/**
		 * Send validation error response
		 * 
		 * @param array $errors
		 */
		public function validationError($errors) {
			$this->error('Validation failed', 422, $errors);
		}

		/**
		 * Send unauthorized response
		 * 
		 * @param string $message
		 */
		public function unauthorized($message = 'Unauthorized') {
			$this->error($message, 401);
		}

		/**
		 * Send forbidden response
		 * 
		 * @param string $message
		 */
		public function forbidden($message = 'Forbidden') {
			$this->error($message, 403);
		}

		/**
		 * Send not found response
		 * 
		 * @param string $message
		 */
		public function notFound($message = 'Resource not found') {
			$this->error($message, 404);
		}
	}
