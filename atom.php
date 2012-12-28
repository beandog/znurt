<?php

	// Testing parsing atoms

	require_once 'class.portage.ebuild.php';
	require_once 'class.portage.atom.php';
	require_once '/home/steve/php/inc/class.shell.php';

	$str = 'x11-misc/seyon';
	$str ='x11-libs/gtk-canvas';
	$str = 'media-fonts/font-adobe-100dpi-1:1[use]';
// 	$str = 'dev-libs/openssl-0.9.8l-r2';
	$str = "net-dialup/mgetty-1.1.36-r3";

	$e = new PortageEbuild($str);
	

 	shell::msg("atom: ".$e->atom);
 	shell::msg("pn: ".$e->pn);
 	shell::msg("pf: ".$e->pf);
	shell::msg("_alpha: ".$e->_alpha);
	shell::msg("_beta: ".$e->_beta);
	shell::msg("_pre: ".$e->_pre);
	shell::msg("_rc: ".$e->_rc);
	shell::msg("_p: ".$e->_p);
	shell::msg("pr: ".$e->pr);
	shell::msg("version: ".$e->version);
	shell::msg("mtime: ".$e->mtime);
	
// 	shell::msg($e->getSuffix("_p"));
// 	
// 	shell::msg("mtime: ".$e->mtime);
	
 	#print_r($e->metadata());


// 	var_dump($e->_alpha);
// 	var_dump($e->_beta);

// 	$e->getSuffix('_beta');
	
	
//  	var_dump($e->version);
// 	var_dump($e->_alpha);
// 	var_dump($e->_beta);

	$e->getComponents();
	
//  	var_dump($e->arr_components);
 	
//  	var_dump($e->getPackageVersionMinusRevision());
	
	
	shell::msg("Class Atom");
	$a = new PortageAtom($str);
	
// 	shell::msg("atom: ".$a->atom);
  	shell::msg("pn: ".$a->getPackageName());
 	shell::msg("pf: ".$a->pf);
	shell::msg("_alpha: ".$a->_alpha);
	shell::msg("_beta: ".$a->_beta);
	shell::msg("_pre: ".$a->_pre);
	shell::msg("_rc: ".$a->_rc);
	shell::msg("_p: ".$a->_p);
	shell::msg("pr: ".$a->pr);
	shell::msg("version: ".$a->version);
	
?>
