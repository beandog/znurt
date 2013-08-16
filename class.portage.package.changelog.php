<?php

	class PackageChangelog {
	
		private $recent_changes;
		private $recent_date;
		
		private $package;
		private $category;
		private $tree;
		private $dir;
		private $filename;
		private $mtime;
		
		private $changelog;
		private $hash;
		private $filesize;
		private $file_exists;
		
		public function __construct($category = null, $package = null, $tree = "/usr/portage") {
		
			global $hits;
			@$hits['changelog']++;
			
			if($category && $package && $tree) {
				$this->setPackage($category, $package, $tree);
			}
		
		}
		
		public function __toString() {
			return $this->getChangelog();
		}
		
		public function __get($var) {
		
			switch($var) {
			
				case 'package':
				case 'category':
				case 'tree':
				case 'dir':
				case 'filename':
				case 'recent_date':
					return $this->$var;
					break;
				
				case 'mtime':
					return $this->getMtime();
					break;
					
				case 'changelog':
					return $this->getChangelog();
					break;
					
				case 'hash':
					return $this->getHash();
					break;
				
				case 'filesize':
					return $this->getFilesize();
					break;
					
				case 'recent_changes':
					return $this->getRecentChanges();
					break;
			
			}
		
		}
		
		private function setPackage($category, $package, $tree) {
			
			$category = basename($category);
			$package = basename($package);
			
			$this->dir = "$tree/$category/$package";
		
			if(is_dir($this->dir)) {
				$this->package = $package;
				$this->category = $category;
				$this->tree = $tree;
			}
			
			if(file_exists($this->dir."/ChangeLog")) {
				$this->file_exists = true;
				$this->filename = $this->dir."/ChangeLog";
			} else {
				$this->file_exists = false;
			}
		
		}
		
		public function getChangelog() {
			if(!$this->changelog && $this->filename)
				$this->changelog = file_get_contents($this->filename);
			return $this->changelog;
		}
		
		public function getFilesize() {
			if(is_null($this->filesize) && $this->filename)
				$this->filesize = filesize($this->filename);
			return $this->filesize;
		}
		
		public function getHash() {
			if(!$this->hash && $this->filename)
				$this->hash = sha1($this->getChangelog());
			return $this->hash;
		}
		
		public function getMtime() {
			if(!$this->mtime && $this->filename)
				$this->mtime = filemtime($this->filename);
			return $this->mtime;
		}
		
		
		function getRecentChanges() {
		
			$pattern_date = "/^\d{1,2}\s\w{3}\s\d{4}/";
// 			$pattern_dev = "/<\w+@gentoo\.org>/";

			$changelog = $this->getChangelog();
			$changelog = trim($changelog);

			if(!strlen($changelog))
				return '';
		
 			$arr = explode("\n", $changelog);
			
			// Cut off the header
 			$arr = array_slice($arr, 4);
 			
 			// Get the date of the latest changes
 			$str = trim($arr[0]);
 			
 			preg_match_all($pattern_date, $str, $matches);
 			$this->recent_date = $date = current(current($matches));
			
			$start = false;
			
			$recent_changes = "";
			
 			foreach($arr as $str) {
 				
 				$first_char = substr($str, 0, 1);
 				$last_char = substr($str, -1, 1);
 				
 				if(($first_char == "*" || empty($str)) && $start) {
 					break;
 				}
 				
 				if($start) {
 					$recent_changes .= " ".trim($str);
 				}
 				
 				if($last_char == ":") {
 					$start = true;
 				}
 				
 			}
 			
 			$recent_changes = trim($recent_changes);
 			
 			return $recent_changes;
 			
		}
		
	
	}

?>
