<?php

	/**
	 * Base API Exception Class
	 * 
	 * Parent class for all custom API exceptions. Provides consistent error
	 * formatting and the ability to attach additional context through variables.
	 */
	class ApiException extends Exception {
		
		/**
		 * @var array|null Additional context variables
		 */
		protected $vars;
		
		/**
		 * @var Exception|null Previous exception
		 */
		protected $previous;
		
		/**
		 * @var string Exception name
		 */
		protected $name;

		/**
		 * Constructor - creates a new API exception
		 * 
		 * @param string|null $message Error message (default: 'An error occurred')
		 * @param array|null $vars Key-value pairs to append to the message for context
		 * @param int $code HTTP status code (default: 500)
		 * @param Exception|null $previous Original exception that caused this error
		 * 
		 * @example
		 * throw new ApiException(
		 *   'User not found',
		 *   ['userId' => 123, 'email' => 'test@example.com'],
		 *   404
		 * );
		 */
		public function __construct($message = null, $vars = null, $code = 500, $previous = null) {
			$finalMessage = $message ?: 'An error occurred';

			// Append vars to message if provided for better context
			if ($vars && is_array($vars)) {
				$varStrings = [];

				foreach ($vars as $key => $value) {
					if (is_array($value)) {
						$value = implode(', ', $value);
					}
					$varStrings[] = "$key: $value";
				}

				$finalMessage .= ' (' . implode(', ', $varStrings) . ')';
			}

			// Call parent constructor
			parent::__construct($finalMessage, $code, $previous);

			// Set exception properties
			$this->name = get_class($this);
			$this->vars = $vars;
		}

		/**
		 * Get the exception name
		 * 
		 * @return string
		 */
		public function getName() {
			return $this->name;
		}

		/**
		 * Get the context variables
		 * 
		 * @return array|null
		 */
		public function getVars() {
			return $this->vars;
		}

		/**
		 * Convert exception to JSON format for API responses
		 * 
		 * @return array JSON representation of the exception
		 */
		public function toJSON() {
			$json = [
				// 'success' => false,
				'error' 	=> $this->name,
				'message' 	=> $this->message,
				// 'code' => $this->code
			];

			if ($this->vars) {
				$json['details'] = $this->vars;
			}

			return $json;
		}

		/**
		 * Convert exception to string
		 * 
		 * @return string
		 */
		public function __toString() {
			return json_encode($this->toJSON());
		}
	}
