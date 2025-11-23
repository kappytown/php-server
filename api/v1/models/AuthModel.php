<?php

	/**
	 * Auth Model
	 * 
	 * Handles database operations for authentication and session management
	 */

	require_once __DIR__ . '/BaseModel.php';

	class AuthModel extends BaseModel {
		
		/**
		 * Constructor
		 * 
		 * @param mixed $db Database connection instance
		 */
		public function __construct($db) {
			parent::__construct($db);
		}

		/**
		 * Returns the user's session if found
		 * 
		 * @param string $token
		 * @return object|null
		 */
		public function authenticate($token) {
			$result = $this->db->query('SELECT user_id FROM sessions WHERE token = ? LIMIT 1', [ $token ]);

			if (count($result) > 0) {
				return $result[0];
			}

			return null;
		}

		/**
		 * Inserts the token in the sessions table for the logged in user
		 * 
		 * @param int $userId
		 * @param string $token
		 * @param string $expiresAt
		 * @return bool
		 */
		public function createSession($userId, $token, $expiresAt) {
			$result = $this->db->execute('INSERT IGNORE INTO sessions (user_id, token, expires_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE token = ?, expires_at = ?', [ $userId, $token, $expiresAt, $token, $expiresAt ]);

			return $result->rowCount() > 0;
		}

		/**
		 * Deletes the associated session in the sessions table
		 * 
		 * @param string $token
		 * @return PDOStatement
		 */
		public function deleteSession($token) {
			$result = $this->db->execute('DELETE FROM sessions WHERE token = ?', [ $token ]);

			return $result->rowCount() > 0;
		}
	}
