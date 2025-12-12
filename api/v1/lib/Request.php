<?php

	require_once __DIR__ . '/InputSanitizer.php';

	/**
	 * Request Class
	 * 
	 * Handles parsing and validation of incoming HTTP requests.
	 * Provides convenient methods to access request data from various sources
	 * (URL parameters, query strings, request body, headers).
	 */
	class Request {
		
		/**
		 * @var string Request method
		 */
		public $method;
		
		/**
		 * @var string Request URI
		 */
		public $url;
		
		/**
		 * @var array Request headers
		 */
		public $headers;
		
		/**
		 * @var array Query string parameters
		 */
		public $query;
		
		/**
		 * @var mixed Request body data
		 */
		public $body;
		
		/**
		 * @var array Route parameters
		 */
		public $params;

		/**
		 * Constructor - initializes request parsing
		 */
		public function __construct() {
			$this->method 	= $_SERVER['REQUEST_METHOD'];
			$this->url 		= $_SERVER['REQUEST_URI'];
			$this->headers 	= $this->getHeaders();
			$this->query 	= $_GET;
			$this->body 	= [];
			$this->params 	= [];
		}

		/**
		 * Get all request headers
		 * 
		 * @return array
		 */
		private function getHeaders() {
			$headers = [];
			
			foreach ($_SERVER as $key => $value) {
				if (strpos($key, 'HTTP_') === 0) {
					$header = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
					$headers[strtolower($header)] = $value;
				}
			}
			
			// Add content-type if present
			if (isset($_SERVER['CONTENT_TYPE'])) {
				$headers['content-type'] = $_SERVER['CONTENT_TYPE'];
			}
			
			return $headers;
		}

		/**
		 * Parse request body
		 * 
		 * @throws Exception
		 */
		public function parseBody() {
			if (in_array($this->method, ['GET', 'DELETE'])) {
				return;
			}

			$rawBody = file_get_contents('php://input');
			
			if (strlen($rawBody) > UPLOAD_MAX_SIZE) {
				throw new Exception('Request body too large');
			}

			$contentType = isset($this->headers['content-type']) ? $this->headers['content-type'] : '';

			if (strpos($contentType, 'application/json') !== false) {
				$this->body = $rawBody ? json_decode($rawBody, true) : [];
				
				if (json_last_error() !== JSON_ERROR_NONE) {
					throw new Exception('Invalid JSON in request body');
				}

			} elseif (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
				parse_str($rawBody, $this->body);

			} elseif (strpos($contentType, 'multipart/form-data') !== false) {
				$this->body = $_POST;

			} else {
				$this->body = $rawBody;
			}
		}

		/**
		 * Sets the endpoint params ({ userId: 11 })
		 * 
		 * @param array $params
		 */
		public function setParams($params) {
			$this->params = $params ?: [];
		}

		/**
		 * Get input value from specified source
		 * 
		 * @param string $key
		 * @param mixed $defaultValue
		 * @param string $source
		 * @return mixed sanitized value
		 */
		public function input($key, $defaultValue = null, $source = 'body') {
			$value = null;

			switch($source) {
				case 'query':
					$value = isset($this->query[$key]) ? $this->query[$key] : null;
					break;
				case 'params':
					$value = isset($this->params[$key]) ? $this->params[$key] : null;
					break;
				case 'body':
				default:
					$value = isset($this->body[$key]) ? $this->body[$key] : null;
					break;
			}

			if ($value === null) {
				return $defaultValue;
			}

			return $this->sanitize($value);
		}

		/**
		 * Get sanitized input with type validation
		 * 
		 * @param string $key The key to look up in the query, params, and body objects
		 * @param mixed $defaultValue The default value to return if the specified key is not found
		 * @param string $type Type of sanitization to perform such as string, integer, etc.
		 * @param array $options Sanitization options
		 * @return mixed The sanitized value
		 * 
		 * @example:
		 *      $this->request->getSanitizedInput('name');
		 *      $this->request->getSanitizedInput('email', null, 'email');
		 *      $this->request->getSanitizedInput('numItems', null, 'int', ['min' => 0, 'max' => 10]);
		 */
		public function getSanitizedInput($key, $defaultValue = null, $type = 'string', $options = []) {
			$value = $this->getValue($key, $defaultValue);
			return InputSanitizer::sanitize($value, $type, $options);
		}

		/**
		 * Gets the value for the specified key from the query, params, or body objects
		 * 
		 * @param string $key
		 * @param mixed $defaultValue
		 * @return mixed sanitized value
		 */
		public function getValue($key, $defaultValue = null) {
			$value = $defaultValue;

			if (array_key_exists($key, $this->query)) {
				$value = $this->getQuery($key, $defaultValue);
				
			} elseif (array_key_exists($key, $this->params)) {
				$value = $this->getParam($key, $defaultValue);

			} elseif (is_array($this->body) && array_key_exists($key, $this->body)) {
				$value = $this->getBody($key, $defaultValue);
			}

			return $value;
		}

		/**
		 * Gets the value for the specified key from the query object
		 * 
		 * @param string $key
		 * @param mixed $defaultValue
		 * @return mixed sanitized value
		 */
		public function getQuery($key, $defaultValue = null) {
			return $this->input($key, $defaultValue, 'query');
		}

		/**
		 * Gets the value for the specified key from the body object
		 * 
		 * @param string $key
		 * @param mixed $defaultValue
		 * @return mixed sanitized value
		 */
		public function getBody($key, $defaultValue = null) {
			return $this->input($key, $defaultValue, 'body');
		}

		/**
		 * Gets the value for the specified key from the params object
		 * 
		 * @param string $key
		 * @param mixed $defaultValue
		 * @return mixed sanitized value
		 */
		public function getParam($key, $defaultValue = null) {
			return $this->input($key, $defaultValue, 'params');
		}

		/**
		 * Sanitizes the value
		 * 
		 * @param mixed $value
		 * @return mixed sanitized value
		 */
		public function sanitize($value) {
			if ($value === null) {
				return $value;
			}

			// If array, sanitize each item
			if (is_array($value)) {
				return array_map(function($item) {
					return $this->sanitize($item);
				}, $value);
			}

			// If object, sanitize each property
			if (is_object($value)) {
				$sanitized = [];
				foreach ($value as $key => $val) {
					$sanitized[$key] = $this->sanitize($val);
				}
				return $sanitized;
			}

			// For string, escape dangerous characters
			if (is_string($value)) {
				$value = str_replace("'", "''", $value);  // Escape single quotes
				$value = str_replace("\\", "\\\\", $value);  // Escape backslashes
				$value = str_replace("\0", "\\0", $value);  // Escape null bytes
				return trim($value);
			}
			
			return $value;
		}

		/**
		 * Gets the value for the specified key, sanitizes it, then casts it to an int
		 * 
		 * @param string $key
		 * @param int|null $defaultValue
		 * @param string $source
		 * @return int
		 */
		public function int($key, $defaultValue = null, $source = 'body') {
			$value = $this->input($key, $defaultValue, $source);
			return InputSanitizer::sanitizeInteger($value, ['default' => $defaultValue]);
		}

		/**
		 * Gets the value for the specified key, sanitizes it, then casts it to a float
		 * 
		 * @param string $key
		 * @param float|null $defaultValue
		 * @param string $source
		 * @return float
		 */
		public function float($key, $defaultValue = null, $source = 'body') {
			$value = $this->input($key, $defaultValue, $source);
			return InputSanitizer::sanitizeFloat($value, ['default' => $defaultValue]);
		}

		/**
		 * Gets the value for the specified key, sanitizes it, then returns a boolean
		 * 
		 * @param string $key
		 * @param bool|null $defaultValue
		 * @param string $source
		 * @return bool
		 */
		public function boolean($key, $defaultValue = false, $source = 'body') {
			$value = $this->input($key, $defaultValue, $source);
			return InputSanitizer::sanitizeBoolean($value, ['default' => $defaultValue]);
		}

		/**
		 * Gets all the key|value pairs from the source object
		 * 
		 * @param string $source
		 * @return array key|value pairs
		 */
		public function all($source = 'body') {
			$data = [];

			switch ($source) {
				case 'query':
					$data = $this->query;
					break;
				case 'params':
					$data = $this->params;
					break;
				case 'body':
				default:
					$data = is_array($this->body) ? $this->body : [];
					break;
			}

			return $this->sanitize($data);
		}

		/**
		 * Checks if the source object has the specified key
		 * 
		 * @param string $key
		 * @param string $source
		 * @return bool true if source has the specified key
		 */
		public function has($key, $source = 'body') {
			switch ($source) {
				case 'query':
					return array_key_exists($key, $this->query);
				case 'params':
					return array_key_exists($key, $this->params);
				case 'body':
				default:
					return is_array($this->body) && array_key_exists($key, $this->body);
			}
		}

		/**
		 * Gets the header value for the specified key
		 * 
		 * @param string $name
		 * @return string|null
		 */
		public function header($name) {
			$name = strtolower($name);
			return isset($this->headers[$name]) ? $this->headers[$name] : null;
		}

		/**
		 * Gets the value of a specific cookie by key
		 * 
		 * @param string $key
		 * @return string|null
		 */
		public function getCookie($key) {
			return isset($_COOKIE[$key]) ? $_COOKIE[$key] : null;
		}

		/**
		 * Gets all the cookies
		 * 
		 * @return array
		 */
		public function getCookies() {
			return $_COOKIE;
		}
	}
