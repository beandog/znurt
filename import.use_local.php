<?php

	echo "[Local USE Flags]\n";

	require_once 'header.php';

	if(!$tree) {
		$tree = PortageTree::singleton();
	}

	require_once 'class.portage.use_flag.php';
	require_once 'class.portage.ebuild.php';
	require_once 'class.portage.atom.php';
	require_once 'class.db.mtime.php';
	require_once 'class.db.use.php';
	require_once 'class.db.package_use.php';
	require_once 'File/Find.php';

  	// Local use flags
 	$type = 'local';
 	$u = new PortageUseFlag($type);
  	$filemtime = filemtime($u->filename);
  	$arr_use_flags[$type] = $u->getUseFlags();
  	$keys = array_keys($arr_use_flags[$type]);

  	$dbmtime = new DBMtime($u->filename);

  	$import = false;

  	if(is_null($dbmtime->mtime)) {
		$dbmtime->mtime = $dbmtime->filemtime;
		$import = true;
	} elseif($filemtime > $dbmtime->mtime) {
		$dbmtime->mtime = $filemtime;
		$import = true;
	}

  	if($import) {
		foreach($arr_use_flags[$type] as $cp => $arr_package_use_flags) {

			echo "\033[K";
			echo "* Progress: $cp\r";

			$sql = "SELECT package FROM view_package WHERE cp = ".$db->quote($cp).";";
			$package = current(pg_fetch_row(pg_query($sql)))

			foreach($arr_package_use_flags as $name => $arr) {
				extract($arr);

				$dbuse = new DBUse($name, 'local', $cp);

				$dbpackage_use = new DBPackageUse($package, $dbuse->id);

				if($dbpackage_use->description != $description)
					$dbpackage_use->description = $description;


			}
		}
	}

?>
