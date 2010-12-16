<?php

require_once("fpdf/fpdf.php");

class libPDF extends FPDF {
	private $default_font;
	private $excess_text;
	private $defered_borders;
	private $cur_line_h;
	private $angle;

	public function __construct($orientation = "P", $unit = "mm", $format = "A4") {
		parent::__construct($orientation, $unit, $format);

		$this->default_font = array(
			"name" => "Arial",
			"style" => "",
			"size" => "10"
		);

		$this->cur_line_h = null;
		$this->excess_text = array();
		$this->defered_borders = array();
		$this->angle = 0;

		$this->SetDefaultFont();
	}

	public function TableCell($text, $width = null, $fontstyle = null, $align = "L", $border = 0, $link = null) {
		if( $fontstyle != null ) {
			if( is_string($fontstyle) )
				$fontstyle = array(
					"style" => $fontstyle
				);

			$curfont = $this->GetCurrentFont();

			if( !isset($fontstyle["size"]) )
				$fontstyle["size"] = $curfont["size"];

			if( !isset($fontstyle["style"]) )
				$fontstyle["style"] = $curfont["style"];

			if( !isset($fontstyle["name"]) )
				$fontstyle["name"] = $curfont["name"];

			$this->SetDefaultFont($fontstyle);
		}

		if( $width == null )
			$width = $this->w - $this->rMargin - $this->GetX();

		$lines = $this->SplitIntoLines($text, $width);

		$this->OutputText($lines, $this->GetX(), $width, $this->GetCurrentFont(), $border, $align, $link);

		if( $fontstyle != null )
			$this->SetDefaultFont($curfont);
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

	// Random stuff to do fancy shit

	// This was stolen from a random web forum and modified until it made
	// sense.
	public function Rotate($angle, $x = null, $y = null) {
		if($x == null)
			$x = $this->x;
	       
		if($y == null)
			$y = $this->y;

		if($this->angle != 0)
			$this->_out('Q');
	       
		$this->angle = $angle;
	       
		if($angle != 0) {
			$angle *= M_PI / 180;
			$c = cos($angle);
			$s = sin($angle);
			$cx = $x * $this->k;
			$cy = ($this->h - $y) * $this->k;
			
			$this->_out(sprintf('q %.5f %.5f %.5f %.5f %.2f %.2f cm 1 0 0 1 %.2f %.2f cm', $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy));
		}
       	}

	// Overidden base class functions

	public function AddPage($o = "", $f = "") {
		parent::AddPage($o, $f);

		$this->cur_line_h = 0;
	}

	public function Ln($h = null) {
		if( $this->InHeader || $this->InFooter ) {
			if( $h === null )
				$h = $this->cur_line_h;

			if( $h === null )
				$h = $this->FontSizePt / 2;

			$this->cur_line_h = null;

			return parent::Ln($h);
		}

		$curfont = $this->GetCurrentFont();

		while( count($this->excess_text) > 0 ) {
			$saved_defered = array();

			if( count($this->defered_borders) != 0 ) {
				foreach($this->defered_borders as &$item) {
					$saved_defered[$item["x"]] = array(
						"x" => $item["x"],
						"width" => $item["width"],
						"border" => $item["border"]
					);

					if( strpos($item["border"], "B") !== false )
						$item["border"] = str_replace("B", "", $item["border"]);
				}

				$this->handleDeferedBorders();
			}

			$this->AddPage();

			$set = $this->excess_text;
			$this->excess_text = array();

			foreach($set as $item)
				$this->OutputText($item["text"], $item["x"], $item["width"], $item["fontstyle"], $item["border"], $item["align"], $item["link"]);

			if( count($this->defered_borders) > 0 )
				foreach($this->defered_borders as $item)
					unset($saved_defered[$item["x"]]);

			if( count($saved_defered) > 0 )
				foreach($saved_defered as $item)
					$this->defered_borders[] = $item;
		}

		$this->handleDeferedBorders();

		$this->SetDefaultFont($curfont);

		if( $h === null )
			$h = $this->cur_line_h;

		if( $h === null )
			$h = $this->FontSizePt / 2;

		$this->cur_line_h = null;

		parent::Ln($h);
	}

	public function SetDrawColor($r, $g = null, $b = null) {
		$c = $this->parseColours($r, $g, $b);

		return parent::SetDrawColor($c["red"], $c["green"], $c["blue"]);
	}

	public function SetFillColor($r, $g = null, $b = null) {
		$c = $this->parseColours($r, $g, $b);

		return parent::SetFillColor($c["red"], $c["green"], $c["blue"]);
	}

	public function SetTextColor($r, $g = null, $b = null) {
		$c = $this->parseColours($r, $g, $b);

		return parent::SetTextColor($c["red"], $c["green"], $c["blue"]);
	}

	// Helper functions

	private function OutputText($lines, $x, $width, $fontstyle, $border, $align, $link) {
		$this->SetDefaultFont($fontstyle);

		$cur_line_h = 0;
		$next_page = array();
		$curborder = "";

		$h = $this->FontSizePt / 2;

		if( $this->InHeader || $this->InFooter )
			$curborder = $border;
		else {
			if( $border == 1 )
				$border = "TRBL";
			else if( $border === 0 )
				$border = "";

			if( strpos($border, "T") !== false ) {
				$curborder = "T";
				$border = str_replace("T", "", $border);
			}
		}

		$output = false;

		foreach($lines as $i => $line)
			if( !$this->InHeader && !$this->InFooter && $this->GetY() + ($h * 2) > $this->PageBreakTrigger )
				$next_page[] = $line;
			else {
				$output = true;

				if( $i != 0 ) {
					parent::Ln($h);

					if( !$this->InHeader && !$this->InFooter )
						$curborder = "";
				}

				$cur_line_h += $h;

				$this->SetX($x);

				$this->Cell($width, $h, $line, $curborder, 0, $align, 0, $link);
			}

		if( $this->cur_line_h < $cur_line_h )
			$this->cur_line_h = $cur_line_h;

		$this->SetXY($x + $width, $this->GetY() - $cur_line_h + $h);

		if( !$output )
			$border .= $curborder;

		if( $next_page != array() ) {
			$this->excess_text[] = array(
				"text" => $next_page,
				"x" => $x,
				"width" => $width,
				"fontstyle" => $fontstyle,
				"border" => $border,
				"align" => $align,
				"link" => $link
			);

			if( $output && $border !== 0 && $border != "" )
				$this->defered_borders[] = array(
					"x" => $x,
					"width" => $width,
					"border" => str_replace("B", "", $border)
				);
		} else if( !$this->InHeader && !$this->InFooter && $output && $border !== 0 && $border != "" )
			$this->defered_borders[] = array(
				"x" => $x,
				"width" => $width,
				"border" => $border
			);
	}

	private function handleDeferedBorders() {
		if( count($this->defered_borders) == 0 )
			return;

		$h = $this->cur_line_h;
		if($this->GetY() + $h > $this->PageBreakTrigger)
			$h = $this->PageBreakTrigger - $this->GetY();

		foreach($this->defered_borders as $item) {
			$this->SetX($item["x"]);

			$this->Cell($item["width"], $h, "", $item["border"]);
		}

		$this->defered_borders = array();
	}

	private function parseColours($r, $g, $b) {
		if( $r == null )
			$r = 0;

		if( is_array($r) )
			return $r;

		if( $g == null || $b == null || ($r == $g && $g == $b) )
			return array("red" => $r, "green" => null, "blue" => null);

		return array("red" => $r, "green" => $g, "blue" => $b);
	}

	// Utility functions

	public function SplitIntoLines($input, $width) {
		$inlines = explode("\n", $input);

		$lines = array();
		foreach($inlines as $line)
			if( $line == "" )
				$lines[] = "";
			else {
				while($line != "" && $this->GetStringWidth($line) > $width) {
					$str = "";
					$strlen = 0;

					while(preg_match("/^(\s*\S*\s*)(.*)$/s", $line, $regs) && ($strlen + ($len = $this->GetStringWidth($regs[1]))) < $width - 2) {
						$str .= $regs[1];
						$strlen += $len;

						if( !isset($regs[2]) ) {
							$line = "";
							$regs = null;

							break;
						}

						$line = $regs[2];

						$regs = null;
					}

					if( $str == "" ) {
						if( $regs == null ) {
							$str = $line;
							$line = "";
						} else {
							$str = $regs[1];

							if( !isset($regs[2]) )
								$line = "";
							else
								$line = $regs[2];
						}
					}

					$lines[] = $str;
				}

				if( $line != "" )
					$lines[] = $line;
			}

		return $lines;
	}
}
