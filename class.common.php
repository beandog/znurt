<?
	/**
	 * A set of common functions
	 *
	 * @access public
	 * @author Shane Gebs
	 * @author Steve Dibb
	 * @category Admin
	 * @package Admin
	 */
	class Common {
	
		var $html;
		var $id;
		
		function Common() {
			$this->state_name = array("Alabama","Alaska","Arizona","Arkansas","California","Colorado","Connecticut","Delaware","District Of Columbia","Florida","Georgia","Hawaii","Idaho","Illinois","Indiana","Iowa","Kansas","Kentucky","Louisiana","Maine","Maryland","Massachusetts","Michigan","Minnesota","Mississippi","Missouri","Montana","Nebraska","Nevada","New Hampshire","New Jersey","New Mexico","New York","North Carolina","North Dakota","Ohio","Oklahoma","Oregon","Pennsylvania","Rhode Island","South Carolina","South Dakota","Tennessee","Texas","Utah","Vermont","Virgin Islands","Virginia","Washington","West Virginia","Wisconsin","Wyoming");
			$this->state_code = array('AL','AK','AZ','AR','CA','CO','CT','DE','DC','FL','GA','HI','ID','IL','IN','IA','KS','KY','LA','ME','MD','MA','MI','MN','MS','MO','MT','ND','NE','NV','NH','NJ','NM','NY','NC','ND','OH','OK','OR','PA','RI','SC','SD','TN','TX','UT','VT','VI','VA','WA','WV','WI','WY');
		}

		/**
		 * Formats a currency value to $dollars.cents
		 *
		 * @param mixed $amount
		 * @return float $amount
		 *
		 */
		function displayCurrency($amount) {
			$amount = ereg_replace("[^0-9,.,\-]","",$amount);
			if(intval($amount) == 0)
				$amount = "0.00";
			if(!strpos($amount,"."))
				$amount .= ".00";
			return($amount);
		}
		
		function selectForm($array = false, $field_name = 'select', $default = false, $class = '', $blank_header = false) {
			$this->html = "<select name='$field_name' class='$class'>\n";
			if($blank_header) {
				$this->html .= "<option value=''></option>\n";
			}
			foreach($array as $key => $value) {
				$this->html .= "<option value='$key'";
				if($default == $key)
					$this->html .= " selected";
				$this->html .= ">$value</option>\n";
			}
			$this->html .= "</select>\n";
			#print_r($array);
			return($this->html);
		}
		
		
		/**
		 * print_r() formatted nicely
		 *
		 * Intended for HTML output.  Sandwiches the output between <pre> tags.
		 *
		 * @param mixed $var
		 */
		function pre($var, $header = false, $sort = true) {
			if($header)
				echo "<p><b>$header</b></p>\n";
			echo "<pre>\n";
			if($sort == true && is_array($var))
				ksort($var);
			if(is_array($var) || is_object($var))
			{
				print_r($var);
			}
			else {
				$var = htmlentities($var);
				print_r($var);
			}	
			echo "</pre>\n";
		}

		/**
		 * Converts phone number to display of (123) 456-7890
		 *
		 * @param mixed $phone
		 * @return string $phone
		 */
		function displayPhone($phone) {
			$phone=ereg_replace("[^0-9,.]","",$phone);
			if(strlen($phone) == 10)
				$phone = "(".substr($phone,0,3).") ".substr($phone,3,3)."-".substr($phone,6,4);
			elseif(strlen($phone)==7)
				$phone = substr($phone,0,3)."-".substr($phone,3,4);
			return $phone;
		}
		
		/**
		 * insertNull()
		 *
		 * To help with debugging, this is used when actually
		 * inserting the data, basically putting quotes around
		 * its value
		 *
		 * Its worth noting that only a few dates should be able to go in as NULL,
		 * and those are the ones that can be inserted if they are blank or not
		 * Birthdays, lease end dates and vehicle delivery dates can be NULL.
		 * Order dates shouldn't be.
		 *
		 * @param string $str string to be inserted
		 * @return string formatted string
		 *
		 */
		function insertNull($str) {
			if(is_null($str) || empty($str))
				return 'NULL';
			else
				return "'".pg_escape_string($str)."'";
		}
  
  		/**
		 * Creates a mailto: href
		 *
		 * @param string $email Email address
		 * @param string $class CSS class
		 * @param mixed $name Addressee's name
		 * @return string $email
		 *
		 */
		function mailto($email, $class='', $name=false) {
			$email=strtolower($email);
			if($name)
				$email="<a href='mailto:$name <$email>' class='$class'>$email</a>";
			else
				$email="<a href='mailto:$email' class='$class'>$email</a>";
			return $email;
		}
		
		/**
		 * Standardizes display format display for clients
		 *
		 * @param mixed $date
		 * @return string
		 */
		function clientDate($date) {
			if($date == "")
				return($date);
			else
			{
				$date = strtotime($date);
				$date = date("M. j, Y", $date);
				return($date);
			}
		}
	
		/**
		 * Creates a select menu from a recordset
		 *
		 * @param object $rs recordset object
		 * @param string $select_name value for select name
		 * @param int $option_value_id the index key from the array of the recordset for the values (option value)
		 * @param int $option_string_id the index key from the array of the recordset for the strings (option string)
		 * @param string $default string of the default option
		 * @param bool $display_blank_option add an empty option to the top
		 * @param string $first_option_value new value at the top
		 * @param string $first_option_text new option at the top
		 */
		function selectRecordset($rs, $select_name = 'select', $option_value_id = 0, $option_string_id = 1, $default = null, $display_blank_option = false,  $first_option_value = 0, $first_option_text = '') {
		
			if(!is_numeric($option_value_id) || !is_numeric($option_string_id))
				return 0;
		
			$html = "<select name='$select_name'>\n";
			
			// If they want a blank starter, display it
			if($display_blank_option === true)
				$html .= "<option value=''></option>\n";
			
			// See if they need a first row
			if(!empty($first_option_text))
				$html .= "<option value='$first_option_value'>$first_option_text</option>\n";
	
			while($arr = mssql_fetch_row($rs)) {
				if(!is_null($default) && $arr[$option_value_id] == $default)
					$selected = ' selected';
				else
					$selected = '';
				$html .= "<option value='{$arr[$option_value_id]}'$selected>{$arr[$option_string_id]}</option>\n";
			}
			
			$html .= "</select>\n";
			
			return $html;
		}
		
		/**
		 * Creates a <select> menu of states
		 *
		 * @param string $state default state
		 * @param string $name select value
		 * @param string $class css class
		 */
		function selectState($state = 'state', $name = 'state',  $class = '') {
			$select = "<select name='$name' class='$class'>\n";
			if($state == '')
				$select .= "<option value='' selected>--</option>\n";
			foreach($this->state_code as $key => $value) {
				$select .= "<option value='$value'";
				if($value == $state)
					$select .= " selected";
				$select .= ">$value</option>\n";
			}
			$select .= "</select>\n";
			return($select);
		}


		/**
		 * Add months - easy alternative to PEAR's annoying Date class
		 *
		 * @param string $date Original date
		 * @param int $months Number of months to add
		 * @return string $date Y-m-d value of new date
		 */
		function addMonths($date, $months) {
			return strtotime("$months month $date");
		}
		
		/**
		 * Add a few days using PEAR's Date class
		 * @param string $date
		 * @param int $days
		 * @return string $date Y-m-d value of new date
		 * @access public
		 */
		function addDays($date, $days, $bump = true) {
			require_once("Date.php");
			$date = strtotime($date);
			$old = new Date($date);
			# Add number of days
			$span = new Date_Span("$days, 0, 0, 0"); # fmt days, hours, mins, secs
			$old->addSpan($span);
			if($day = $old->getDay() > 28) {
				$add_days = (32 - $day);
				$span->setFromDays($add_days);
				$old->addSpan($span);
			}
			return date("Y-m-d",strtotime($old->getDate()));
		}
		
		/**
		 * Adds hours
		 *
		 * @param mixed $date start date
		 * @param int $hours hours to add
		 * @param bool $bump
		 * @return string
		 */
		function addHours($date, $hours, $bump = true) {
			require_once("Date.php");
			$date = strtotime($date);
			$old = new Date($date);
			# Add number of hours
			$span = new Date_Span("0, $hours, 0, 0"); # fmt days, hours, mins, secs
			$old->addSpan($span);
			if($day = $old->getDay() > 28) {
				$add_days = (32 - $day);
				$span->setFromDays($add_days);
				$old->addSpan($span);
			}
			return date("Y-m-d",strtotime($old->getDate()));
		}

		/**
		 * A fix for PHP's native num_array math function, which is buggy when calculating floats
		 *
		 * @param array $array
		 * @return mixed $sum
		 * @access public
		 */
		function array_sum($array) {
			$sum = 0;
			if(!is_array($array)) {
				trigger_error("array_sum() Argument must be an array.", E_USER_ERROR);
				return false;
				break;
			}
			if(extension_loaded('bcmath') || dl('bcmath')) {
				foreach($array as $key => $value) {
					$sum = bcadd($sum, $value);
				}
				return $sum;
			}
			elseif(extension_loaded('gmp') || dl('gmp')) {
				foreach($array as $key => $value) {
					$sum = gmp_add($sum, $value);
				}
				return $sum;
			}
			else {
				trigger_error("array_sum() The bcmath or gmp extensions are required.", E_USER_ERROR);
				return false;
			}
		}

		/**
		 * Displays newlines, carriage returns and tabs
		 *
		 * @param string $str
		 * @return string $str
		 *
		 */
		function displayBreaks($str) {
			$str = str_replace("\t","\\t\t",$str);
			$str = str_replace("\n","\\n\n",$str);
			$str = str_replace("\r","\\r\r",$str);
			return($str);
		}


		/**
		 * Format an address from user input
		 *
		 * Removes anything not A to z, 0 to 9, ",", ".", "'", space, or "-"
		 *
		 * Wrapper for formatInput()
		 *
		 * @param string $str
		 * @return string
		 */
		function formatAddress($str) {
			$str = $this->formatInput($str, 'address');
		}

		/**
		 * Formats user input data
		 *
		 * Based on the input type, will format just about any data
		 * from user input to make it clean and usable for database
		 * insertion.
		 *
		 * Uses a lot of regular expressions to strip non-relevant
		 * characters.
		 *
		 * Types: address, business, city, email, int, name, phone, string, zip
		 *
		 * @param mixed $fmt string to format
		 * @param string $type input type
		 * @return mixed
		 *
		 */
		function formatInput($fmt, $type = 'string') {

			// strip html and trim whitespaces
			$fmt = strip_tags($fmt);
			$fmt = trim($fmt);
			
			// Correct case on address, name, city and business
			if($type == 'address' || $type == 'name' || $type == 'city' || $type == 'business')
				$fmt = ucwords(strtolower($fmt));

			switch($type) {
		 		// not A to z, 0 to 9, ",", ".", "'", space, # or "-"
				case 'address':
					$patterns = array(
						'/[^(A-z|0-9|,|.|\-|\'| |,|#)]/',
						/*
						'/\bN(o|orth)?\b\.?/i',
						'/\bE(ast)?\b\.?/i',
						'/\bS(o|outh)?\b\.?/i',
						'/\bW(est)?\b\.?/i',
						'/\bSt(reet)?\b\.?/i',
						'/\b(Lane|Ln?)\b\.?/i',
						'/\bDr(ive)?\b\.?/i',
						'/\b(Blvd|Boulevard)\b\.?/i',
						'/\b(Ci(r)?(cle)?|Cr)\b\.?/i',
						'/\b(Hwy|Highway)\b\.?/i',
						'/\bp\.?o\b\.?/i',
						'/\bAv(e|enue)?\b\.?/i',
						'/\b(Court|Ct)\b\.?/i',
						*/
					);
					$rep = array(
						'',
						/*
						'N.',
						'E.',
						'S.',
						'W.',
						'St.',
						'Ln.',
						'Dr.',
						'Blvd.',
						'Cir.',
						'Hwy.',
						'P.O.',
						'Ave.',
						'Ct.'
						*/
					);
					$fmt = preg_replace($patterns, $rep, $fmt);
					break;
				// not A to z, 0 to 9
				case 'alphanumeric':
					$fmt = preg_replace('/[^(A-z|0-9)]/', '', $fmt);
					break;
		 		// not A to z, ",", ".", "'", space, or "-"
				case "name":
					$fmt = preg_replace("/[^(A-z|,|.|\'| |\-)]/", "", $fmt);
					break;
		 		// not A to z, ",", ".", "'", space, or "-"
				case "city":
					$fmt = preg_replace("/[^(A-z|,|.|\'| |\-)]/", "", $fmt);
					break;
		 		// not 0 to 9
				case "zip":
					$fmt = preg_replace("/[^(0-9)]/", "", $fmt);
					$strlen = strlen($fmt);
					if($strlen < 5)
						$fmt = null;
					elseif($strlen > 5)
						$fmt = substr($fmt, 0, 5);
					break;
		 		// not A to z, 0 to 9, ",", ".", "'", space, "-", "&", "\", ":"
				case "business":
					$fmt = preg_replace("/[^(A-z|0-9|,|.|\-|\'| |\&|\\|\:)]/", "", $fmt);
					break;
		 		// not 0 to 9
				case "phone":
					#$fmt = preg_replace("/[^0-9]/", "", $fmt);
					$fmt = preg_replace("/\D/", "", $fmt);
					
					if(empty($fmt))
						return null;
					
					$strlen = strlen($fmt);
					if(($strlen > 7 && $strlen < 10) || $strlen < 7 || $strlen > 11)
						$fmt = null;
					elseif($strlen == 11 && (substr($fmt, 0, 1) == 1))
						$fmt = substr($fmt, 1, 10);
					break;
		 		// not A to z, 0 to 9, ".", "-", "@"
				case "email":
					$fmt = strtolower($fmt);
					$fmt = preg_replace("/[^(a-z|0-9|.|\-|\'|\@)]/", "", $fmt);
					break;
		 		// not 0 to 9, "."
				case "money":
					$fmt = preg_replace('/([^\.0-9-])/', '', $fmt);
					if(is_numeric($fmt)) {
						if(!(strpos($fmt, '.') === false)) {
							$tmp = explode('.', $fmt);
							$tmp[1] = substr($tmp[1], 0, 2);
							$fmt = str_pad($tmp[0], 1, 0).'.'.str_pad($tmp[1], 2, 0, STR_PAD_RIGHT);
						}
						else
							$fmt .= '.00';
					}
					else
						$fmt = '0.00';
					break;
				case "date":
					if(!empty($fmt))
						$fmt = strtotime($fmt);
					
					if(empty($fmt) || ($fmt) == -1)
						$fmt = null;
					else
						$fmt = date('Y-m-d', $fmt);
					break;
				case "sex":
					$fmt = ucwords($fmt);
					$fmt = substr($fmt, 0, 1);
					if($fmt != 'M' && $fmt != 'F')
						$fmt = '';
					break;
				case "year":
					if(is_numeric($fmt))
						$fmt = intval($fmt);
					if(strlen($fmt) != 4) {	
						if(strlen($fmt) == 2 && $fmt < 50)
							$fmt = "20$fmt";
						elseif(strlen($fmt) == 1 && $fmt < 50)
							$fmt = "200$fmt";
						elseif(strlen($fmt) == 2 && $fmt >= 50)
							$fmt = "19$fmt";
						else
							$fmt = null;
						}
					break;
				case 'integer':
				case 'int':
					$fmt = preg_replace('/\D/', '', $fmt);
					break;
				default:
					return $fmt;
					break;
			} #end switch

			$fmt = trim($fmt);
			return($fmt);
		}
	
		/**
		 * Cleanly displays a td with border if the value is blank
		 *
		 * @param string $str
		 * @return string
		 *
		 */
		function displayTD($str = '') {
			$str = trim($str);
			if(is_null($str) || empty($str))
				return " &nbsp; ";
			else
				return $str;
		}

		function displayNull($str) {
			if(is_null($str))
				$str = '<i>NULL</i>';
			elseif(empty($str) && $str != 0)
				$str = '<i>EMPTY</i>';
			return $str;
		}

		function displayDate($date) {
			if(!empty($date))
				return date('Y-m-d', strtotime($date));
			else
				return '';
		}
		
		function selectChecked($value = 0, $checked = null) {
			if($value == $checked)
				return ' checked ';
			else
				return '';
		}
		
		function getRowColor($i = 0) {
			$i = intval($i);
			
			if(($i % 2) == 0)
				return '#ffffff';
			else
				return '#f5f4f3';
		}
	};
	
?>
