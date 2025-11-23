<?php

	/**
	 * Base Model Class
	 * 
	 * Parent class for all API models.
	 */
	class BaseModel {
		protected $db;
		protected $userId;

		/**
		 * Constructor - initializes controller with database instance
		 * 
		 * @param Database $db - Database instance
		 * @param int $userId - ID of the authenticated user
		 */
		public function __construct($db, $userId = 0) {
			$this->db 		= $db;
			$this->userId 	= $userId;
		}

		public function __destruct() {
			unset($this->db);
		}
	}