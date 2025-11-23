<?php
	require_once __DIR__ . '/Database.php';
	require_once __DIR__ . '/../exceptions/CustomExceptions.php';

	class MysqlDatabase extends Database
	{
		private $connection;
		private $host;
		private $port;
		private $user;
		private $password;
		private $database;

		/**
		 * Calls the connect method to connect to the database when instantiated
		 */
		public function __construct($config) {
			parent::__construct($config);

			$this->host 	= $config['DB_HOST'];
			$this->port 	= $config['DB_PORT'];
			$this->user 	= $config['DB_USER'];
			$this->password = $config['DB_PASSWORD'];
			$this->database = $config['DB_NAME'];
		}

		/**
		 * Connects to the database if not already connected
		 */
		public function connect()
		{
			if (!$this->host || !$this->user || !$this->password || !$this->database) {
				throw new DatabaseConnectionException('Invalid database connection. Please provide connection parameters');
			}

			// Only connect if we do not have a connection
			if (!isset($this->connection)) {

				$options = [
					PDO::ATTR_ERRMODE 				=> PDO::ERRMODE_EXCEPTION,
					PDO::ATTR_DEFAULT_FETCH_MODE 	=> PDO::FETCH_ASSOC
				];
				//$server = $config['DB_SERVER'];

				if ($this->host == 'localhost' || $this->host == '127.0.0.1' ) {
					$options = NULL;
				}

				try {
					$attributes = [];
					/*[
						//PDO::ATTR_ERRMODE 					=> PDO::ERRMODE_EXCEPTION,
						//PDO::MYSQL_ATTR_USE_BUFFERED_QUERY 	=> 1
					];*/

					$this->connection = new PDO('mysql:host=' . $this->host . ';port=' . $this->port . ';dbname=' . $this->database . ';charset=utf8', $this->user , $this->password, $options);

					if (isset($attributes)) {
        				foreach($attributes as $key => $value) {
        					$this->connection->setAttribute($key, $value);
        				}
        			}
					//error_log('database connection open' . PHP_EOL);

				} catch(Exception $e) {
					throw new DatabaseConnectionException($e->getMessage());
				}
			}
		}


		/**
		 * A proxy to native PDO methods
		 *
		 * @param string $method
		 * @param array $params
		 *
		 * @return mixed
		 */
		public function __call($method, $params)
		{
			return call_user_func_array([$this->connection, $method], $params);
		}

		/**
		 * Helper function to run prepared statements smoothly
		 *
		 * @param string $sql
		 * @param array $args
		 *
		 * @return PDOStatement
		 */
		public function execute($sql, $params=[])
		{
			if (!isset($this->connection)) {
				$this->connect();
			}

			if (empty($params)) {
				return $this->connection->query($sql);
			}

			$stmt = $this->connection->prepare($sql);
			$stmt->execute($params);
			return $stmt;
		}

		/**
		 * Returns one ore more results
		 *
		 * @param string $sql
		 * @param array $params
		 *
		 * @return array
		 */
		public function query($sql, $params = [])
		{
			$stmt = $this->execute($sql, $params);

			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
			$stmt = null;

			return $result;
		}

		/**
		 * Returns the first result
		 *
		 * @param string $sql
		 * @param array $params
		 *
		 * @return array
		 */
		public function fetchFirst($sql, $params = [])
		{
			$result = $this->query($sql, $params);
			if (count($result) > 0) {
				return $result[0];
			} else {
				return [];
			}
		}

		/**
		 * 
		 * @return int|null
		 */
		public function getInsertId() {
			return $this->connection->lastInsertId();
		}

		/**
		 * 
		 * @return int|null
		 */
		public function getAffectedRows(&$stmt) {
			return $stmt->rowCount();
		}

		/**
		 * 
		 * @return int|null
		 */
		public function getChangedRows() {
			return $this->connection->rowCount();
		}

		/**
		 * Closes the connection
		 */
		public function close()
		{
			$this->connection = null;
		}

		/**
		 *
		 */
		public function __destruct()
		{
			parent::__destruct();

			unset($this->connection);
			unset($this->config);
		}
	}
