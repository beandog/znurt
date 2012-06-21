<?

	class PackageManifest {
	
		private $arr_files;
		private $arr_entries;
		
		private $package;
		private $category;
		private $tree;
		private $dir;
		private $filename;
		private $mtime;
		
		private $manifest;
		private $hash;
		private $filesize;
	
		function __construct($category = null, $package = null, $tree = "/usr/portage") {
		
			global $hits;
			$hits['manifest']++;
			
			if($category && $package && $tree)
				$this->setPackage($category, $package, $tree);
		
		}
		
		public function __get($var) {
		
			switch($var) {
			
				case 'package':
				case 'category':
				case 'tree':
				case 'dir':
				case 'filename':
					return $this->$var;
					break;
				
				case 'manifest':
					return $this->getManifest();
					break;
					
				case 'mtime':
					return $this->getMtime();
					break;
					
				case 'hash':
					return $this->getHash();
					break;
				
				case 'filesize':
					return $this->getFilesize();
					break;
					
			}
		
		}
		
		public function __toString() {
			return $this->manifest;
		}
		
		private function parse() {
		
			if(!$this->arr_files) {
			
				$this->arr_files = $this->arr_entries = array();
				
				$arr_types = array('AUX', 'DIST', 'EBUILD', 'MISC');
				
				foreach($arr_types as $key) {
					$this->arr_entries[$key] = array();
				}
		
				$contents = file($this->filename);
				
				foreach($contents as $line) {
					$arr = explode(" ", $line);
					
					if(in_array($arr[0], $arr_types)) {
					
						$this->arr_files[$arr[1]] = $this->arr_entries[$arr[0]][$arr[1]] = array(
							'filesize' => $arr[2],
							'rmd160' => $arr[4],
							'sha1' => $arr[6],
							'sha256' => $arr[8],
						);
					}
					
				}
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
			
			if(file_exists($this->dir."/Manifest")) {
				$this->filename = $this->dir."/Manifest";
			}
		
		}
		
		public function getManifest() {
			if(!$this->manifest && $this->filename)
				$this->manifest = file_get_contents($this->filename);
			return $this->manifest;
		}
		
		public function getFilesize($file = 'Manifest') {
			if($file == 'Manifest' && is_null($this->filesize) && $this->filename)
				$str = $this->filesize = filesize($this->filename);
			else {
			
				$this->parse();
					
				$str = $this->arr_files[$file]['filesize'];
			
			}
			return $str;
		}
		
		
		public function getHash($file = 'Manifest', $type = 'sha1') {
		
			// FIXME This is really dumb.
			if($file == 'Manifest') {
				
				if(!$this->hash) {
					$str = $this->hash = sha1($this->getManifest());
				} else {
					$str =& $this->hash;
				}
				
			} else {
				
				$this->parse();
					
				$str = $this->arr_files[$file][$type];
				
			}	
		
			return $str;
		}
		
		public function getMtime() {
			if(!$this->mtime && $this->filename)
				$this->mtime = filemtime($this->filename);
			return $this->mtime;
		}
		
		public function getFiles() {
		
			$this->parse();
			
			return array_keys($this->arr_entries['AUX']);
		}
		
		public function getDistfiles() {
		
			$this->parse();
		
			return array_keys($this->arr_entries['DIST']);
		}
		
		public function getEbuilds() {
		
			$this->parse();
			
			return array_keys($this->arr_entries['EBUILD']);
		}
		
		public function getMisc() {
		
			$this->parse();
		
			return array_keys($this->arr_entries['MISC']);
		}
		
	}
?>