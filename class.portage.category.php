<?

	class PortageCategory {
	
		private $name;
		protected $dir;
		protected $cache_dir;
		private $metadata;
		private $description;
		
		private $arr_packages;
		
		function __construct($category) {
		
			global $hits;
			$hits['category']++;
		
			$this->setCategory($category, $tree = "/usr/portage");
		
		}
		
		public function __get($var) {
			return $this->$var;
		}
		
		public function __toString() {
			return $this->name;
		}
		
		protected function setCategory($category, $tree) {
		
			$category = basename($category);
			$dir = "$tree/$category";
		
			if(is_dir($dir)) {
				$this->name = $category;
				$this->dir = $dir;
				$this->cache_dir = "$tree/metadata/cache/$category";
				$this->metadata = "$dir/metadata.xml";
			}
		
		}
		
		public function getDescriptions() {
		
			if(!$this->description) {
				// Get metadata
 				$xml = simplexml_load_file($this->metadata);
 				foreach($xml->longdescription as $obj) {
 					$str = trim(preg_replace('/\s+/', ' ', (string)$obj));
 					$lang = (string)$obj['lang'];
					$this->description[$lang] = $str;
				}
			}
		
			return $this->description;
		}
		
		public function getLanguages() {
			return array_keys($this->description);	
		}
	
		public function getPackages() {
		
			if(!$this->arr_packages) {
				$scandir = scandir($this->dir);
 				$scandir = preg_grep('/^\.{1,2}$/', $scandir, PREG_GREP_INVERT);
 				
 				foreach($scandir as $name)
 					if(is_dir($this->dir."/".$name))
 						$arr[] = $name;
 				
 				sort($arr);
 				
 				$this->arr_packages = $arr;
			}
		
			return $this->arr_packages;
		}
		
	}
?>