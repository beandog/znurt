<?php

	// Need PEAR::MDB2 and PEAR::MDB2_pgsql
	require_once 'PEAR.php';
	require_once 'MDB2.php';

	$dsn = array(
		'phptype' => 'pgsql',
		'username' => 'znurt',
		'database' => 'portage',
	);

	// If you want to override these generic connection settings, create a
	// file named header.dsn.php and set the variable $dsn
	@include 'header.dsn.php';

	$options = array(
		'debug'       => 2,
		'portability' => MDB2_PORTABILITY_ALL ^ MDB2_PORTABILITY_EMPTY_TO_NULL,
	);

	$db = MDB2::factory($dsn, $options);

	if(PEAR::isError($db))
		die($db->getMessage());

	$db->loadModule('Manager');
	$db->loadModule('Extended');

	$db->setFetchMode(MDB2_FETCHMODE_ASSOC);

	PEAR::setErrorHandling(PEAR_ERROR_DIE);

	function pearError ($e) {
		echo $e->getMessage().': '.$e->getUserinfo();
		echo '\n';
	}

	PEAR::setErrorHandling(PEAR_ERROR_CALLBACK, 'pearError');

?>
