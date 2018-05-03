<?php

	/**
	* Create the 'extended version' of the original versions,
	* which is basically numerical version schemes padded
	* with zeroes to be able to be sorted numerically.
	*
	* @param array indexed array of original versions (pv)
	* @return array of extended versions with original version as key
	*/
	function extendVersions($arr) {

		if(!count($arr))
			return array();

		// Check if we have any dots in versions
		// If we don't, it's fine as is -- just pad the zeroes
		if(!(count($arr) && preg_grep('/\./', $arr))) {

			foreach($arr as $value) {
				$max = max($max, strlen($value));
			}

			foreach($arr as $key => $value) {
				$ext[$key] = str_pad($value, $max, 0, STR_PAD_LEFT);
			}

			return $ext;

		}

		// Keep a count of the max number of version breaks (dots)
		// array('1.2', '2.12.3') would have a max of 2.
		$max = 0;
		foreach($arr as $key => $str) {

			$max = max($max, substr_count($str, '.'));
			$arr_extended[$key] = explode('.', $str);
		}

		$max++;

		// Get the max *numerical* lengths for each split
		// 2.12.3 would create: array(1, 2, 1);
		foreach($arr_extended as $tmp) {
			foreach($tmp as $key => $value) {
				$value = preg_replace('/\D/', '', $value);
				$arr_max_strlen[$key] = max($arr_max_strlen[$key], strlen($value));
			}
		}

		// Now pad everything to the extended version
		foreach($arr as $key => $value) {

			// Split the version into each of its dot values
			$explode = explode(".", $value);

			// Pad and/or create the version for each dot version
			for($x = 0; $x < $max; $x++) {

				// Yes, you *have* to pad the first number too.  Otherwise PHP
				// will compare it wrong if there's more than one decimal point.
				if($x == 0)
					$ext[$key] = str_pad($explode[$x], $arr_max_strlen[$x], 0, STR_PAD_LEFT);
				if($x > 0)
					$ext[$key] .= ".".str_pad($explode[$x], $arr_max_strlen[$x], 0, STR_PAD_LEFT);

			}

		}

		asort($ext);

		return $ext;

	}
?>
