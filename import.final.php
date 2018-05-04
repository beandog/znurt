<?php

	echo "[Finish Import]\n";

	require_once 'header.php';

	// Reset packages that were updated
	$sql = "UPDATE package SET status = 0 WHERE status = 1;";
	$db->query($sql);

	// Set those that were gonna be updated to be live
	$sql = "UPDATE ebuild SET status = 0 WHERE status = 1;";
	$db->query($sql);

	// Delete the ones flagged for removal
 	$sql = "DELETE FROM ebuild WHERE status IN(2,3);";
 	$db->query($sql);

	// Same for bugs
	$sql = "SELECT COUNT(1) FROM package_bugs WHERE status = 1;";
	$count = $db->getOne($sql);

	if($count) {
		$sql = "DELETE FROM package_bugs WHERE status = 0;";
		$db->query($sql);

		$sql = "UPDATE package_bugs SET status = 0 WHERE status = 1;";
		$db->query($sql);
	}

	// Check for package_recent replacements
	$sql = "SELECT COUNT(1) FROM package_recent WHERE status = 1;";
	$count = $db->getOne($sql);

	if($count) {
		$sql = "DELETE FROM package_recent WHERE status = 0;";
		$db->query($sql);

		$sql = "UPDATE package_recent SET status = 0 WHERE status = 1;";
		$db->query($sql);
	}

	$sql = "SELECT COUNT(1) FROM package_recent_arch WHERE status = 1;";
	$count = $db->getOne($sql);

	if($count) {
		$sql = "DELETE FROM package_recent_arch WHERE status = 0;";
		$db->query($sql);

		$sql = "UPDATE package_recent_arch SET status = 0 WHERE status = 1;";
		$db->query($sql);
	}

	// Check for package_mask replacements
	$sql = "SELECT COUNT(1) FROM package_mask WHERE status = 1;";
	$count = $db->getOne($sql);

	if($count) {
		// Foreign key will remove any ebuild_mask entries as well
		$sql = "DELETE FROM package_mask WHERE status = 0;";
		$db->query($sql);

		$sql = "UPDATE package_mask SET status = 0 WHERE status = 1;";
		$db->query($sql);

	}

	$sql = "UPDATE ebuild_mask SET status = 0 WHERE status = 1;";
	$db->query($sql);

 	// Update the arches to make sure the ones who have ebuilds are active
 	$sql = "UPDATE arch SET active = (SELECT CASE WHEN id IN (SELECT DISTINCT a.id FROM arch a JOIN ebuild_arch ea ON a.id = ea.arch AND ea.status != 2) THEN true ELSE false END);";
 	$db->query($sql);

 	// Finalize the import
 	if($cron) {
		$sql = "INSERT INTO znurt (action) VALUES ('finish_import');";
		$db->query($sql);
	}

	if(!$cron) {
		shell::msg("Total hits:");
		print_r($hits);
	}

	// Update status
	if($import_id) {
		$sql = "UPDATE import_status SET status = 'finish', udate = NOW() WHERE id = ".$db->quote($import_id).";";
		$db->query($sql);
	}

?>
