<?php

	/**
	 * User Controller
	 * 
	 * Handles CRUD operations for customer resources.
	 * Manages customer contact information and company details.
	 */

	require_once __DIR__ . '/AuthController.php';
	require_once __DIR__ . '/../models/ProductsModel.php';
	require_once __DIR__ . '/../exceptions/CustomExceptions.php';

	/**
	 * UserController class handles all user related actions.
	 * 
	 * Note: All controllers that require authentication must extend the AuthController 
	 * and call $this->authenticate() before executing any other code. This will ensure that no 
	 * other code will execute if the user's session is invalid.
	 */
	class ProductsController extends AuthController {

		/**
		 * @var ProductsModel
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

			$this->model = new ProductsModel($this->db);
		}

		/**
		 * Lists all products
		 * 
		 */
		public function readAll() {
			$offset = intval($this->request->input('offset', 0, 'query'));
			$limit 	= intval($this->request->input('limit', 10, 'query'));
			$result = $this->model->readAll($offset, $limit);

			$this->response->success($result);
		}

		/**
		 * Gets the product by id
		 * 
		 * @throws MissingParametersException
		 */
		public function read() {
			$id = intval($this->request->getParam('id'));

			if (empty($id)) {
				throw new MissingParametersException('Product id is required.');
			}
			
			$result = $this->model->read($id);

			$this->response->success($result);
		}

		/**
		 * Gets all the product categories
		 * 
		 */
		public function readCategories() {
			$result = $this->model->readCategories();

			$this->response->success($result);
		}

		/**
		 * Gets the product by category name
		 * 
		 * @throws MissingParametersException
		 */
		public function readCategory() {
			$name = $this->request->getParam('name');

			if (empty($name)) {
				throw new MissingParametersException('Product category name is required.');
			}
			
			$result = $this->model->readCategory($name);

			$this->response->success($result);
		}

		/**
		 * Updates the product by id
		 * 
		 * @throws MissingParametersException
		 * @throws ValidationException
		 */
		public function update() {
			$this->authenticate();

			$id = intval($this->request->getParam('id'));
			$product = [
				'name' 		=> $this->request->getSanitizedInput('name', null),
				'desc' 		=> $this->request->getSanitizedInput('description', null),
				'price' 	=> $this->request->getSanitizedInput('price', null, 'float'),
				'stock' 	=> $this->request->getSanitizedInput('stock', null, 'int'),
				'category' 	=> $this->request->getSanitizedInput('category', null),
				'image_url' => $this->request->getSanitizedInput('image_url', null),
				'is_active' => $this->request->getSanitizedInput('is_active', null, 'boolean')
			];

			if (empty($id)) {
				throw new MissingParametersException('Product id is required.');
			}

			$result = $this->model->update($id, $product);

			if (!$result) {
				throw new ValidationException('Failed to update product.');
			}

			$this->response->success([]);
		}

		/**
		 * Deletes the product by id
		 * 
		 * @throws MissingParametersException
		 * @throws ValidationException
		 */
		public function delete() {
			$this->authenticate();

			$id = intval($this->request->getParam('id'));

			if (empty($id)) {
				throw new MissingParametersException('Product id is required.');
			}

			$result = $this->model->delete($id);

			if (!$result) {
				throw new ValidationException('Failed to delete product.');
			}

			$this->response->success([]);
		}
	}
