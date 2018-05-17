<?php

	class PortageTree {

		private static $instance;

		// Strings
		protected $tree;

		public function __construct($tree = "/usr/portage") {

			if($tree)
				$this->setTree($tree);
		}

		public static function singleton() {
			if (!isset(self::$instance)) {
				$c = __CLASS__;
				self::$instance = new $c;
			}

			return self::$instance;
		}

		public function getTree() {
			return $this->tree;
		}

		public function setTree($x) {
			if(is_string($x) && is_dir($x))
				$this->tree = $x;
		}

		public function getArches($prefix = false) {

			$filename = $this->getTree().'/profiles/arch.list';
			$arr = file($filename, FILE_IGNORE_NEW_LINES);

			$arr = preg_grep('/^[a-z]/', $arr);
			sort($arr);

			foreach($arr as $value) {
				$arches[] = $value;
			}

			return $arches;

		}

		public function getCategories() {

			$filename = $this->getTree().'/profiles/categories';
			$arr = file($filename, FILE_IGNORE_NEW_LINES);

			$arr = preg_grep('/^[a-z]/', $arr);
			sort($arr);

			foreach($arr as $value) {
				$categories[] = $value;
			}

			return $categories;

		}

		public function getEclasses() {

			$scandir = scandir($this->getTree().'/eclass/');

			$scandir = preg_grep('/\.eclass$/', $scandir);
			sort($scandir);
			foreach($scandir as $filename) {
				$filename = preg_replace("/\.eclass$/", "", $filename);
				$arr[] = $filename;
			}

			return $arr;

		}

		public function getLicenses() {

			$scandir = scandir($this->getTree().'/licenses/');
			$scandir = preg_grep('/^\.{1,2}$/', $scandir, PREG_GREP_INVERT);
			sort($scandir);
			foreach($scandir as $filename) {
				$arr[] = $filename;
			}

			return $arr;

		}

		/** Use Flags **/

		/**
		 * Get the use flags for any file w/use flags
		 *
		 * @param filename filename of use flags
		 * @return array
		 */
		public function arrUseFlags($filename) {

			$arr_file = file($filename, FILE_IGNORE_NEW_LINES);

			$arr_file = preg_grep('/^.+\s+\-\s+/', $arr_file);

			sort($arr_file);

			foreach($arr_file as $str) {
				$arr = explode(' - ', $str);
				$desc = str_replace($arr[0].' - ', '', $str);
				$arr_use_flags[$arr[0]] = $desc;
			}

			return $arr_use_flags;
		}

		/**
		 * Get the global use flags
		 *
		 */
		public function getUseFlags() {
			return array_keys($this->arrUseFlags($this->getTree()."/profiles/use.desc"));
		}

	}

?>
