<?php
	/**
	 * Base Controller Class
	 * 
	 * Parent class for all API controllers.
	 */
	class BaseController {
		protected $db;
		protected $request;
		protected $reponse;

		/**
		 * Constructor - initializes controller with database, request and response instances
		 * 
		 * @param Database db - Database instance
		 * @param Request request
		 * @param Reponse response
		 */
		public function __construct($db, $request, $response) {
			$this->db 		= $db;
			$this->request 	= $request;
			$this->response = $response;
		}

		/**
		 * Cleans up by closing database connection and unsetting variables
		 */
		public function __destruct() {
			if (isset($this->db)) {
				$this->db->close();
				unset($this->db);
			}
			unset($this->request);
			unset($this->response);
		}
	}
