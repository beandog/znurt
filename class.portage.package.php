<?php

	require_once 'class.portage.tree.php';

	class PortagePackage {

		private $name;
		private $category;
		private $tree;
		private $dir;
		private $portage_mtime;

		private $arr_ebuilds;
		private $arr_herds;
		private $arr_maintainers;

		private $manifest;
		private $manifest_filename;
		private $manifesh_hash;
		private $manifest_mtime;

		private $metadata;
		private $metadata_filename;
		private $metadata_hash;
		private $metadata_mtime;
		private $metadata_xml;

		function __construct($category = null, $package = null, $tree = "/usr/portage") {

			global $hits;
			$hits['package']++;

			if($category && $package && $tree)
				$this->setPackage($category, $package, $tree);

		}

		public function __get($var) {

			switch($var) {

				case 'category':
				case 'dir':
				case 'name':
				case 'package_dir':
				case 'portage_mtime':
				case 'tree':
					return $this->$var;
					break;

				case 'manifest':
					return $this->getManifest();
					break;

				case 'manifest_hash':
					return $this->getManifestHash();
					break;

				case 'manifest_mtime':
					return $this->getManifestMtime();
					break;

				case 'metadata':
					return $this->getMetadata();
					break;

				case 'metadata_hash':
					return $this->getMetadataHash();
					break;

				case 'metadata_mtime':
					return $this->getMetadataMtime();
					break;

			}

		}

		public function __toString() {
			return $this->name;
		}

		private function setPackage($category, $package, $tree) {

			$package = basename($package);
			// Changed the name of package_dir to dir
			$this->dir = $this->package_dir = $dir = "$tree/$category/$package";

			if(is_dir($dir)) {
				$this->name = $package;
				$this->category = $category;
				$this->tree = $tree;
				$this->portage_mtime = filemtime($dir);
			}

			if(file_exists($this->package_dir."/metadata.xml")) {
				$this->metadata_filename = $this->package_dir."/metadata.xml";
			}

			if(file_exists($this->package_dir."/Manifest")) {
				$this->manifest_filename = $this->package_dir."/Manifest";
			}

		}

		public function getCategory() {
			return $this->category;
		}

		public function getEbuilds() {

			if(!$this->arr_ebuilds) {
				$scandir = scandir($this->dir);
 				$arr = preg_grep('/\.ebuild$/', $scandir);
 				$arr = preg_replace("/\.ebuild$/", "", $arr);

 				sort($arr);

 				$this->arr_ebuilds = $arr;
			}

			return $this->arr_ebuilds;
		}

		public function getHerds() {

			$arr = array();

			if(!$this->metadata_filename)
				return $arr;

			$obj =& $this->getMetadataXML();

			if($obj->herd) {
				foreach($obj->herd as $name) {
					$arr[] = (string)$name;
				}
				sort($arr);
			}

			return $arr;

		}

		public function getMaintainers() {

			$arr = array();

			if(!$this->metadata_filename)
				return $arr;

			$obj =& $this->getMetadataXML();

			if($obj->maintainer) {
				$x = 0;
				foreach($obj->maintainer as $maintainer) {
					if($maintainer->name)
						$arr[$x]['name'] = (string)$maintainer->name;
					if($maintainer->email)
						$arr[$x]['email'] = (string)$maintainer->email;
					$x++;
				}
			}

			return $arr;

		}

		public function getManifest() {
			if(!$this->manifest) {
				$this->manifest = file_get_contents($this->manifest_filename);
				$this->manifest_hash = sha1($this->manifest);
				$this->manifest_mtime = filemtime($this->manifest_filename);
			}
			return $this->manifest;
		}

		public function getManifestHash() {
			if(!$this->manifest_hash)
				$this->getManifest();
			return $this->manifest_hash;
		}

		public function getManifestMtime() {
			if(!$this->manifest_mtime)
				$this->getManifest();
			return $this->manifest_mtime;
		}

		public function getMetadata() {
			if(is_null($this->metadata)) {
				$this->metadata = file_get_contents($this->metadata_filename);
				$this->metadata_hash = sha1($this->metadata);
				$this->metadata_xml = simplexml_load_string($this->metadata);
				$this->metadata_mtime = filemtime($this->metadata_filename);
			}

			return $this->metadata;
		}

		public function getMetadataHash() {
			if(!$this->metadata_hash)
				$this->getMetadata();
			return $this->metadata_hash;
		}

		public function getMetadataMtime() {
			if(!$this->metadata_mtime)
				$this->getMetadata();
			return $this->metadata_mtime;
		}

		public function getMetadataXML() {

			if(is_null($this->metadata_xml))
				$this->getMetadata();

			return $this->metadata_xml;

		}

		public function getUseFlags() {

			$arr = array();

			if(!$this->metadata_filename)
				return $arr;

			$obj =& $this->getMetadataXML();

			// Getting attributes is always a pain
			// http://us.php.net/manual/en/function.simplexml-element-attributes.php
			if($obj->use) {
				foreach($obj->use->flag as $flag) {
					foreach($flag->attributes() as $key => $name)  {
						if($key == 'name') {
							$name = (string)$name;
							$arr[$name] = (string)$flag;
						}
					}
				}
			}
			ksort($arr);
			return $arr;

		}

	}
?>
