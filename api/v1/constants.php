<?php
	define('DS', 				DIRECTORY_SEPARATOR);
	define('ROOT_DIR', 			__DIR__ 	. DS);
	define('SERVER_DIR', 		ROOT_DIR 	. '..' 			. DS . '..' . DS);
	define('API_DIR', 			SERVER_DIR 	. 'api' 		. DS);
	define('THIRD_PARTY_DIR', 	API_DIR 	. 'third_party' . DS);
	define('CONF_DIR', 			ROOT_DIR 	. 'conf' 		. DS);
	define('CONTROLLERS_DIR', 	ROOT_DIR 	. 'controllers' . DS);
	define('LOGS_DIR', 			ROOT_DIR 	. 'tmp' 		. DS . 'logs' . DS);
	
	define('API_PATH',			'/php-server/api/v1');