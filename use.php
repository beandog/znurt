<?
	
	require_once 'class.portage.tree.php';
	require_once 'class.portage.use_flag.php';
	require_once 'class.shell.php';

	$u = new PortageUseFlag('local');
	
	print_r($u->getUseFlags());
	
?>
