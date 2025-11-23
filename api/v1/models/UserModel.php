<?php

	/**
	 * User Model
	 * 
	 * Handles all database operations for customer resources.
	 * Manages customer contact information and company associations.
	 */

	require_once __DIR__ . '/BaseModel.php';
	require_once __DIR__ . '/../exceptions/CustomExceptions.php';

	class UserModel extends BaseModel {
		
		/**
		 * Authenticate user login
		 * 
		 * @param string $email
		 * @param string $password
		 * @return array|null
		 */
		public function login($email, $password) {
			$result = $this->db->query('SELECT * FROM users WHERE email = ?', [ $email ]);
			
			if ($result && count($result) > 0) {
				// Verify password
				$validPassword = password_verify($password, $result[0]['password']);
				if (!$validPassword) {
					return null;
				}

				return $result[0];
			}

			return null;
		}

		/**
		 * Create a new user
		 * 
		 * @param string $name
		 * @param string $email
		 * @param string $password
		 * @return array|null
		 */
		public function create($name, $email, $password) {
			// Check if user already exists
			$result = $this->getUserByField('email', $email);
			
			if ($result && count($result) > 0) {
				throw new ValidationException('User already exists');
			}

			// Hash password before storing
			$hashedPassword = password_hash($password, PASSWORD_BCRYPT);
			
			// Insert new user
			$result = $this->db->execute('INSERT INTO users (name, email, password) VALUES (?, ?, ?)', [ $name, $email, $hashedPassword ]);
			$userId = $this->db->getInsertId();

			if ($userId > 0) {
				return [ 'id' => $userId, 'email' => $email, 'name' => $name ];
			} else {
				return null;
			}
		}

		/**
		 * Read user by ID
		 * 
		 * @param int $id
		 * @return array
		 */
		public function read($id) {
			$result = $this->getUserByField('id', $id);

			if ($result && count($result) > 0) {
				return [
					'id' 	=> $result[0]['id'],
					'name' 	=> $result[0]['name'],
					'email' => $result[0]['email']
				];
			}

			return [];
		}

		/**
		 * Update user information
		 * 
		 * @param int $id
		 * @param string $name
		 * @param string $email
		 * @param string|null $password
		 * @param string|null $newPassword
		 * @return array|null
		 */
		public function update($id, $name, $email, $password, $newPassword) {
			$result = null;
			
			// If updating password...
			if ($password && $newPassword) {
				$result = $this->getUserByField('id', $id);

				if ($result && count($result) > 0) {
					// Verify password
					$validPassword = password_verify($password, $result[0]['password']);
					if (!$validPassword) {
						throw new ValidationException('Your current password is invalid');
					}
				}

				// Hash password before storing
				$hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

				$result = $this->db->execute('UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?', [ $name, $email, $hashedPassword, $id ]);
			} else {
				$result = $this->db->execute('UPDATE users SET name = ?, email = ? WHERE id = ?', [ $name, $email, $id ]);
			}

			if ($result->rowCount() > 0) {
				return ['id' => $id, 'email' => $email, 'name' => $name];
			} else {
				return null;
			}
		}

		/**
		 * Delete user by ID
		 * 
		 * @param int $id
		 * @return bool
		 */
		public function delete($id) {
			$result = $this->db->execute('DELETE FROM users WHERE id = ?', [$id]);
			
			return $result->rowCount() > 0;
		}

		/**
		 * Sends email
		 * 
		 * @param string $name
		 * @param string $email
		 * @param string $message
		 * @return bool
		 */
		public function sendMail($name, $email, $message) {
			try {
				/* TODO: Send email implementation */
				$to 		= "your_email@support.com";
				$subject 	= "Contact Us Form Inquery";
				$message 	= "From: $name\r\nEmail: $email\r\n" . $message;
				$headers 	= "From: $email\r\n" . 
					"Reply-To: $email\r\n" . 
					"X-Mailer: PHP/" . phpversion();

				if (mail($to, $subject, $message, $headers)) {
					return true;
				}
			} catch (Exception $e) {
				error_log($e->getMessage());
			}
			return false;
		}

		/**
		 * Get user by specific field
		 * 
		 * @param string $field
		 * @param mixed $value
		 * @return array
		 */
		protected function getUserByField($field, $value) {
			// Whitelist allowed fields to prevent SQL injection
			$allowedFields = [ 'id', 'email', 'name' ];
			if (!in_array($field, $allowedFields)) {
				throw new ValidationException('Invalid field name');
			}
			
			return $this->db->query("SELECT * FROM users WHERE $field = ? LIMIT 1", [$value]);
		}
	}
