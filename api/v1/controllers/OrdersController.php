<?php

	require_once __DIR__ . '/AuthController.php';
	require_once __DIR__ . '/../models/OrdersModel.php';
	require_once __DIR__ . '/../exceptions/CustomExceptions.php';

	/**
	 * OrdersController class handles all user related actions.
	 * 
	 * Note: All controllers that require authentication must extend the AuthController 
	 * and call $this->authenticate() before executing any other code. This will ensure that no 
	 * other code will execute if the user's session is invalid.
	 */
	class OrdersController extends AuthController {

		/**
		 * @var OrdersModel
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

			$this->model = new ordersModel($this->db);
		}

		/**
		 * Lists all orders
		 * 
		 * @throws ValidationException if validation fails
		 */
		public function readAll() {
			$this->authenticate();

			$offset = intval($this->request->input('offset', 0, 'query'));
			$limit 	= intval($this->request->input('limit', 10, 'query'));
			$result = $this->model->readAll($offset, $limit);
			
			$this->response->success($result);
		}

		/**
		 * Gets the order by id
		 * 
		 * @throws MissingParametersException
		 */
		public function read() {
			$this->authenticate();
			
			$id = $this->request->getParam('id');

			if (empty($id)) {
				throw new MissingParametersException('Order id is required.');
			}
			
			$result = $this->model->read($id);

			$this->response->success($result);
		}

		/**
		 * Gets all the order statuses
		 * 
		 */
		public function readStatuses() {
			$result = $this->model->readStatuses();

			$this->response->success($result);
		}

		/**
		 * Gets the orders by status name
		 * 
		 * @throws MissingParametersException
		 */
		public function readStatus() {
			$name = $this->request->getParam('name');

			if (empty($name)) {
				throw new MissingParametersException('Order status name is required.');
			}
			
			$result = $this->model->readStatus($name);
			
			$this->response->success($result);
		}
	}
