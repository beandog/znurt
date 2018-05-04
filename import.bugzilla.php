<?php

	echo "[Bugzilla]\n";

	/**
	 * The bugzilla script grabs a CSV export from Gentoo's bugzilla and inserts them into the database.
	 *
	 * The CSV export seems to be non-negotiable in asking for columns, so you're stuck with what you get.
	 *
	 * Only executes once a day.  Goes rather fast, for the most part.  Could probably use some curl error
	 * checking in case the site is down, unreachable or slow to respond.
	 */

	require_once 'header.php';
	if(!$tree) {
		$tree =& PortageTree::singleton();
	}

	$import_bugzilla = false;

	$sql = "SELECT COUNT(1) FROM package_bugs WHERE status = 0;";
	$count = $db->getOne($sql);

	if(!$count)
		$import_bugzilla = true;
	else {
		// Only run this one once a day
		$sql = "SELECT interval '1 day' + MAX(idate) < NOW() FROM package_bugs WHERE status = 0;";
		$bool = $db->getOne($sql);
		if($bool == 't')
			$import_bugzilla = true;
	}

	if($import_bugzilla) {

		$sql = "DELETE FROM bugzilla;";
		$db->query($sql);

		$sql = "DELETE FROM package_bugs WHERE status = 1;";
		$db->query($sql);

		if($verbose) {
			shell::msg("importing bugs");
		}

		$arr_categories = $tree->getCategories();

		$arr_keys = array('bug_id', 'bug_severity', 'priority', 'op_sys', 'assigned_to', 'bug_status', 'resolution', 'short_short_desc');

		foreach($arr_categories as $category_name) {

			if($debug)
				shell::msg("bugs: $category_name");

			$url = "http://bugs.gentoo.org/buglist.cgi?bug_file_loc=&bug_file_loc_type=allwordssubstr&bug_id=&bug_status=UNCONFIRMED&bug_status=NEW&bug_status=ASSIGNED&bug_status=REOPENED&bugidtype=include&chfieldfrom=&chfieldto=Now&chfieldvalue=&email1=&email2=&emailtype1=exact&emailtype2=substring&field-1-0-0=product&field-1-1-0=bug_status&field-1-2-0=short_desc&field0-0-0=assigned_to&field0-1-0=assigned_to&field0-2-0=short_desc&keywords=&keywords_type=allwords&long_desc=&long_desc_type=allwordssubstr&product=Gentoo%20Linux&query_format=advanced&remaction=&short_desc=$category_name%2F&short_desc_type=allwordssubstr&status_whiteboard=&status_whiteboard_type=allwordssubstr&type-1-0-0=anyexact&type-1-1-0=anyexact&type-1-2-0=allwordssubstr&type0-0-0=notequals&type0-1-0=notequals&type0-2-0=notsubstring&value-1-0-0=Gentoo%20Linux&value-1-1-0=UNCONFIRMED%2CNEW%2CASSIGNED%2CREOPENED&value-1-2-0=media-video%2F&value0-0-0=maintainer-wanted%40gentoo.org&value0-1-0=maintainer-needed%40gentoo.org&value0-2-0=new%20package&votes=&ctype=csv";

			$ch = curl_init($url);

			$filename = tempnam("/tmp", "bugzilla");

			$fp = fopen($filename, "w");

			// FIXME check for curl errors
			curl_setopt($ch, CURLOPT_FILE, $fp);
			curl_setopt($ch, CURLOPT_HEADER, 0);

			curl_exec($ch);
			curl_close($ch);
			fclose($fp);

			if(filesize($filename)) {

				$row = 1;

				if (($handle = fopen($filename, "r")) !== false) {

					while(($data = fgetcsv($handle, 0, ",")) !== false) {

						if($row > 1) {
							$arr_insert = array_combine($arr_keys, $data);
							$db->autoExecute('bugzilla', $arr_insert, MDB2_AUTOQUERY_INSERT);
						}

						$row++;

					}
					fclose($handle);
				}

			}

			unlink($filename);

		}

		// FIXME this query takes a while to run
		// FIXME Have this run after each category is imported, and query for that $cp.  That'll go much faster.
		$sql = "INSERT INTO package_bugs (bug, package, description, status) SELECT b.bug_id AS bug, p.id AS package, b.short_short_desc, 1 FROM package p INNER JOIN category c ON p.category = c.id INNER JOIN bugzilla b ON b.short_short_desc LIKE ('%' || c.name || '/' || p.name || '%');";
		$db->query($sql);

	} else {

		if($verbose)
			shell::msg("Not importing bugs");

	}


?>
