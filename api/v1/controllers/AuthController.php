<?php

	require_once __DIR__ . '/BaseController.php';
	require_once __DIR__ . '/../exceptions/CustomExceptions.php';
	require_once __DIR__ . '/../models/AuthModel.php';

	/**
	 * Auth Controller
	 * 
	 * Handles Authentication by validating the user token
	 */
	class AuthController extends BaseController {
		
		/**
		 * @var int The logged in user's id
		 */
		protected $userId;
		
		/**
		 * @var string|null The logged in user's token
		 */
		protected $token;
		
		/**
		 * @var string The name of the session cookie
		 */
		protected $cookieName;
		
		/**
		 * @var int The duration of the session in seconds
		 */
		protected $sessionTimeout;
		
		/**
		 * @var AuthModel The model that handles database authentication calls
		 */
		protected $authModel;

		/**
		 * Constructor
		 * 
		 * @param Database $db Database instance
		 * @param Request $request Request object
		 * @param Response $response Response helper object
		 * 
		 * @return void
		 */
		public function __construct($db, $request, $response) {
			parent::__construct($db, $request, $response);

			$this->userId 			= 0;
			$this->token 			= $this->_getToken();
			$this->cookieName 		= 'simple_app_cookie';
			$this->sessionTimeout 	= 60 * 60 * 24 * 14; // 14 days in seconds
			$this->authModel 		= new AuthModel($this->db);
		}

		/**
		 * Gets the associated userId for the token stored in a cookie.
		 * 
		 * @return array
		 * @throws AuthenticationException if validation fails
		 */
		public function authenticate() {
			$this->token = $this->_getToken();

			// Validate the token
			if (!$this->token || !preg_match('/^[0-9a-zA-Z\-_=|:]*$/', $this->token)) {
				throw new AuthenticationException('Invalid or missing authentication token');
			}
			
			// Get the associated userId for the token
			$result = $this->authModel->authenticate($this->token);
			
			if (!$result) {
				throw new AuthenticationException('Session not found.');
			}

			$this->setUserAndToken($result['user_id'], $this->token);
			return $result;
		}

		/**
		 * Creates the user session and cookie
		 * 
		 * @param int $userId
		 * @return array
		 * @throws AuthenticationException if validation fails
		 */
		public function createSession($userId) {
			$this->token = $this->_generateToken();
			
			$expiresAt 	= $this->_getSessionExpiration();
			$result 	= $this->authModel->createSession($userId, $this->token, $expiresAt, $this->token, $expiresAt);

			if ($result) {
				$this->setUserAndToken($userId, $this->token);

				$this->response->cookie($this->cookieName, $this->token, [
					'maxAge' 	=> $this->sessionTimeout,
					'secure' 	=> true,
					'sameSite' 	=> 'Lax'
				]);
				
				return ['token' => $this->token, 'userId' => $this->userId];
			
			} else {
				throw new AuthenticationException('Unable to create user session.');
			}
		}

		/**
		 * Deletes the user session and cookie
		 * 
		 * @return void
		 */
		public function deleteSession() {
			// Delete from the db
			$this->authModel->deleteSession($this->token);

			// Remove the cookie
			$this->response->clearCookie($this->cookieName);

			// Reset vars
			$this->setUserAndToken();
		}

		/**
		 * Checks if the user is already authenticated
		 * 
		 * @return bool true if both token and userId are set
		 */
		public function isAuthenticated() {
			return $this->token !== null && $this->userId !== 0;
		}

		/**
		 * Deletes the session cookie
		 * 
		 * @return void
		 */
		public function deleteCookie() {
			$this->response->clearCookie($this->cookieName);
		}

		/**
		 * Sets the class variables userId and token
		 * 
		 * @param int $userId
		 * @param string|null $token
		 * @return void
		 */
		protected function setUserAndToken($userId = 0, $token = null) {
			$this->userId 	= $userId;
			$this->token 	= $token;
		}

		/**
		 * Gets the token from the cookie
		 * 
		 * @return string the cookie value
		 */
		protected function _getToken() {
			return $this->request->getCookie($this->cookieName) ?: '';
		}

		/**
		 * Gets the session expiration date
		 * 
		 * @return string the session expiration date in MySQL format
		 */
		protected function _getSessionExpiration() {
			$timestamp = time() + $this->sessionTimeout;
			return date('Y-m-d H:i:s', $timestamp);
		}

		/**
		 * Generates a random session token
		 * 
		 * @return string the generated session token
		 */
		protected function _generateToken() {
			$randomBytes = base64_encode(random_bytes(64));
			
			return str_replace(
				[ '+', '/', '\\', ' ' ],
				[ '-', '_', '|', ':' ],
				$randomBytes
			);
		}
	}
