<?php

	require_once __DIR__ . '/AuthController.php';
	require_once __DIR__ . '/../models/UserModel.php';
	require_once __DIR__ . '/../exceptions/CustomExceptions.php';

	/**
	 * UserController class handles all user related actions.
	 * 
	 * Note: All controllers that require authentication must extend the AuthController 
	 * and call $this->authenticate() before executing any other code. This will ensure that no 
	 * other code will execute if the user's session is invalid.
	 */
	class UserController extends AuthController {

		/**
		 * @var UserModel
		 */
		protected $model;

		/**
		 * Constructor
		 * 
		 * @param Database $db Database instance
		 * @param Request $req Request object
		 * @param Response $res Response object
		 */
		public function __construct($db, $req, $res) {
			parent::__construct($db, $req, $res);

			$this->model = new UserModel($this->db);
		}

		/**
		 * Handles getting the user's session as well as deleting it
		 * 
		 */
		public function session() {
			$result = null;
			
			if ($this->request->method === 'GET') {
				// Authenticates the user's session
				$this->authenticate();

				// Gets the user's details
				$result = $this->model->read($this->userId);
			} else {
				// Deletes the user's session
				$result = $this->deleteSession();
			}

			$this->response->success($result);
		}

		/**
		 * Logs the user in and creates the session
		 * 
		 * @throws NotFoundException
		 */
		public function login() {
			$email 		= $this->request->getSanitizedInput('email', null, 'email');
			$password 	= $this->request->getSanitizedInput('password', null, 'password');
			
			if (!$email || !$password) {
				throw new MissingParametersException('Email and password are required');
			}

			$result = $this->model->login($email, $password);

			if (!$result) {
				$this->deleteCookie();
				throw new NotFoundException('Invalid email or password');
			}

			$this->createSession($result['id']);

			// Remove password from result
			unset($result['password']);
			$this->response->success($result);
		}

		/**
		 * Logs the user out and removes the user's session
		 * 
		 */
		public function logout() {
			$this->deleteSession();
			
			$this->response->success(null, 'Logged out successfully');
		}

		/**
		 * Create a new customer
		 * 
		 * @throws MissingParametersException
		 * @throws ValidationException
		 */
		public function create() {
			$name 		= $this->request->getSanitizedInput('name');
			$email 		= $this->request->getSanitizedInput('email', null, 'email');
			$password 	= $this->request->getSanitizedInput('password', null, 'password');

			if (!$name || !$email || !$password) {
				throw new MissingParametersException('Name, email, and password are required.');
			}

			$result = $this->model->create($name, $email, $password);

			if (!$result) {
				throw new ValidationException('Failed to create user. Email may already be in use.');
			}

			$this->response->success($result);
		}

		/**
		 * Gets the logged in user's info
		 * 
		 * @throws MissingParametersException
		 */
		public function read() {
			$this->authenticate();

			if (!$this->userId) {
				throw new MissingParametersException('User id is required.');
			}
			
			$result = $this->model->read($this->userId);

			$this->response->success($result);
		}

		/**
		 * Updates the logged in user's account
		 * 
		 * @throws MissingParametersException
		 * @throws ValidationException
		 */
		public function update() {
			$this->authenticate();

			$name 			= $this->request->getSanitizedInput('name');
			$email 			= $this->request->getSanitizedInput('email', null, 'email');
			$password 		= $this->request->getSanitizedInput('password', null, 'password');
			$newPassword 	= $this->request->getSanitizedInput('new_password', null, 'password');

			if (!$this->userId || !$name || !$email) {
				throw new MissingParametersException('User ID, name, and email are required.');
			}

			if ($password || $newPassword) {
				if (!$password || !$newPassword) {
					throw new ValidationException('Password is not valid.');
				}

				if ($password === $newPassword) {
					throw new ValidationException('Your current password and new password cannot be the same.');
				}
			}
			
			$result = $this->model->update($this->userId, $name, $email, $password, $newPassword);

			if (!$result) {
				throw new ValidationException('Failed to update user. Please try again shortly.');
			}

			$this->response->success($result);
		}

		/**
		 * Deletes the logged in user's account
		 * 
		 * @throws MissingParametersException
		 * @throws ValidationException
		 */
		public function delete() {
			$this->authenticate();

			if (!$this->userId) {
				throw new MissingParametersException('User ID is required.');
			}

			$result = $this->model->delete($this->userId);

			if (!$result) {
				throw new ValidationException('Failed to delete user.');
			}

			$this->response->clearCookie($this->cookieName);
			$this->response->success($result);
		}

		/**
		 * Sends email
		 * 
		 * @throws MissingParametersException
		 * @throws ValidationException
		 */
		public function sendMail() {
			$name 		= $this->request->getSanitizedInput('name');
			$email 		= $this->request->getSanitizedInput('email', null, 'email');
			$message 	= $this->request->getSanitizedInput('message');

			if (empty($name) || empty($email) || empty($message)) {
				throw new MissingParametersException('Name, email, and message are required.');
			}

			$result = $this->model->sendMail($name, $email, $message);

			if (!$result) {
				throw new ValidationException('Failed to send email. Please try again shortly.');
			}

			$this->response->success($result);
		}

		/**
		 * Validates that password
		 * 
		 * @param string $password
		 * @return bool
		 */
		protected function _isValidPassword($password) {
			return preg_match('/^(?=.*\d)(?=.*[A-Z])(?=.*[a-z])(?=.*[!@#\-_~$%^&*()])(?=\S*$).{8,20}$/', $password) === 1;
		}
	}
