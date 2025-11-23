<?php
	interface DatabaseInterface
	{
		public function connect();
		public function close();
		public function execute($sql, $params=[]);
		public function query($sql, $params = []);
		public function fetchFirst($sql, $params = []);
		public function getInsertId();
		public function getAffectedRows(&$stmt);
		public function getChangedRows();
	}
