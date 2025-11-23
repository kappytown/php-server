<?php 
	/**
	 * Input Sanitizer
	 * 
	 * Provides comprehensive input sanitization and validation to prevent
	 * SQL injection, XSS, and other injection attacks.
	 */
	class InputSanitizer {
		
		/**
		 * Sanitizes any value type by specifying the sanitization type in the options {type:'string'}
		 * 
		 * @param mixed $value Value to sanitize
		 * @param string $type Type of sanitization to perform
		 * @param array $options Sanitization options [...]
		 * @return mixed Sanitized value
		 * 
		 * @example
		 * $sanitized = InputSanitizer::sanitize($userData, 'string', ['maxLength' => 150]);
		 */
		public static function sanitize($value, $type = 'string', $options = []) {
			switch(strtolower($type)) {
				case 'string':
					return self::sanitizeString($value, $options);
				case 'int':
				case 'integer':
					return self::sanitizeInteger($value, $options);
				case 'number':
				case 'float':
					return self::sanitizeFloat($value, $options);
				case 'bool':
				case 'boolean':
					return self::sanitizeBoolean($value, $options);
				case 'email':
					return self::sanitizeEmail($value, $options);
				case 'url':
					return self::sanitizeUrl($value, $options);
				case 'object':
					return self::sanitizeObject($value, $options);
				case 'array':
					return self::sanitizeArray($value, null, $options);
				case 'password':
					return self::sanitizePassword($value, $options);
			}
			return $value;
		}

		/**
		 * Sanitize a string value
		 * Removes or escapes potentially dangerous characters
		 * 
		 * @param string $value Value to sanitize
		 * @param array $options Sanitization options ['allowHtml' => false, 'trim' => true, 'maxLength' => 10]
		 * @return string Sanitized value
		 */
		public static function sanitizeString($value, $options = []) {
			if ($value === null || $value === '') {
				return '';
			}

			$sanitized = (string)$value;

			// Trim whitespace
			if (!isset($options['trim']) || $options['trim'] !== false) {
				$sanitized = trim($sanitized);
			}

			// Remove HTML tags unless explicitly allowed
			if (empty($options['allowHtml'])) {
				$sanitized = self::_stripHtmlTags($sanitized);
			}

			// Remove null bytes (potential for SQL injection)
			$sanitized = str_replace("\0", '', $sanitized);

			// Enforce maximum length
			if (isset($options['maxLength']) && strlen($sanitized) > $options['maxLength']) {
				$sanitized = substr($sanitized, 0, $options['maxLength']);
			}

			return $sanitized;
		}

		/**
		 * Sanitize an integer value
		 * 
		 * @param mixed $value Value to sanitize
		 * @param array $options Sanitization options ['min' => 0, 'max' => 10, 'default' => 0]
		 * @return int Sanitized integer
		 */
		public static function sanitizeInteger($value, $options = []) {
			$parsed = filter_var($value, FILTER_VALIDATE_INT);

			if ($parsed === false) {
				return isset($options['default']) ? $options['default'] : 0;
			}

			$sanitized = $parsed;

			// Enforce minimum
			if (isset($options['min']) && $sanitized < $options['min']) {
				$sanitized = $options['min'];
			}

			// Enforce maximum
			if (isset($options['max']) && $sanitized > $options['max']) {
				$sanitized = $options['max'];
			}

			return $sanitized;
		}

		/**
		 * Sanitize a float/decimal value
		 * 
		 * @param mixed $value Value to sanitize
		 * @param array $options Sanitization options ['min' => 0, 'max' => 10, 'default' => 0, 'decimals' => 2]
		 * @return float Sanitized float
		 */
		public static function sanitizeFloat($value, $options = []) {
			$parsed = filter_var($value, FILTER_VALIDATE_FLOAT);

			if ($parsed === false) {
				return isset($options['default']) ? $options['default'] : 0;
			}

			$sanitized = $parsed;

			// Round to specified decimal places
			if (isset($options['decimals'])) {
				$sanitized = round($sanitized, $options['decimals']);
			}

			// Enforce minimum
			if (isset($options['min']) && $sanitized < $options['min']) {
				$sanitized = $options['min'];
			}

			// Enforce maximum
			if (isset($options['max']) && $sanitized > $options['max']) {
				$sanitized = $options['max'];
			}

			return $sanitized;
		}

		/**
		 * Sanitize a boolean value
		 * 
		 * @param mixed $value Value to sanitize
		 * @param array $options Sanitization options ['default' => null]
		 * @return bool|null Sanitized boolean
		 */
		public static function sanitizeBoolean($value, $options = []) {
			if ($value === null || $value === '') {
				return isset($options['default']) ? $options['default'] : null;
			}
			if (is_bool($value)) return true;
			if (is_string($value)) {
				return in_array(strtolower($value), ['true', '1', 'yes']);
			}
			return (bool)$value;
		}

		/**
		 * Sanitize email address
		 * 
		 * @param string $email Email to sanitize
		 * @param array $options Sanitization options (unused)
		 * @return string Sanitized email or empty string if invalid
		 */
		public static function sanitizeEmail($email, $options = []) {
			if (!$email) return '';

			$sanitized = trim(strtolower((string)$email));

			// Remove any characters that aren't valid in email addresses
			$sanitized = preg_replace('/[^a-z0-9@._+-]/', '', $sanitized);

			// Basic email format validation
			if (!filter_var($sanitized, FILTER_VALIDATE_EMAIL)) {
				return '';
			}

			return $sanitized;
		}

		/**
		 * This will validate the password and return an empty string if it fails
		 * or the original password value if it passes
		 * 
		 * @param string $password Password to validate
		 * @param array $options Sanitization options (unused)
		 * @return string password if valid
		 */
		public static function sanitizePassword($password, $options = []) {
			if (!$password) return '';

			$sanitized = trim((string)$password);

			$regex = '/^(?=.*\d)(?=.*[A-Z])(?=.*[a-z])(?=.*[!@#\-_~$%^&*()])(?=\S*$).{8,20}$/';

			if (!preg_match($regex, $sanitized)) return '';

			return $sanitized;
		}

		/**
		 * Sanitize URL
		 * 
		 * @param string $url URL to sanitize
		 * @param array $options Sanitization options ['allowedProtocols' => ['http', 'https']]
		 * @return string Sanitized URL or empty string if invalid
		 */
		public static function sanitizeUrl($url, $options = []) {
			if (!$url) return '';

			$allowedProtocols = isset($options['allowedProtocols']) ? $options['allowedProtocols'] : ['http', 'https'];
			$sanitized = trim((string)$url);

			// Validate URL format
			if (!filter_var($sanitized, FILTER_VALIDATE_URL)) {
				return '';
			}

			$urlParts = parse_url($sanitized);
			
			if (!$urlParts || !isset($urlParts['scheme'])) {
				return '';
			}

			// Check if protocol is allowed
			if (!in_array($urlParts['scheme'], $allowedProtocols)) {
				return '';
			}

			return $sanitized;
		}

		/**
		 * Sanitize an object by applying sanitization to all properties
		 * 
		 * @param array $obj Object to sanitize
		 * @param array $schema Sanitization schema defining rules for each field
		 * @return array Sanitized object
		 */
		public static function sanitizeObject($obj, $schema) {
			$sanitized = [];

			foreach ($schema as $key => $rules) {
				$value = isset($obj[$key]) ? $obj[$key] : null;

				switch ($rules['type']) {
					case 'string':
						$sanitized[$key] = self::sanitizeString($value, $rules);
						break;
					case 'int':
					case 'integer':
						$sanitized[$key] = self::sanitizeInteger($value, $rules);
						break;
					case 'float':
					case 'number':
						$sanitized[$key] = self::sanitizeFloat($value, $rules);
						break;
					case 'email':
						$sanitized[$key] = self::sanitizeEmail($value);
						break;
					case 'url':
						$sanitized[$key] = self::sanitizeUrl($value, $rules);
						break;
					case 'bool':
					case 'boolean':
						$sanitized[$key] = self::sanitizeBoolean($value, $rules);
						break;
					default:
						$sanitized[$key] = $value;
				}
			}

			return $sanitized;
		}

		/**
		 * Detect potential SQL injection attempts
		 * 
		 * @param string $value Value to check
		 * @return bool True if potential SQL injection detected
		 */
		public static function detectSqlInjection($value) {
			if (!$value || !is_string($value)) {
				return false;
			}

			// Common SQL injection patterns
			$sqlInjectionPatterns = [
				'/(\b(SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|EXEC|EXECUTE)\b)/i',
				'/(UNION\s+SELECT)/i',
				'/(-{2}|\/\*|\*\/)/',  // SQL comments
				'/(\bOR\b|\bAND\b)\s+[\d\w]+\s*=\s*[\d\w]+/i',  // OR 1=1, AND 1=1
				'/(;\s*(SELECT|INSERT|UPDATE|DELETE|DROP))/i',
				'/(\bxp_|\bsp_)/i',  // SQL Server stored procedures
				'/(INFORMATION_SCHEMA|SYSOBJECTS|SYSCOLUMNS)/i'
			];

			foreach ($sqlInjectionPatterns as $pattern) {
				if (preg_match($pattern, $value)) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Strip HTML tags from string
		 * 
		 * @param string $value String with potential HTML
		 * @return string String without HTML tags
		 */
		private static function _stripHtmlTags($value) {
			return preg_replace('/<[^>]*>/', '', $value);
		}

		/**
		 * Escape special characters for HTML output (XSS prevention)
		 * 
		 * @param string $value Value to escape
		 * @return string HTML-safe value
		 */
		public static function escapeHtml($value) {
			if (!$value) return '';

			return htmlspecialchars((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		}

		/**
		 * Sanitize array of values
		 * 
		 * @param array $arr Array to sanitize
		 * @param string $type Type of sanitization to apply
		 * @param array $options Sanitization options
		 * @return array Sanitized array
		 */
		public static function sanitizeArray($arr, $type = 'string', $options = []) {
			if (!is_array($arr)) {
				return [];
			}

			return array_map(function($value) use ($type, $options) {
				switch ($type) {
					case 'string':
						return self::sanitizeString($value, $options);
					case 'integer':
						return self::sanitizeInteger($value, $options);
					case 'float':
						return self::sanitizeFloat($value, $options);
					case 'email':
						return self::sanitizeEmail($value);
					default:
						return $value;
				}
			}, $arr);
		}
	}
