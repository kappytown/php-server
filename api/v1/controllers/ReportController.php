<?php
	require_once __DIR__ . '/AuthController.php';
	require_once __DIR__ . '/../models/ReportModel.php';
	require_once __DIR__ . '/../exceptions/CustomExceptions.php';
	
	/**
	 * Report Controller
	 * 
	 * Handles CRUD operations for report resources.
	 * Reports can store structured data in JSON format for analytics and reporting.
	 */
	class ReportController extends AuthController {

		public function __construct($db, $req, $res) {
			parent::__construct($db, $req, $res);

			$this->model = new ReportModel($this->db);
		}

		/**
		 * This will route the request based off of the report id
		 * 
		 * @throws NotFoundException
		 */
		public function index() {
			$reportId = $this->request->getParam('reportId');
			
			if (method_exists($this, $reportId)) {
				$this->$reportId();
			} else {
				throw new NotFoundException('Report not found');
			}
		}

		/**
		 * Gets the order status
		 */
		public function orderStats() {
			$this->authenticate();

			$result = $this->model->orderStats($this->userId);
			
			$this->response->success($result);
		}

		/**
		 * Gets the top products
		 */
		public function topProducts() {
			$this->authenticate();

			$result = $this->model->topProducts($this->userId);
			
			$this->response->success($result);
		}

		/**
		 * Gets the recent orders
		 */
		public function recentOrders() {
			$this->authenticate();

			$result = $this->model->recentOrders($this->userId);
			
			$this->response->success($result);
		}
	}
