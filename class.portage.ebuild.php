<?php

	require_once 'class.portage.tree.php';

	/**
	 * Notes on the standard
	 *
	 * =foo-bar/baz_2-1.2.3_alpha0
	 *
	 * Package names (baz_2), if they end in a number, will never stop on that
	 * number with a dash (-2 vs _2).  So, you can always infer that the
	 * version will start with the first dash and integer. (-1.2.3..)
	 */

	class PortageEbuild {

		// Versioning
		private $version;
		private $_alpha;
		private $_beta;
		private $_pre;
		private $_rc;
		private $_p;

		private $atom;
		private $slot;

		// Ebuild Variables
		// P = package (vim)
		// V = version + suffix (6.3_beta3)
		// R = revision (r1)

		// PV
		private $p;		// Package name and version (excluding revision, if any), for example vim-6.3_beta3.
		// PVR
		private $pf;	// Full package name, ${PN}-${PVR}, for example vim-6.3_beta3-r1.
		// P
		private $pn;	// Package name, for example vim.
		// R
		private $pr;	// Package revision, or 0 if no revision exists.
		// V
		/** Confirmed, this includes any suffices (beta, alpha, rc, etc.) */
		private $pv;	// Package version (excluding revision, if any), for example 6.3_beta3.
		// VR
		private $pvr;	// Package version and revision (if any), for example 6.3_beta3, 6.3_beta3-r1.

		// File properties
		private $dir;
		private $filename;
		private $filename_cache;
		private $filesize;
		private $source;
		private $portage;
		private $cache;
		private $manifest_filename;
		private $basename;

		private $category;

		private $arr_metadata;
		private $arr_metadata_keys;
		private $arr_versions;
		public $arr_elements;
		private $has_version;

		// File mtimes
		private $portage_mtime;
		private $changelog_mtime;
		private $metadata_mtime;
		private $cache_mtime;

		// hash sums
		private $hash;



		function __construct($str) {

			$tree = PortageTree::singleton();

			$this->atom = trim($str);
			$this->arr_suffix = array('alpha', 'beta', 'rc', 'pre', 'p');
			$this->portage = $tree->getTree();
			$this->cache = $this->portage.'/metadata/md5-cache';

			$this->has_version = $this->hasVersion();

			/** Cleaning up **/
			$this->category = $this->getCategory();
			$this->pn = $this->getPackageName();
			$this->pvr = $this->getPackageVersionAndRevision();
			$this->slot = $this->getSlot();
			$this->p = $this->getPackageNameAndVersionMinusRevision();
			/**/

			$this->dir = $this->portage."/".$this->category."/".$this->pn;
			$this->manifest_filename = $this->dir."/Manifest";

			$this->basename = $this->getFullPackageName().".ebuild";
			$this->filename = $this->dir."/".$this->basename;

			$this->filename_cache = $this->cache."/".$this->category."/".$this->getFullPackageName();

			if(file_exists($this->filename_cache))
				$this->cache_mtime = filemtime($this->filename_cache);

			$this->arr_metadata_keys = array('depend', 'rdepend', 'slot', 'src_uri', 'restrict', 'homepage', 'license', 'description', 'keywords', 'inherited', 'iuse', 'cdepend', 'pdepend', 'provide', 'eapi', 'properties', 'defined_phases');


			$this->arr_elements = array();

		}

		public function __get($var) {

			if(is_null($this->$var)) {

				if(in_array($var, $this->arr_metadata_keys)) {

					if(is_null($this->arr_metadata))
						$this->arr_metadata = $this->metadata();
					return $this->arr_metadata[$var];
				}

				switch($var) {

					// Suffixes
					case 'version':
					case '_alpha':
					case '_beta':
					case '_pre':
					case '_rc':
					case '_p':
						return $this->getSuffix($var);

					// 'r' is the only one that could
					// get a bit confusing, since there's so many
					// other ways to get these.
					// 'pr' is the correct way, since portage
					// has a stored variable for it.
					case 'pr':
					case 'r':
					case '_r':
					case 'revision':
						return $this->getSuffix('pr');
						break;

					// Other
					case 'category':
						return $this->category;
						break;

					case 'slot':
						return $this->slot;
						break;

					// Ebuild Variables
					case 'p':
						return $this->p;
						break;

					case 'pn':
					case 'package':
						return $this->pn;
						break;

					case 'pf':
						return $this->getFullPackageName();
						break;

					case 'pv':
						return $this->getPackageVersionMinusRevision();
						break;

					case 'pvr':
						return $this->pvr;
						break;

					case 'portage_mtime':
						return $this->getMtime();
						break;

					case 'filename':
						return $this->filename;
						break;

					case 'filesize':
						return $this->getFilesize();
						break;

					case 'source':
						return $this->source = file_get_contents($this->filename);
						break;

					case 'hash':
						return $this->hash = $this->getHash();
						break;

				}

			}

			return $this->$var;
		}

		public function __toString() {
			return $this->pf;
		}

		/**
		 * Gather information about the ebuild
		 * from the metadata cache
		 */
		function metadata() {

			if(!is_null($this->arr_metadata))
				return $this->arr_metadata;

			foreach($this->arr_metadata_keys as $metadata_key)
				$arr_metadata[$metadata_key] = '';

			if(!file_exists($this->filename) || !file_exists($this->filename_cache))
				return null;

			$file = file($this->filename_cache, FILE_IGNORE_NEW_LINES);

			// Kill off the empty lines
			$arr = array_slice($file, 0, 17, true);

			foreach($this->arr_metadata_keys as $metadata_key) {

				$pattern = "/^_?${metadata_key}?=/i";
				$arr_grep = preg_grep($pattern, $arr);
				if(count($arr_grep)) {
					$str = current($arr_grep);

					$arr_slice = array_slice(explode('=', $str), 1);
					$str = implode('=', $arr_slice);

					$arr_metadata[$metadata_key] = $str;
				}
			}

			return $arr_metadata;

		}


		function getCategory() {

			$arr = explode("/", $this->atom);

			if(!count($arr) || count($arr) == 1)
				return '';

			$str = current($arr);

			return $str;

		}

		function getFullPackageName() {

			$var = 'pf';

			if($this->has_version)
				return $this->$var = $this->pn."-".$this->pvr;
			else
				return "";

		}

		function getPackageNameAndVersionMinusRevision() {

			$arr = $this->getElements();

			$p = $this->pn."-".$arr['pv'];

			return $p;

		}

		function getPackageVersionMinusRevision() {

			$var = 'pv';

			if(!$this->has_version)
				return $this->$var = "";

			$arr = $this->getElements();

 			$str = $arr['version'];

 			foreach($this->arr_suffix as $tmp) {

 				if(!is_null($arr[$tmp])) {

 					// Can't use empty, since it will break on
 					// _suffix0
 					if(strlen($arr[$tmp]) == 0)
 						$str .= "_$tmp";
 					else
 						$str .= "_$tmp".$arr[$tmp];
 				}
 			}

			return $this->$var = $str;

		}

		function getPackageVersionAndRevision() {

			if(!$this->has_version)
				return '';

 			$arr = $this->getElements();

 			$str = $this->getPackageVersionMinusRevision();

			if($arr['pr'])
				$str .= "-r".$arr['pr'];

			return $str;

		}

		function getSlot() {

			$str = '';

			if(strpos($this->atom, ':') > 0) {
				$arr = explode(':', $this->atom);
				$str = end($arr);
			}

			return $str;

		}

		// This could really use a better name.
		function getSuffix($var) {

			if(!in_array($var, array('_alpha', '_beta', '_pre', '_rc', '_p', 'pr', 'version')))
				return null;

			$arr = $this->getElements();
			if($var[0] == "_")
				$str = str_replace("_", "", $var);
			return $this->$var = $arr[$str];

		}

		/**
		 * Simplified way to get specific version information
		 */
		function getElements() {

			if(count($this->arr_elements)) {
				return $this->arr_elements;
			}

			$arr_elements = array(
				'pf' => '',
				'pv' => '',
				'pr' => null,
				'pvr' => '',
				'alpha' => null,
				'beta' => null,
				'pre' => null,
				'rc' => null,
				'p' => null,
				'version' => '',
			);

			if(!$this->has_version) {
				return $arr_elements;
			}

			$str = $this->stripPackage($this->atom);

			$arr = explode("-", $str);

			// We might be done at this point, depending on
			// the details of the atom passed in.
			if(!count($arr))
				return $this->arr_elements = $arr_elements;

 			$arr_elements['pv'] = array_shift($arr);

			// Have the exploded one first so the version is the first value
 			$arr = array_merge(explode("_", $arr_elements['pv']), $arr);

			// This format of the version isn't used in portage anywhere,
			// but it could be useful for package.masks or something
			// similar.  It's basically the version without any of the
			// suffices.  (vim-6.3_beta3 => 6.3)
			$arr_elements['version'] = $this->version = array_shift($arr);

			// See if we have more
			if(count($arr)) {

				foreach($arr as $str) {

					// Each value is returned as a trimmed string *if* it
					// can find that there is a suffix version of it.  The
					// reason for this is because there are three possible
					// outcomes: the value is not set (null), it is set, but
					// there is no number (empty string), or it has a value
					// (integer).

					if(substr($str, 0, 5) == "alpha")
						$arr_elements['alpha'] = $this->_alpha = trim(substr($str, 5));
					elseif(substr($str, 0, 4) == "beta")
						$arr_elements['beta'] = $this->_beta = trim(substr($str, 4));
					elseif(substr($str, 0, 3) == "pre")
						$arr_elements['pre'] = $this->_pre = trim(substr($str, 3));
					elseif(substr($str, 0, 2) == "rc")
						$arr_elements['rc'] = $this->_rc = trim(substr($str, 2));
					// Shouldn't need the extra checks for pre/rc since the
					// whole thing is going to look at each string once, and
					// in order .. but weirder things have happened.  More
					// checks never hurt.
					elseif($str[0] == "p" && !(substr($str, 0, 3) == "pre"))
						$arr_elements['p'] = $this->_p = trim(substr($str, 1));
					elseif($str[0] == "r" && !(substr($str, 0, 2) == "rc"))
						$arr_elements['pr'] = $this->pr = trim(substr($str, 1));

				}
			}

			return $this->arr_elements = $arr_elements;

		}

		function getHash() {

			if(!$this->hash) {
				$contents = file_get_contents($this->filename);
				$this->hash = sha1($contents);
			}

			return $this->hash;

		}

		public function getFilesize() {

			if(!$this->filesize) {
				$this->filesize = filesize($this->filename);
			}

			return $this->filesize;

		}

		// Duplicate code during cleanup
		function getPackageName() {

			$str = $this->stripCategory();
			$str = $this->stripSlot($str);

			// This pattern makes ONE grand assumption:
			// That a version that has both digits and letters (see ([A-Za-z])? ) that there is
			// ONLY one letter (fex: openssl-0.9a).  This lets us catch the pn properly of
			// atoms like font-adobe-100dpi, where it would normally think 100dpi = version.
			$pattern = '/\-\d+((\.?\d+)+)?([A-Za-z])?((_(alpha|beta|pre|rc|p)\d*)+)?(\-r\d+)?(\:.+)?([.+])?$/';
			$arr = preg_split($pattern, $str);

			$pn = current($arr);

			return $pn;

		}

		function hasVersion() {

			$str = $this->stripCategory();
			$str = $this->stripSlot($str);

			// This pattern makes ONE grand assumption:
			// That a version that has both digits and letters (see ([A-Za-z])? ) that there is
			// ONLY one letter (fex: openssl-0.9a).  This lets us catch the pn properly of
			// atoms like font-adobe-100dpi, where it would normally think 100dpi = version.
 			$pattern = '/\-\d+((\.?\d+)+)?([A-Za-z])?((_(alpha|beta|pre|rc|p)\d*)+)?(\-r\d+)?(\:.+)?([.+])?$/';
 			$arr = preg_split($pattern, $str);

 			$this->pn = $arr[0];

 			// Check to see if it has a version or not (p.mask)
 			if(count($arr) == 1) {
 				$this->version = "";
 				$this->pf = "";
 				$this->pv = "";
 				$this->pvr = "";
 				return false;
 			} else
 				return true;

		}

		function stripCategory() {

			if(strpos($this->atom, '/') > 0) {
				$arr = explode("/", $this->atom);

				if(count($arr) > 1) {
					$str = $arr[1];
				}

				return $str;
			} else {
				return $this->atom;
			}
		}

		function stripPackage($str) {

			$str = $this->stripCategory();
			$str = $this->stripSlot($str);
			$str = str_replace($this->pn."-", "", $str);
			return $str;

		}

		function stripSlot($str) {

			$str = preg_replace('/\:.*$/', '', $str);

			return $str;

 		}

 		public function getMtime() {

 			if(file_exists($this->filename))
 				return filemtime($this->filename);
 			else
 				return null;

 		}

	}

?>
