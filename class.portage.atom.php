<?php

	class PortageAtom extends PortageEbuild {
	
		// Ranges
		private $gt; // greater than >
		private $lt; // lesser than <
		private $eq; // equals =
		private $ar; // any revision ~
		private $av; // any version *
		
		private $arr_chars;
		private $arr_ranges;
		
		public $ebuild_atom;
		
		function __construct($str) {
		
			$this->gt = false;
			$this->lt = false;
			$this->eq = false;
			$this->ar = false;
			$this->av = false;
		
			// Find the ranges
			if($str[0] == '>')
				$this->gt = true;
			
			if($str[0] == '<')
				$this->lt = true;
			
			if($str[0] == '=' || $str[1] == '=')
				$this->eq = true;
				
			if($str[0] == '~')
				$this->ar = true;
			
			$end = $str[strlen($str) - 1];
			
			if($end == '*')
				$this->av = true;
				
			$this->arr_chars = array('>', '<', '=', '~', '*');
			
			foreach($this->arr_chars as $char)
				$str = str_replace($char, '', $str);
			
			$this->arr_ranges = array('gt', 'lt', 'eq', 'ar', 'av');
			
			$this->ebuild_atom = $str;
			
			
			parent::__construct($this->ebuild_atom);
		
		}
		
		function __get($str) {
		
			if(in_array($str, $this->arr_ranges))
				return $this->$str;
			else
				return parent::__get($str);
		}
	
	}

?>
