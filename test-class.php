<?php

class TESTClass
{

public $variable1 = 1;

public $variable2 = 2;

public $variable3 = 3;

public $variable4 = 4;

public $displayer = "";

	public function __construct(){
		$this->variable1 = $this->variable1 *10;
		$this->variable2 = $this->variable2 *10;
		$this->variable3 = $this->variable3 *10;
		$this->variable4 = $this->variable4 *10;
		return;
	}
	
	public function display_variables(){
		$this->displayer .= "Variable 1: $this->variable1<br />\n";
		$this->displayer .= "Variable 2: $this->variable2<br />\n";
		$this->displayer .= "Variable 3: $this->variable3<br />\n";
		$this->displayer .= "Variable 4: $this->variable4<br />\n";
		return $this->displayer;
	}

}

?>