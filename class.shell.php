<?

	class shell {
	
		function __construct() {
		}
		
		/**
		* Execute shell scripts
		*
		* @param string execution string
		* @param boolean drop stderr to /dev/null
		* @param boolean ignore exit codes
		* @param array exit codes that indicate success
		* @return output array
		*/
		function cmd($str, $stderr_to_null = true, $ignore_exit_code = false, $passthru = false, $arr_successful_exit_codes = array(0)) {
			
			$arr = array();
			
			if($stderr_to_null)
				$exec = "$str 2> /dev/null";
			else
				$exec =& $str;
			
			if($passthru)
				passthru($exec, $return);
			else
				exec($exec, $arr, $return);
			
			if(!in_array($return, $arr_successful_exit_codes) && !$ignore_exit_code) {
				shell::msg("execution died: $str");
				die($return);
			} else
				return $arr;
			
		}
		
		/**
		 * Output text to stdout or stderr)
		 *
		 * @param string output string
		 * @param boolean outout to stderr
		 * @param boolean debugging
		 */
		function msg($str = '', $stderr = false, $debug = false) {
		
			if($debug === true) {
				if($this->debug == true)
					$str = "[Debug] $str";
				else
					$str = '';
			}
		
			if(!empty($str)) {
				if($stderr === true) {
					fwrite(STDERR, "$str\n");
				} else {
					fwrite(STDOUT, "$str\n");
				}
			}
		}
		
		/**
		 * Ask a question
		 *
		 */
		function ask($str, $default = false) {
			if(is_string($str)) {
				fwrite(STDOUT, "$str ");
				$input = fread(STDIN, 255);
				
				if($input == "\n") {
					return $default;
				} else {
					$input = trim($input);
					return $input;
				}
			}
		}
		
		/**
		* Parse CLI arguments
		*
		* If a value is unset, it will be set to 1
		*
		* @param $argc argument count (system variable)
		* @param $argv argument array (system variable)
		* @return array
		*/
		function parseArguments() {
		
			global $argc;
			global $argv;
		
			$args = array();
			
			if($argc > 1) {
				array_shift($argv);
	
				for($x = 0; $x < count($argv); $x++) {
				
					if(preg_match('/^(-\w$|--\w+)/', $argv[$x]) > 0) {
						$argv[$x] = preg_replace('/^-{1,2}/', '', $argv[$x]);
						$args[$argv[$x]] = 1;
					}
					else {
						if(in_array($argv[($x-1)], array_keys($args))) {
							$args[$argv[($x-1)]] = $argv[$x];
						}
					}
				}
	
				return $args;
			}
			else
				return array();
		}
		
		/**
		 * Check for a file in a directory
		 *
		 * @param string filename
		 * @param string directory
		 * @return boolean
		 */
		function in_dir($file, $dir) {
		
			if(!is_dir($dir))
				return false;
			
			$arr = scandir($dir);
			
			$file = basename($file);
			
			if(in_array($file, $arr))
				return true;
			else
				return false;
		}
		
		/**
		 * Get the contents of a filename, optionally stripping out non-comments
		 *
		 * @param string filename
		 * @param bool strip comments
		 * @return array
		 */
		function arrFilename($filename, $confcat = false) {
			$arr = array();

			if(!is_string($filename) || empty($filename) || !file_exists($filename))
				trigger_error("Cannot read filename $filename", E_USER_ERROR);
				#return $arr;

			$arr = file($filename);
			if($confcat)
				$arr = preg_grep('/(^#|^$)/', $arr, PREG_GREP_INVERT);
			foreach($arr as $key => $value) {
				$arr[$key] = trim($value);
			}
			
			return $arr;
		}
		
		// Calculate execution time, based on
		// two integer timestamps
		function executionTime($start, $finish) {
		
			$arr = array();
		
			$start = abs(intval($start));
			$finish = abs(intval($finish));
			
			if($finish > $start) {
				$arr = array(
					'minutes' => intval(($finish - $start) / 60),
					'seconds' => intval(($finish - $start) % 60),
				);
			}
			
			return $arr;
		
		}
		
		/**
		 * Format a title for saving to filesystem
		 *
		 * @param string original title
		 * @return new title
		 */
		function formatTitle($str = 'Title', $underlines = true) {
			$str = preg_replace("/[^A-Za-z0-9 \-,.?':!]/", '', $str);
			$underlines && $str = str_replace(' ', '_', $str);
			return $str;
		}
		
	}

?>