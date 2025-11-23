<?php
	require_once(CONF_DIR . 'config_env.php');

	/**
	 * Sets the type of error reporting for the different environments
	 * 
	 * @return void
	 */
	function setReporting()
	{
		if (DEVELOPMENT_ENVIRONMENT === true) {
			mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

			error_reporting(E_ALL ^ E_WARNING);
			ini_set('ignore_repeated_errors', 	TRUE); // only ignores within same script run
			ini_set('display_errors',			1);
			ini_set('log_errors', 				1);
			ini_set('error_log', 				LOGS_DIR . 'dev.log');

		} else {
			error_reporting(0);
			ini_set('ignore_repeated_errors', 	TRUE); // only ignores within same script run
			ini_set('display_errors', 			0);
			ini_set('log_errors', 				1);
			//ini_set('error_log', 				LOGS_DIR . 'prod.log');
		}
	}

	setReporting();