<?php

require_once("fpdf/fpdf.php");
require_once("lib/libPDFInterface.php");

class libPDF extends FPDF implements libPDFInterface {
	private $default_font;
	private $excess_text;
	private $defered_borders;
	private $cur_line_h;
	private $angle;
	private $defaultOrientation;

	public function __construct($orientation = "P", $unit = "mm", $format = "A4") {
		parent::__construct($orientation, $unit, $format);

		$this->default_font = array(
			"name" => "Arial",
			"style" => "",
			"size" => "10",
			"background" => "#FFFFFF", /* FIXME: Changing this
						    * doesn't change the page's
						    * background colour */
			"color" => "#000000"
		);

		$this->cur_line_h = null;
		$this->excess_text = array();
		$this->defered_borders = array();
		$this->curFlowLine = array();
		$this->angle = 0;

		$this->SetDefaultFont();
	}

	public function getMimeType() {
		return "application/pdf";
	}

	public function getExtension() {
		return "pdf";
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

			if( !isset($fontstyle["background"]) )
				$fontstyle["background"] = $curfont["background"];

			if( !isset($fontstyle["color"]) )
				$fontstyle["color"] = $curfont["color"];

			$this->SetDefaultFont($fontstyle);
		}

		$c = $this->GetFillColor();

		$bg = $c["red"] != 255 || $c["green"] != 255 || $c["blue"] != 255;

		if( $width == null )
			$width = $this->w - $this->rMargin - $this->GetX();

		$lines = $this->SplitIntoLines($text, $width);

		$this->OutputText($lines, $this->GetX(), $width, $this->GetCurrentFont(), $border, $align, $link, $bg);

		if( $fontstyle != null )
			$this->SetDefaultFont($curfont);
	}

	public function FlowText($text, $style = null, $align = "L") {
		if( $style != null ) {
			$curfont = $this->GetCurrentFont();

			if( is_string($style) )
				$style = array(
					"style" => $style
				);

			$this->SetDefaultFont($style);
		}

		$realstyle = $this->GetCurrentFont();

		$h = $this->FontSizePt / 2;

		if( $this->cur_line_h < $h )
			$this->cur_line_h = $h;

		while( $text != "" ) {
			$x = $this->GetX();
			$w = $this->w - $this->rMargin - $x;

			$set = $this->SplitTextAt($text, $w);

			if( $set == null )
				break;

			$chunk = $set[0];

			if( isset($set[1]) ) {
				$text = $set[1];

				if( $chunk != "" )
					$chunk .= " ";
			} else
				$text = "";

			if( $chunk != "" ) {
				$cw = $this->GetStringWidth($chunk);

				if( $align == "L" || $this->curFlowLineAlign == "L" ) {
					$this->curFlowLineAlign = "L";

					$this->emitCurFlowLine();

					// Make left alignment infectious
					$this->curFlowLineAlign = "L";

					$this->Cell($cw, $h, $chunk);
				} else {
					$this->curFlowLineAlign = $align;
					$this->curFlowLine[] = array(
						"x" => $x,
						"w" => $cw,
						"style" => $realstyle,
						"text" => $chunk
					);

					$this->SetX($x + $cw);
				}
			}

			if( $this->GetY() + ($h * 2) > $this->PageBreakTrigger )
				$this->AddPage();

			if( $text != "" )
				$this->Ln();
		}

		if( $style != null )
			$this->SetDefaultFont($curfont);
	}

	private function emitCurFlowLine() {
		if( count($this->curFlowLine) == 0 ) {
			$this->curFlowLineAlign = null;

			return;
		}

		$firstset = current($this->curFlowLine);

		$x = $firstset["x"];

		$curfont = $this->GetCurrentFont();

		$offset = 0;

		switch($this->curFlowLineAlign) {
		case "C":
		case "R":
			$tw = 0;

			foreach($this->curFlowLine as $set)
				$tw += $set["w"];

			$mw = $this->w - $this->rMargin - $x;

			$offset = $mw - $tw;

			if( $this->curFlowLineAlign == "C" )
				$offset /= 2;

			// Fall through
		default:
			$this->SetX($x + $offset);

			foreach($this->curFlowLine as $set) {
				if( $set["style"] != null )
					$this->SetDefaultFont($set["style"]);

				$this->Cell($set["w"], $this->FontSizePt / 2, $set["text"]);

				if( $set["style"] != null )
					$this->SetDefaultFont($curfont);
			}
		}

		$this->curFlowLine = array();
		$this->curFlowLineAlign = null;
	}

	public function HTMLText($html, $bstyle = array()) {
		$doc = new DOMDocument();

		$doc->loadXML("<root/>");

		$f = $doc->createDocumentFragment();
		if( !$f->appendXML($html) )
			return;

		$doc->documentElement->appendChild($f);

		$cur = $doc->documentElement;

		$hs = array();

		$inpara = null;

		while( $cur != null ) {
			if( $cur->nodeType == XML_TEXT_NODE ) {
				if( $inpara === 0 ) {
					$this->Ln();
					$this->Ln();
				}

				$inpara = 1;

				if( count($hs) > 0 )
					$style = array_merge($bstyle, array("style" => implode("", $hs)));
				else
					$style = $bstyle;

				$this->FlowText($cur->nodeValue, $style);
			} else if( $cur->nodeType == XML_ELEMENT_NODE )
				switch(strtolower($cur->nodeName)) {
					case "b":
						array_push($hs, "B");
						break;
					case "i":
						array_push($hs, "I");
						break;
					case "u":
						array_push($hs, "U");
						break;
					case "br":
						$this->Ln();
						break;
					case "p":
						if( $inpara !== null && $inpara < 2 ) {
							$this->Ln();
							$this->Ln();
						}

						$inpara = 2;
						break;
				}

			if( $cur->firstChild )
				$cur = $cur->firstChild;
			else if( $cur->nextSibling )
				$cur = $cur->nextSibling;
			else {
				while( $cur != null && $cur->nextSibling == null) {
					$cur = $cur->parentNode;

					if( $cur != null )
						switch(strtolower($cur->nodeName)) {
							case "b":
							case "i":
							case "u":
								array_pop($hs);
								break;
							case "p":
								$inpara = 0;
						}
				}

				if( $cur != null )
					$cur = $cur->nextSibling;
			}
		}
	}

	public function SetDefaultFont($name = null, $style = null, $size = null) {
		$bgcolor = $this->default_font["background"];
		$color = $this->default_font["color"];

		if( is_array($name) ) {
			if( isset($name["size"]) )
				$size = $name["size"];
			else
				$size = $this->default_font["size"];

			if( isset($name["style"]) )
				$style = $name["style"];
			else
				$style = $this->default_font["style"];

			if( isset($name["background"]) )
				$bgcolor = $name["background"];

			if( isset($name["color"]) )
				$color = $name["color"];

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
		$this->SetFillColor($bgcolor);
		$this->SetTextColor($color);
	}

	public function GetCurrentFont() {
		$style = $this->FontStyle;

		if($this->underline)
			$style .= "U";

		return array(
			"name" => $this->FontFamily,
			"style" => $style,
			"size" => $this->FontSizePt,
			"background" => $this->GetFillColor(),
			"color" => $this->GetTextColor()
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

	public function setDefaultOrientation($o) {
		if( is_string($o) && $o != "" && (($o = strtoupper($o[0])) == "P" || $o == "L") )
			$this->defaultOrientation = $o;
	}

	// Overidden base class functions

	public function AddPage($o = null, $f = "") {
		if( $o == null )
			$o = $this->defaultOrientation;

		parent::AddPage($o, $f);

		$this->cur_line_h = 0;
	}

	public function Ln($h = null) {
		$this->emitCurFlowLine();

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
						"border" => $item["border"],
						"background" => $item["background"]
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
				$this->OutputText($item["text"], $item["x"], $item["width"], $item["fontstyle"], $item["border"], $item["align"], $item["link"], $item["bg"]);

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

	public function GetDrawColor() {
		return $this->decodePDFColour($this->DrawColor, "G", "RG");
	}

	public function SetFillColor($r, $g = null, $b = null) {
		$c = $this->parseColours($r, $g, $b);

		return parent::SetFillColor($c["red"], $c["green"], $c["blue"]);
	}

	public function GetFillColor() {
		return $this->decodePDFColour($this->FillColor, "g", "rg");
	}

	public function SetTextColor($r, $g = null, $b = null) {
		$c = $this->parseColours($r, $g, $b);

		return parent::SetTextColor($c["red"], $c["green"], $c["blue"]);
	}

	public function GetTextColor() {
		return $this->decodePDFColour($this->TextColor, "g", "rg");
	}

	// Helper functions

	private function OutputText($lines, $x, $width, $fontstyle, $border, $align, $link, $bg) {
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

				$this->Cell($width, $h, $line, $curborder, 0, $align, $bg, $link);
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
				"link" => $link,
				"bg" => $bg
			);

			if( $output && ( ($border !== 0 && $border != "") || $bg) )
				$this->defered_borders[] = array(
					"x" => $x,
					"width" => $width,
					"border" => str_replace("B", "", $border),
					"background" => $bg ? $fontstyle["background"] : null,
					"h" => $cur_line_h
				);
		} else if( !$this->InHeader && !$this->InFooter && $output && ( ($border !== 0 && $border != "") || $bg ) )
			$this->defered_borders[] = array(
				"x" => $x,
				"width" => $width,
				"border" => $border,
				"background" => $bg ? $fontstyle["background"] : null,
				"h" => $cur_line_h
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

			if( isset($item["background"]) ) {
				$sbg = $this->GetFillColor();

				$this->SetFillColor($item["background"]);

				if( isset($item["h"]) ) {
					$y = $this->GetY();

					$this->SetXY($item["x"], $y + $item["h"]);

					$sh = $h;
					$h -= $item["h"];

					$b = $item["border"];
					$item["border"] = 0;
				}
			}

			if( $h > 0 )
				$this->Cell($item["width"], $h, "", $item["border"], null, null, isset($item["background"]));

			if( isset($item["background"]) ) {
				$this->SetFillColor($sbg);

				if( isset($item["h"]) ) {
					$this->SetXY($item["x"], $y);

					$h = $sh;

					$this->Cell($item["width"], $h, "", $b);
				}
			}
		}

		$this->defered_borders = array();
	}

	private function parseColours($r, $g, $b) {
		if( $r == null )
			$r = 0;

		if( is_array($r) )
			return $r;

		if( is_string($r) && $g == null && $b == null )
			return parseHTMLColour($r);

		if( $g == null || $b == null || ($r == $g && $g == $b) )
			return array("red" => $r, "green" => null, "blue" => null);

		return array("red" => $r, "green" => $g, "blue" => $b);
	}

	private function decodePDFColour($string, $g, $c) {
		if( preg_match("/^([[:digit:].]*)\s+".$g."$/", $string, $regs) )
			return $regs[1] * 255;

		if( preg_match("/^([[:digit:].]*)\s+([[:digit:].]*)\s+([[:digit:].]*)\s+".$c."$/", $string, $regs) )
			return array("red" => $regs[1] * 255, "green" => $regs[2] * 255, "blue" => $regs[3] * 255);

		return null;
	}

	// Utility functions

	public function SplitTextAt($string, $width) {
		$strings = array();

		if( $string == "" )
			return null;
		else if( $this->GetStringWidth($string) < $width )
			return array($string);

		$str = "";
		$strlen = 0;

		while(preg_match("/^(\s*\S*\s*)(.*)$/s", $string, $regs) && ($strlen + ($len = $this->GetStringWidth($regs[1]))) < $width - 2) {
			$str .= $regs[1];
			$strlen += $len;

			if( !isset($regs[2]) ) {
				$string = "";
				$regs = null;

				break;
			}

			$string = $regs[2];

			$regs = null;
		}

		return array($str, $string);
	}

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

	public function getContent() {
		return $this->Output(null, "S");
	}
}
