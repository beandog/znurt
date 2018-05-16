<?php

	class PortageLicense {

		private $name;
		private $pdf;

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
					$this->pdf = true;
				} else {
					$this->name = $str;
					$this->pdf = false;
				}

			}

		}

		public function getName() {
			return $this->name;
		}

		public function isPDF() {
			return $this->pdf;
		}


	}
?>
