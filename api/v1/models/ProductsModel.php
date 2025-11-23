<?php

	/**
	 * User Model
	 * 
	 * Handles all database operations for customer resources.
	 * Manages customer contact information and company associations.
	 */

	require_once __DIR__ . '/BaseModel.php';
	require_once __DIR__ . '/../exceptions/CustomExceptions.php';

	class ProductsModel extends BaseModel {
		
		/**
		 * Gets all products
		 * 
		 * @param int $offset
		 * @param int $limit
		 * @return array
		 */
		public function readAll($offset, $limit) {
			return $this->db->query("SELECT * FROM products LIMIT $offset, $limit", []);
		}

		/**
		 * Get product by ID
		 * 
		 * @param int $id
		 * @return object
		 */
		public function read($id) {
			return $this->db->fetchFirst('SELECT * FROM products WHERE id = ?', [ $id ]);
		}

		/**
		 * Get product categories
		 * 
		 * @return array
		 */
		public function readCategories() {
			return $this->db->query('SELECT id, category FROM products GROUP BY category ORDER BY category', []);
		}

		/**
		 * Get products category by name
		 * 
		 * @param string $name
		 * @return array
		 */
		public function readCategory($name) {
			return $this->db->query('SELECT * FROM products WHERE LOWER(category) = ?', [ strtolower($name) ]);

		}

		/**
		 * Updates the product by id
		 * 
		 * @param int $id
		 * @param array $product
		 * @return bool
		 */
		public function update($id, $product) {
			$sql = '';
			$values = [];

			// Loop over product keys to create update statement
			foreach ($product as $key => $value) {
				if ($value !== null) {
					$sql .= ($sql === '' ? '' : ', ') . $key . ' = ?';

					// Store the values for the replacements
					array_push($values, $value);
				}
			}

			if ($sql !== '') {
				array_push($values, $id);
				$sql = 'UPDATE products SET ' . $sql  . ' WHERE id = ?';
				$result = $this->db->execute($sql, $values);
			
				return $result->rowCount() > 0;
			}

			return false;
			
		}

		/**
		 * Deletes the product by ID
		 * 
		 * @param int $id
		 * @return bool
		 */
		public function delete($id) {
			$result = $this->db->execute('DELETE FROM products WHERE id = ?', [ $id ]);
			
			return $result->rowCount() > 0;
		}
	}
