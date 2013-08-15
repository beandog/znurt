<?php

	/*
	 * Create a PDO instance to connect to the PostgreSQL database
	 */

	// Specific connection settings
	@include_once 'header.dsn.php';

	try {
		$dbh = new PDO($pdo_dsn);
	} catch(PDOException $e) {
		echo 'PDO connection failed: '.$e->getMessage();
		echo "\n";
	}

	$dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

?>
