<?php

	$filename = "/tmp/bugs.csv";
	
	if (($handle = fopen($filename, "r")) !== FALSE) {
	
		while(($data = fgetcsv($handle, 0, ",")) !== FALSE) {
		
			print_r($data);
		
		}
		fclose($handle);
	}

?>
