<?php

	class PortageLicense {

		private $name;

		function __construct($license = null) {

			if($license)
				$this->setLicense($license);

		}

		public function setLicense($str) {

			$str = basename($str);

			$tree = PortageTree::singleton();

			if(file_exists($tree->getTree()."/licenses/$str")) {

				if(substr($str, -4, 4) == ".pdf") {
					$this->name = basename($str, ".pdf");
				} else {
					$this->name = $str;
				}

			}

		}

		public function getName() {
			return $this->name;
		}

	}

?>
