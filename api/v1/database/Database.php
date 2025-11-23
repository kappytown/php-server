<?php
	require_once __DIR__ . '/DatabaseInterface.php';

	abstract class Database implements DatabaseInterface
	{
		protected $config;

		/**
		 * Constructor - initializes controller with database configuration
		 * 
		 * @param array config - Database configuration=
		 */
		public function __construct($config) {
			$this->config = $config;
		}

		/**
		 * Performs cleanup tasks such as closing the database connection
		 */
		public function __destruct() {
			$this->close();
		}
	}
