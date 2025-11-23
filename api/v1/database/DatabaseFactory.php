<?php

	require_once __DIR__ . './MysqlDatabase.php';
	
	class DatabaseFactory
	{
		public static function getInstance($type, $config = [])
		{
			switch(strtolower($type)) {
				case 'mysql':
					return new MysqlDatabase($config);
				default:
					return new MysqlDatabase($config);
			}
		}
	}
