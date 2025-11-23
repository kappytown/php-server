<?php

	/**
	 * Orders Model
	 * 
	 * Handles all database operations for customer resources.
	 * Manages customer contact information and company associations.
	 */

	require_once __DIR__ . '/BaseModel.php';
	require_once __DIR__ . '/../exceptions/CustomExceptions.php';

	class OrdersModel extends BaseModel {
		
		/**
		 * Gets all orders
		 * 
		 * @return array
		 */
		public function readAll($offset, $limit) {
			return $this->db->query("SELECT * FROM orders LIMIT $offset, $limit", []);
		}

		/**
		 * Gets the order by ID
		 * 
		 * @param int $id
		 * @return array
		 */
		public function read($id) {
			return $this->db->query('SELECT o.id as order_id, u.name as customer, o.total, o.status, p.name as product, oi.quantity, oi.price FROM orders o JOIN users u ON o.user_id = u.id JOIN order_items oi ON o.id = oi.order_id JOIN products p ON oi.product_id = p.id WHERE o.id = ?', [ $id ]);
		}

/**
		 * Get all order statuses
		 * 
		 * @return array
		 */
		public function readStatuses() {
			return $this->db->query('SELECT id, status FROM orders GROUP BY status ORDER BY status', []);
		}

		/**
		 * Get the orders by status name
		 * 
		 * @param string $name
		 * @return array
		 */
		public function readStatus($name) {
			return $this->db->query('SELECT * FROM orders WHERE LOWER(status) = ?', [ strtolower($name) ]);
		}
	}
