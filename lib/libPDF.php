<?php

require_once("fpdf/fpdf.php");

class libPDF extends FPDF {
	private $default_font;

	public function __construct($orientation = "P", $unit = "mm", $format = "A4") {
		parent::__construct($orientation, $unit, $format);

		$this->default_font = array(
			"name" => "Arial",
			"style" => "",
			"size" => "10"
		);

		$this->SetDefaultFont();
	}

	public function SetDefaultFont($name = null, $style = null, $size = null) {
		if( is_array($name) ) {
			if( isset($name["size"]) )
				$size = $name["size"];
			else
				$size = $this->default_font["size"];

			if( isset($name["style"]) )
				$style = $name["style"];
			else
				$style = $this->default_font["style"];

			if( isset($name["name"]) )
				$name = $name["name"];
			else
				$name = $this->default_font["name"];
		} else {
			if( $name === null )
				$name = $this->default_font["name"];

			if( $style === null )
				$style = $this->default_font["style"];

			if( $size === null )
				$size = $this->default_font["size"];
		}

		$this->SetFont($name, $style, $size);
	}

	public function GetCurrentFont() {
		$style = $this->FontStyle;

		if($this->underline)
			$style .= "U";

		return array(
			"name" => $this->FontFamily,
			"style" => $style,
			"size" => $this->FontSizePt
		);
	}
}

?>
