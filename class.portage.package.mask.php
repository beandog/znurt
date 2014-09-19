<?php

	class PackageMask {

		private $filename;
		private $mtime;

		function __construct($profile = 'portage') {

			global $hits;
			$hits['package_mask']++;

			$tree =& PortageTree::singleton();

			switch($profile) {

				case 'portage':
					$filename = 'package.mask';
					break;

			}

			$this->filename = $tree->getTree()."/profiles/$filename";
			$this->mtime = filemtime($this->filename);

		}

		function __get($var) {
			return $this->$var;
		}

		function getMaskedPackages() {

			$arr = file($this->filename, FILE_IGNORE_NEW_LINES);

			$arr = preg_grep("/(^#|^$)/", $arr, PREG_GREP_INVERT);

			sort($arr);

			return $arr;

		}

	}

?>
