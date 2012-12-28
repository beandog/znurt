<?php

	class PortageUseFlag {
	
		private $name;
		private $description;
		private $global;
		private $local;
		
		private $arr_use_flags;
	
		function __construct($type = 'global', $name = "") {
		
			global $hits;
			$hits['use_flag']++;
			
			$tree =& PortageTree::singleton();
		
			$dir = $tree->getTree()."/profiles/";
		
			switch($type) {
				
				case 'global':
					$this->type = $type;
					$this->filename = $dir."use.desc";
					break;
				
				case 'local':
					$this->type = $type;
					$this->filename = $dir."use.local.desc";
					break;
				
				case 'expand':
					$this->type = $type;
					$name = basename($name);
					$this->filename = $dir."desc/$name.desc";
					
					if(file_exists($this->filename)) {
						$this->prefix = $name;
					}
					
					break;
				
			}
		
		
		}
		
		public function getUseFlags() {
		
				return $this->arrUseFlags($this->filename);
			
		}
		
		public function arrUseFlags($filename) {
			
			$arr_file = file($filename, FILE_IGNORE_NEW_LINES);
			
			$arr_file = preg_grep('/^.+\s+\-\s+/', $arr_file);
			
			sort($arr_file);
			
			foreach($arr_file as $str) {
			
				if($this->type == 'local') {
					
					$tmp = explode(":", $str);
					$package = array_shift($tmp);
					$str = implode(":", $tmp);
					
				}
					
// 				$arr = explode(" - ", $str);
				$arr = preg_split("/\s+-\s+/", $str);
				
				$name = array_shift($arr);
				$description = implode(" - ", $arr);
				
				if($this->prefix) {
					$name = $this->prefix."_$name";
					$arr_use_flags[$name]['prefix'] = $this->prefix;
				}
				
				if($package) {
					$arr_use_flags[$package][$name]['description'] = $description;
				} else {
					$arr_use_flags[$name]['description'] = $description;
				}
				
			}
			
			return $arr_use_flags;
		}
		
	
	}
?>
