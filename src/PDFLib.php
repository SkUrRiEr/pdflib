<?php namespace PDFLib;

use FPDF;

class PDFLib extends FPDF
{
    public $default_font;
    private $excess_text;
    private $defered_borders;
    private $cur_line_h;
    private $angle;
    private $curFlowLine;
    private $curFlowLineAlign;
    private $interlacedPNGs;
    private $toUnlink;

    /* Local default orientation - FPDF's can only be set in the constructor.
     */
    private $defaultOrientation;

    /**
     * Over-write the protected properties of the FPDF base class.
     *
     * @var
     */
    public $bMargin;
    public $tMargin;
    public $rMargin;
    public $lMargin;
    public $PageBreakTrigger;
    public $FontSizePt;

    /**
     * PDFLib constructor.
     *
     * @param string $orientation
     * @param string $unit
     * @param string $format
     */
    public function __construct($orientation = "P", $unit = "mm", $format = "A4")
    {
        parent::__construct($orientation, $unit, $format);

        $this->setDefaultOrientation($orientation);

        $this->default_font = array(
            "name"       => "Helvetica",
            "style"      => "",
            "size"       => "10",
            "background" => "#FFFFFF", /* FIXME: Changing this
                                        * doesn't change the page's
                                        * background colour */
            "color"      => "#000000"
        );

        $this->cur_line_h       = null;
        $this->cur_max_h        = 0;
        $this->valign_defered   = array();
        $this->excess_text      = array();
        $this->defered_borders  = array();
        $this->curFlowLine      = array();
        $this->curFlowLineAlign = null;
        $this->angle            = 0;
        $this->interlacedPNGs   = array();
        $this->toUnlink         = array();

        $this->SetDefaultFont();

        $this->PDFVersion = '1.4'; // For artifact tagging in page number
                                   // methods
    }

    /**
     * Clean up temporary deinterlaced PNGs
     */
    public function __destruct()
    {
        foreach ($this->toUnlink as $filename) {
            unlink($filename);
        }
    }

    /**
     * @return array
     */
    public function getAvailableFonts()
    {
        $fonts = array();

        $d = opendir(realpath(__DIR__ . "/../fonts"));

        while ($f = readdir($d)) {
            if (preg_match("/^(.*)\.php$/", $f, $regs)) {
                $fonts[] = $regs[1];
            }
        }

        return $fonts;
    }

    /**
     * @param        $family
     * @param string $style
     * @param int    $size
     */
    public function SetFont($family, $style = '', $size = 0)
    {
        if ($family != '') {
            $f = strtolower($family);
            $s = strtoupper($style);

            if (strpos($s, 'U') !== false) {
                $s = str_replace('U', '', $s);
            }

            if ($s == 'IB') {
                $s = 'BI';
            }

            if ($this->FontFamily == $f && $this->FontStyle == $s && $this->FontSizePt == $s) {
                return;
            }

            $fontkey = $f . $s;

            if ( ! isset($this->fonts[$fontkey]) && ! in_array($f, $this->CoreFonts)) {
                $file = str_replace(' ', '', $f) . strtolower($s) . '.php';

                if (file_exists(__DIR__ . "/../fonts/" . $file)) {
                    $this->AddFont($f, $s);

                }
            }
        }

        parent::SetFont($family, $style, $size);
    }

    /**
     * @param $font
     *
     * @return array
     */
    public function _loadfont($font)
    {

        $defaultFontpath = $this->fontpath;

        if (file_exists(realpath(__DIR__ . "/../fonts") . '/' . $font)) {
            $this->fontpath = realpath(__DIR__ . "/../fonts/") . '/';
        }

        $data = parent::_loadfont($font);

        if ($this->fontpath != $defaultFontpath && isset($data["file"])) {
            $a = $this->pathSplit($defaultFontpath);
            $b = $this->pathSplit($this->fontpath);

            $set = array();

            while (true) {
                $ca = array_pop($a);
                $cb = array_pop($b);

                if ($ca === null && $cb === null) {
                    break;
                }

                if ($ca === $cb) {
                    continue;
                }

                if ($cb !== null) {
                    $set[] = $cb;
                }

                if ($ca !== null) {
                    array_unshift($set, "..");
                }
            }

            $data["file"] = implode($set, "/") . "/" . $data["file"];
        }

        $this->fontpath = $defaultFontpath;

        return $data;
    }

    /**
     * @param $str
     *
     * @return array
     */
    private function pathSplit($str)
    {
        // FIXME: May not work on Windows

        $set = array();

        $str = str_replace("/./", "/", $str);

        if ($str[0] != "/") {
            $str = dirname($_SERVER["SCRIPT_FILENAME"]) . "/" . $str;
        }

        while (strlen($str) > 1 && $str != "/") {
            $set[] = basename($str);
            $str   = dirname($str);
        }

        if ($str != "/") {
            $set[] = $str;
        }

        return $set;
    }

    /**
     * @param        $html
     * @param null   $width
     * @param null   $fontstyle
     * @param string $align
     * @param int    $border
     * @param null   $link
     * @param string $valign
     */
    public function TableCell(
        $html,
        $width = null,
        $fontstyle = null,
        $align = "L",
        $border = 0,
        $link = null,
        $valign = "T"
    ) {
        $html = utf8_decode($html);

        if ($fontstyle != null) {
            if (is_string($fontstyle)) {
                $fontstyle = array(
                    "style" => $fontstyle
                );
            }

            $curfont = $this->GetCurrentFont();

            if ( ! isset($fontstyle["size"])) {
                $fontstyle["size"] = $curfont["size"];
            }

            if ( ! isset($fontstyle["style"])) {
                $fontstyle["style"] = $curfont["style"];
            }

            if ( ! isset($fontstyle["name"])) {
                $fontstyle["name"] = $curfont["name"];
            }

            if ( ! isset($fontstyle["background"])) {
                $fontstyle["background"] = $curfont["background"];
            }

            if ( ! isset($fontstyle["color"])) {
                $fontstyle["color"] = $curfont["color"];
            }

            $this->SetDefaultFont($fontstyle);
        } else {
            $curfont = $fontstyle = $this->GetCurrentFont();
        }

        $c = $this->GetFillColor();

        $bg = $c["red"] != 255 || $c["green"] != 255 || $c["blue"] != 255;

        if ($width == null) {
            $width = $this->getPageWidth() - $this->rMargin - $this->GetX();
        }

        $chunks = $this->SplitHTMLChunks($html, $fontstyle);

        $lines = $this->countChunkedLines($chunks, $width);

        $h = $fontstyle["size"] / 2;

        $cellheight = $lines * $h;

        if ($cellheight > $this->cur_max_h) {
            $this->cur_max_h = $cellheight;
        }

        $this->OutputText($chunks, $this->GetX(), $width, $border, $align, $link, $bg, $valign, $cellheight);

        if ($fontstyle != null) {
            $this->SetDefaultFont($curfont);
        }
    }

    /**
     * @param        $text
     * @param null   $style
     * @param string $align
     */
    public function FlowText($text, $style = null, $align = "L")
    {
        $text = utf8_decode($text);

        if ($style != null) {
            $curfont = $this->GetCurrentFont();

            if (is_string($style)) {
                $style = array(
                    "style" => $style
                );
            }

            $this->SetDefaultFont($style);
        }

        $realstyle = $this->GetCurrentFont();

        $h = $this->FontSizePt / 2;

        if ($this->cur_line_h < $h) {
            $this->cur_line_h = $h;
        }

        while ($text != "") {
            $x = $this->GetX();
            $w = $this->getPageWidth() - $this->rMargin - $x;

            $set = $this->SplitTextAt($text, $w);

            if ($set == null) {
                break;
            }

            $chunk = $set[0];

            if (isset($set[1])) {
                $text = $set[1];

                if ($chunk != "") {
                    $chunk .= " ";
                }
            } else {
                $text = "";
            }

            if ($chunk != "") {
                $cw = $this->GetStringWidth($chunk);

                if ($align == "L" || $this->curFlowLineAlign != "L") {
                    $this->curFlowLineAlign = $align;
                }

                $this->curFlowLine[] = array(
                    "x"     => $x,
                    "w"     => $cw,
                    "style" => $realstyle,
                    "text"  => $chunk
                );

                $this->SetX($x + $cw);
            }

            if ($text != "") {
                $this->Ln();
                $this->cur_line_h = $h;
            }
        }

        if ($style != null) {
            $this->SetDefaultFont($curfont);
        }
    }

    /**
     * @param        $html
     * @param array  $bstyle
     * @param string $align
     */
    public function HTMLText($html, $bstyle = array(), $align = "L")
    {
        if ($bstyle == null) {
            $bstyle = array();
        } elseif ( ! is_array($bstyle)) {
            $bstyle = array(
                "style" => $bstyle
            );
        }

        $doc = new \DOMDocument();

        $doc->loadXML("<root/>");

        $html = preg_replace("/&(?!([a-z\d]+|#\d+|#x[a-f\d]+);)/i", "&amp;", $html);
        $html = preg_replace("/<br\s*>/i", "<br/>", $html);

        mb_substitute_character("none");

        $html = mb_convert_encoding($html, "UTF-8", "UTF-8");

        $f = $doc->createDocumentFragment();
        if ( ! $f->appendXML($html)) {
            return;
        }

        $doc->documentElement->appendChild($f);

        $cur = $doc->documentElement;

        $hs = array();

        $inpara = null;

        while ($cur != null) {
            if ($cur->nodeType == XML_TEXT_NODE) {
                if ($inpara === 0) {
                    $this->Ln();
                    $this->Ln();
                }

                $inpara = 1;

                if (count($hs) > 0) {
                    $style = array_merge($bstyle, array("style" => implode("", $hs)));
                    if (isset($bstyle["style"])) {
                        $style["style"] .= $bstyle["style"];
                    }
                } else {
                    $style = $bstyle;
                }

                $this->FlowText($cur->nodeValue, $style, $align);
            } elseif ($cur->nodeType == XML_ELEMENT_NODE) {
                switch (strtolower($cur->nodeName)) {
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
                        if ($inpara !== null && $inpara < 2) {
                            $this->Ln();
                            $this->Ln();
                        }

                        $inpara = 2;
                        break;
                }
            }

            if ($cur->firstChild) {
                $cur = $cur->firstChild;
            } elseif ($cur->nextSibling) {
                $cur = $cur->nextSibling;
            } else {
                while ($cur != null && $cur->nextSibling == null) {
                    $cur = $cur->parentNode;

                    if ($cur != null) {
                        switch (strtolower($cur->nodeName)) {
                            case "b":
                            case "i":
                            case "u":
                                array_pop($hs);
                                break;
                            case "p":
                                $inpara = 0;
                        }
                    }
                }

                if ($cur != null) {
                    $cur = $cur->nextSibling;
                }
            }
        }
    }

    /**
     * @param null $name
     * @param null $style
     * @param null $size
     */
    public function SetDefaultFont($name = null, $style = null, $size = null)
    {
        $bgcolor = $this->default_font["background"];
        $color   = $this->default_font["color"];

        if (is_array($name)) {
            if (isset($name["size"])) {
                $size = $name["size"];
            } else {
                $size = $this->default_font["size"];
            }

            if (isset($name["style"])) {
                $style = $name["style"];
            } else {
                $style = $this->default_font["style"];
            }

            if (isset($name["background"])) {
                $bgcolor = $name["background"];
            }

            if (isset($name["color"])) {
                $color = $name["color"];
            }

            if (isset($name["name"])) {
                $name = $name["name"];
            } else {
                $name = $this->default_font["name"];
            }
        } else {
            if ($name === null) {
                $name = $this->default_font["name"];
            }

            if ($style === null) {
                $style = $this->default_font["style"];
            }

            if ($size === null) {
                $size = $this->default_font["size"];
            }
        }

        $this->SetFont($name, $style, $size);
        $this->SetFillColor($bgcolor);
        $this->SetTextColor($color);
    }

    /**
     * @return array
     */
    public function GetCurrentFont()
    {
        $style = $this->FontStyle;

        if ($this->underline) {
            $style .= "U";
        }

        return array(
            "name"       => $this->FontFamily,
            "style"      => $style,
            "size"       => $this->FontSizePt,
            "background" => $this->GetFillColor(),
            "color"      => $this->GetTextColor()
        );
    }

    /**
     * Random stuff to do fancy shit.
     * This was stolen from a random web forum and modified until it made sense.
     *
     * @param      $angle
     * @param null $x
     * @param null $y
     */
    public function Rotate($angle, $x = null, $y = null)
    {
        if ($x == null) {
            $x = $this->x;
        }

        if ($y == null) {
            $y = $this->y;
        }

        if ($this->angle != 0) {
            $this->_out('Q');
        }

        $this->angle = $angle;

        if ($angle != 0) {
            $angle *= M_PI / 180;
            $c  = cos($angle);
            $s  = sin($angle);
            $cx = $x * $this->k;
            $cy = ($this->GetPageHeight() - $y) * $this->k;

            $this->_out(sprintf('q %.5f %.5f %.5f %.5f %.2f %.2f cm 1 0 0 1 %.2f %.2f cm', $c, $s, -$s, $c, $cx, $cy,
                -$cx, -$cy));
        }
    }

    /**
     * @param $o
     */
    public function setDefaultOrientation($o)
    {
        if (is_string($o) && $o != "" && (($o = strtoupper($o[0])) == "P" || $o == "L")) {
            $this->defaultOrientation = $o;
        }
    }

    /**
     * @param null    $o
     * @param string  $f
     * @param integer $rotation
     */
    public function AddPage($orientation = null, $size = "", $rotation = 0)
    {
        /* Override FPDF's default orientation as it can only be set in
         * FPDF's constructor.
         */
        if ($orientation == null) {
            $orientation = $this->defaultOrientation;
        }

        parent::AddPage($orientation, $size, $rotation);

        /* Fudge starting position so all pages start at the same point
         * regardless of how much header there is.
         */
        $this->SetY($this->tMargin);

        $this->cur_line_h = 0;
    }

    /**
     * @param null $h
     */
    public function Ln($h = null)
    {
        $this->emitCurFlowLine();

        if ($this->InHeader || $this->InFooter) {
            if ($h === null) {
                $h = $this->cur_line_h;
            }

            if ($h === null) {
                $h = $this->FontSizePt / 2;
            }

            $this->cur_line_h = null;

            return parent::Ln($h);
        }

        $curfont = $this->GetCurrentFont();

        if (count($this->valign_defered) > 0) {
            $max_h = $this->cur_max_h;

            if ($h != null && $h > $max_h) {
                $max_h = $h;
            }

            foreach ($this->valign_defered as $item) {
                $th = $item["height"];

                $offset = $max_h - $th;

                if ($item["valigndata"] == "M") {
                    $offset /= 2;
                }

                $this->OutputText($item["chunks"], $item["x"], $item["width"], $item["border"], $item["align"],
                    $item["link"], $item["bg"], $offset, $item["height"]);
            }

            $this->valign_defered = array();
        }

        while (count($this->excess_text) > 0) {
            $saved_defered = array();

            if (count($this->defered_borders) != 0) {
                foreach ($this->defered_borders as &$item) {
                    $saved_defered[$item["x"]] = array(
                        "x"          => $item["x"],
                        "width"      => $item["width"],
                        "border"     => $item["border"],
                        "background" => $item["background"]
                    );

                    if (strpos($item["border"], "B") !== false) {
                        $item["border"] = str_replace("B", "", $item["border"]);
                    }
                }

                $this->handleDeferedBorders();
            }

            $this->AddPage();

            $set               = $this->excess_text;
            $this->excess_text = array();

            foreach ($set as $item) {
                $this->OutputText($item["chunks"], $item["x"], $item["width"], $item["border"], $item["align"],
                    $item["link"], $item["bg"], $item["valigndata"], $item["height"]);
            }

            if (count($this->defered_borders) > 0) {
                foreach ($this->defered_borders as $item) {
                    unset($saved_defered[$item["x"]]);
                }
            }

            if (count($saved_defered) > 0) {
                foreach ($saved_defered as $item) {
                    $this->defered_borders[] = $item;
                }
            }
        }

        $this->cur_max_h = 0;

        $this->handleDeferedBorders($h);

        $this->SetDefaultFont($curfont);

        if ($h === null) {
            $h = $this->cur_line_h;
        }

        if ($h === null) {
            $h = $this->FontSizePt / 2;
        }

        $this->cur_line_h = null;

        parent::Ln($h);
    }

    /**
     * @param      $r
     * @param null $g
     * @param null $b
     */
    public function SetDrawColor($r, $g = null, $b = null)
    {
        $c = $this->parseColours($r, $g, $b);

        return parent::SetDrawColor($c["red"], $c["green"], $c["blue"]);
    }

    /**
     * @return array|null
     */
    public function GetDrawColor()
    {
        return $this->decodePDFColour($this->DrawColor, "G", "RG");
    }

    /**
     * @param      $r
     * @param null $g
     * @param null $b
     */
    public function SetFillColor($r, $g = null, $b = null)
    {
        $c = $this->parseColours($r, $g, $b);

        return parent::SetFillColor($c["red"], $c["green"], $c["blue"]);
    }

    /**
     * @return array|null
     */
    public function GetFillColor()
    {
        return $this->decodePDFColour($this->FillColor, "g", "rg");
    }

    public function SetTextColor($r, $g = null, $b = null)
    {
        $c = $this->parseColours($r, $g, $b);

        return parent::SetTextColor($c["red"], $c["green"], $c["blue"]);
    }

    public function GetTextColor()
    {
        return $this->decodePDFColour($this->TextColor, "g", "rg");
    }

    /**
     * Override FPDF's Image method to handle interlaced PNGs
     *
     * @see FPDF::Image()
     */
    public function Image($file, $x = null, $y = null, $w = 0, $h = 0, $type = '', $link = '')
    {
        if (preg_match("/\.png$/i", $file) || strtolower($type) == "png") {
            $file = $this->fixInterlacedPNG($file);

            // This may return a temp without a png extension
            $type = "png";
        }

        return parent::Image($file, $x, $y, $w, $h, $type, $link);
    }

    /**
     * Emit PDF code to start a "Pagination" "Artifact"
     *
     * This must be used with endPageNumbers().
     *
     * Exactly what you can and can't do within a footer is fairly restricted,
     * so it's recommended that you emit footers as in the example document:
     * @example test/example.php 33 5 startPageNumbers() example
     *
     * Note that this feature was introduced in version 1.4 of the PDF
     * specification, so you _MUST_ not use this method if you're producing
     * PDFs with a lower version number.
     */
    public function startPageNumbers()
    {
        $this->_out("/Artifact << /Type /Pagination\n/SubType /Footer >> BDC");
    }

    /**
     * Emit PDF code to end a "Pagination" "Artifact"
     *
     * @see PDFLib::startPageNumbers()
     */
    public function endPageNumbers()
    {
        $this->_out("EMC");
    }

    // Helper functions

    private function SplitHTMLChunks($html, $fontstyle)
    {
        $html = str_replace(chr(160), " ", $html);

        if (strip_tags($html) == $html) {
            $html = nl2br(htmlspecialchars($html));
        } else {
            $html = preg_replace("/&(?!([a-z\d]+|#\d+|#x[a-f\d]+);)/i", "&amp;", $html);
            $html = preg_replace("/<br\s*>/i", "<br/>", $html);
        }

        mb_substitute_character("none");

        $html = mb_convert_encoding($html, "UTF-8", "UTF-8");

        if ($html == "") {
            return array(
                array(
                    "text"     => "",
                    "style"    => $fontstyle,
                    "newlines" => 0
                )
            );
        }

        $doc = new \DOMDocument();

        $doc->loadXML("<root/>");

        $f = $doc->createDocumentFragment();

        if ( ! $f->appendXML($html)) {
            return array();
        }

        $doc->documentElement->appendChild($f);

        $cur = $doc->documentElement;

        $hs = array();

        $inpara = null;

        $chunks = array();

        while ($cur != null) {
            if ($cur->nodeType == XML_TEXT_NODE) {
                if ($inpara === 0) {
                    $chunks[count($chunks) - 1]["newlines"] += 2;
                }

                $inpara = 1;

                if (count($hs) > 0) {
                    $style = array_merge($fontstyle, array("style" => implode("", $hs)));
                    if (isset($fontstyle["style"])) {
                        $style["style"] .= $fontstyle["style"];
                    }
                } else {
                    $style = $fontstyle;
                }

                $chunks[] = array(
                    "text"     => $cur->nodeValue,
                    "style"    => $style,
                    "newlines" => 0
                );
            } elseif ($cur->nodeType == XML_ELEMENT_NODE) {
                switch (strtolower($cur->nodeName)) {
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
                        $chunks[count($chunks) - 1]["newlines"]++;
                        break;
                    case "p":
                        if ($inpara !== null && $inpara < 2) {
                            $chunks[count($chunks) - 1]["newlines"] += 2;
                        }

                        $inpara = 2;
                        break;
                }
            }

            if ($cur->firstChild) {
                $cur = $cur->firstChild;
            } elseif ($cur->nextSibling) {
                $cur = $cur->nextSibling;
            } else {
                while ($cur != null && $cur->nextSibling == null) {
                    $cur = $cur->parentNode;

                    if ($cur != null) {
                        switch (strtolower($cur->nodeName)) {
                            case "b":
                            case "i":
                            case "u":
                                array_pop($hs);
                                break;
                            case "p":
                                $inpara = 0;
                        }
                    }
                }

                if ($cur != null) {
                    $cur = $cur->nextSibling;
                }
            }
        }

        return $chunks;
    }

    private function emitCurFlowLine()
    {
        if (count($this->curFlowLine) == 0) {
            $this->curFlowLineAlign = null;

            return;
        }

        if ($this->GetY() + $this->cur_line_h > $this->PageBreakTrigger) {
            // AddPage wipes cur_line_h for good reasons however we
            // need it to be valid to handle the eventual Ln() at
            // the end of this line.
            $clh = $this->cur_line_h;

            $this->AddPage();

            $this->cur_line_h = $clh;
        }

        $firstset = current($this->curFlowLine);

        $x = $firstset["x"];

        $curfont = $this->GetCurrentFont();

        $offset = 0;

        switch ($this->curFlowLineAlign) {
            case "C":
            case "R":
                $tw = 0;

                foreach ($this->curFlowLine as $set) {
                    $tw += $set["w"];
                }

                $mw = $this->getPageWidth() - $this->rMargin - $x;

                $offset = $mw - $tw;

                if ($this->curFlowLineAlign == "C") {
                    $offset /= 2;
                }

            // Fall through
            default:
                $this->SetX($x + $offset);

                foreach ($this->curFlowLine as $set) {
                    if ($set["style"] != null) {
                        $this->SetDefaultFont($set["style"]);
                    }

                    $this->Cell($set["w"], $this->FontSizePt / 2, $set["text"]);

                    if ($set["style"] != null) {
                        $this->SetDefaultFont($curfont);
                    }
                }
        }

        $this->curFlowLine      = array();
        $this->curFlowLineAlign = null;
    }

    private function OutputText($chunks, $x, $width, $border, $align, $link, $bg, $valigndata, $cellheight = null)
    {
        $lengths = $this->getChunkedLineLengths($chunks, $width);

        if (count($lengths) == 0 || (count($lengths) == 1 && $lengths[0] == 0)) {
            $valigndata = 0;
        } elseif ($this->InHeader || $this->InFooter) { // TODO: untested
            $valigndata = 0;
        } elseif (is_string($valigndata)) {
            if ($valigndata != "T") {
                $this->valign_defered[] = array(
                    "chunks"     => $chunks,
                    "x"          => $x,
                    "width"      => $width,
                    "border"     => $border,
                    "align"      => $align,
                    "link"       => $link,
                    "bg"         => $bg,
                    "valigndata" => $valigndata,
                    "height"     => $cellheight
                );

                $this->SetX($x + $width);

                return;
            } else {
                $valigndata = 0;
            }
        }

        $cur_line_h = 0;

        $next_page = array();
        $curborder = "";

        if ($this->InHeader || $this->InFooter) {
            $curborder = $border;
        } else {
            if ($border == 1) {
                $border = "TRBL";
            } elseif ($border === 0) {
                $border = "";
            }

            if (strpos($border, "T") !== false) {
                $curborder = "T";
                $border    = str_replace("T", "", $border);
            }
        }

        $output = false;

        $starty = $this->GetY();

        if ($valigndata > 0) {
            $firstchunk = $chunks[0];

            $this->SetDefaultFont($firstchunk["style"]);

            $offset = min($valigndata, $this->cur_line_h);

            $rh = $this->PageBreakTrigger - ($this->FontSizePt / 2) - $this->GetY();

            if ($offset > $rh) {
                $offset = $rh;
            }

            $cur_line_h += $offset;
            $v = $valigndata;
            $valigndata -= $offset;

            $this->SetX($x);

            $this->Cell($width, $offset, "", $curborder, 0, $align, $bg, $link);
            parent::Ln($offset);

            $output    = true;
            $curborder = "";
        }

        $this->SetX($x);

        $cx = 0;

        $first = true;

        $j = 0;

        $lineended = true;

        foreach ($chunks as $i => $chunk) {
            if (count($next_page) > 0) {
                $next_page[] = $chunk;
            } else {
                $this->SetDefaultFont($chunk["style"]);

                $text = $chunk["text"];

                while ($this->InHeader || $this->InFooter || $this->GetY() + $this->FontSizePt < $this->PageBreakTrigger) {
                    $lineended = false;

                    $lines = $this->SplitTextAt($text, $width - $cx, false, false);

                    $cw = $w = $this->GetStringWidth($lines[0]);

                    if ($cx == 0) {
                        $lines[0] = ltrim($lines[0]);
                    }

                    $talign = $align;

                    if (isset($lines[1]) || $chunk["newlines"] > 0 || $i == count($chunks) - 1) {
                        $cw       = $width - $cx;
                        $lines[0] = rtrim($lines[0]);

                        if ($cx != 0 && $align == "C") {
                            $talign = "L";
                        }
                    } elseif ($cx == 0 && $align != "L") {
                        $offset = $width - $lengths[$j];

                        if ($align == "C") {
                            $offset /= 2;
                            $talign = "R";
                        }

                        $cw += $offset;
                        $cx = $offset;
                    }

                    $cx += $w;

                    $this->Cell($cw, $this->FontSizePt / 2, $lines[0], $curborder, 0, $talign, $bg, $link);

                    $output = true;

                    if (isset($lines[1])) {
                        $lineended = true;

                        $curborder = "";

                        parent::Ln();

                        $j++;
                        $cx = 0;

                        $cur_line_h += $this->FontSizePt / 2;

                        $this->SetX($x);

                        $text = $lines[1];
                    } else {
                        $text = "";

                        break;
                    }
                }

                if ($text != "") {
                    $next_page[] = array(
                        "text"     => $text,
                        "style"    => $chunk["style"],
                        "newlines" => $chunk["newlines"]
                    );
                } elseif ($chunk["newlines"] > 0) {
                    $lineended = true;

                    $curborder = "";

                    $cx = 0;

                    for ($i = 0; $i < $chunk["newlines"] && $this->GetY() + $this->FontSizePt < $this->PageBreakTrigger; $i++) {
                        $j++;
                        parent::Ln();

                        $cur_line_h += $this->FontSizePt / 2;
                    }

                    $this->SetX($x);
                }
            }
        }

        if ($output && ! $lineended) {
            parent::Ln();

            $cur_line_h += $this->FontSizePt / 2;
        } else {
            $border .= $curborder;
        }

        if ($this->cur_line_h < $cur_line_h) {
            $this->cur_line_h = $cur_line_h;
        }

        $this->SetXY($x + $width, $starty);

        if ($next_page != array()) {
            $this->excess_text[] = array(
                "chunks"     => $next_page,
                "x"          => $x,
                "width"      => $width,
                "border"     => $border,
                "align"      => $align,
                "link"       => $link,
                "bg"         => $bg,
                "valigndata" => $valigndata,
                "height"     => $this->countChunkedLines($next_page, $width) * $this->FontSizePt / 2
            );

            if ($output && (($border !== 0 && $border != "") || $bg)) {
                $this->defered_borders[] = array(
                    "x"          => $x,
                    "width"      => $width,
                    "border"     => str_replace("B", "", $border),
                    "background" => $bg ? $this->GetFillColor() : null,
                    "h"          => $cur_line_h
                );
            }
        } elseif ( ! $this->InHeader && ! $this->InFooter && $output && (($border !== 0 && $border != "") || $bg)) {
            $this->defered_borders[] = array(
                "x"          => $x,
                "width"      => $width,
                "border"     => $border,
                "background" => $bg ? $this->GetFillColor() : null,
                "h"          => $cur_line_h
            );
        }
    }

    private function handleDeferedBorders($lh = null)
    {
        if (count($this->defered_borders) == 0) {
            return;
        }

        if ($lh != null) {
            $h = max($this->cur_line_h, $lh);
        } else {
            $h = $this->cur_line_h;
        }

        if ($this->GetY() + $h > $this->PageBreakTrigger) {
            $h = $this->PageBreakTrigger - $this->GetY();
        }

        foreach ($this->defered_borders as $item) {
            $this->SetX($item["x"]);

            if (isset($item["background"])) {
                $sbg = $this->GetFillColor();

                $this->SetFillColor($item["background"]);

                if (isset($item["h"])) {
                    $y = $this->GetY();

                    $this->SetXY($item["x"], $y + $item["h"]);

                    $sh = $h;
                    $h -= $item["h"];

                    $b              = $item["border"];
                    $item["border"] = 0;
                }
            }

            if ($h > 0) {
                $this->Cell($item["width"], $h, "", $item["border"], null, null, isset($item["background"]));
            }

            if (isset($item["background"])) {
                $this->SetFillColor($sbg);

                if (isset($item["h"])) {
                    $this->SetXY($item["x"], $y);

                    $h = $sh;

                    $this->Cell($item["width"], $h, "", $b);
                }
            }
        }

        $this->defered_borders = array();
    }

    /**
     * Check if PNGs passed are interlaced and make a temporary de-interlaced
     * version if they are.
     *
     * De-interlaced versions are stored in the system temp directory and are
     * unlinked when the class is destructed.
     *
     * @param string $file Filename of the PNG file to be checked
     * @return string Filename of a non-interlaced PNG
     */
    private function fixInterlacedPNG($file)
    {
        if (isset($this->interlacedPNGs[$file])) {
            return $this->interlacedPNGs[$file];
        }

        $handle = fopen($file, "r");

        if (!$handle) {
            $this->Error("Cannot open ".$file);
        }

        $contents = fread($handle, 32);
        fclose($handle);

        if (ord($contents[28]) != 0) {
            $im = imagecreatefrompng($file);

            if (!$im) {
                $this->Error("Cannot create image from ".$file);
            }

            imageinterlace($im, false);
            imagealphablending($im, true);
            imagesavealpha($im, true);

            $tempname = tempnam(sys_get_temp_dir(), 'FOO');

            $ret = imagepng($im, $tempname);

            imagedestroy($im);

            if (!$ret) {
                $this->Error("Could not save a non-interlaced version of ".$file);
            }

            $this->interlacedPNGs[$file] = $tempname;
            $this->toUnlink[] = $tempname;

            return $tempname;
        }

        $this->interlacedPNGs[$file] = $file;

        return $file;
    }

    private function parseHTMLColour($colour)
    {
        $colour = trim($colour);

        if (preg_match("/^#([0123456789ABCDEF]{2})([0123456789ABCDEF]{2})([0123456789ABCDEF]{2})$/i", $colour, $regs)) {
            $red   = $regs[1];
            $green = $regs[2];
            $blue  = $regs[3];
        } elseif (preg_match("/^#([0123456789ABCDEF]{3})$/i", $colour, $regs)) {
            $red   = $regs[1][0] . $regs[1][0];
            $green = $regs[1][1] . $regs[1][1];
            $blue  = $regs[1][2] . $regs[1][2];
        }

        if (isset($red)) {
            $out = array();

            $out["red"]   = hexdec($red);
            $out["green"] = hexdec($green);
            $out["blue"]  = hexdec($blue);

            return $out;
        }

        /* TODO: Add support for standard named HTML colours
                switch($colour) {
                }
         */

        return array(
            "red"   => 255,
            "green" => 255,
            "blue"  => 255
        );
    }

    private function parseColours($r, $g, $b)
    {
        if ($r == null) {
            $r = 0;
        }

        if (is_array($r)) {
            return $r;
        }

        if (is_string($r) && $g == null && $b == null) {
            return $this->parseHTMLColour($r);
        }

        if ($g == null || $b == null || ($r == $g && $g == $b)) {
            return array("red" => $r, "green" => null, "blue" => null);
        }

        return array("red" => $r, "green" => $g, "blue" => $b);
    }

    private function decodePDFColour($string, $g, $c)
    {
        if (preg_match("/^([[:digit:].]*)\s+" . $g . "$/", $string, $regs)) {
            return $regs[1] * 255;
        }

        if (preg_match("/^([[:digit:].]*)\s+([[:digit:].]*)\s+([[:digit:].]*)\s+" . $c . "$/", $string, $regs)) {
            return array("red" => $regs[1] * 255, "green" => $regs[2] * 255, "blue" => $regs[3] * 255);
        }

        return null;
    }

    // Utility functions

    public function GetStringWidthLines($string, $lines)
    {
        $words = preg_split("/(\s+)/", $string, null, PREG_SPLIT_DELIM_CAPTURE);

        if (count($words) <= 2 || $lines <= 1) {
            return $this->GetStringWidth($string);
        }

        $min = 0;

        $widths = array();

        foreach ($words as $i => $w) {
            if ($w == "") {
                $widths[$i] = 0;
            } else {
                $widths[$i] = $this->GetStringWidth($w);
            }
        }

        $maxwidth = 0;

        for ($j = 1; $j < count($widths) && $lines > 0; $lines--) {
            if ($j > 0) {
                $wset = array_slice($widths, $j);
            } else {
                $wset = $widths;
            }
            $target = (array_sum($wset)) / $lines;

            $l = $widths[$j - 1];

            for (; $l < $target && $j < count($widths); $j += 2) {
                $l += $widths[$j] + $widths[$j + 1];
            }

            if ($j < count($widths)) {
                $cw = $widths[$j + 1];

                if ($l - $target < $target + $cw - $l) {
                    $j += 2;
                } else {
                    $l -= $cw + $widths[$j];
                }
            }

            if ($maxwidth < $l) {
                $maxwidth = $l;
            }
        }

        return $maxwidth;
    }

    public function SplitTextAt($string, $width, $splitatnl = true, $allowempty = true)
    {
        $strings = array();

        if ($string == "") {
            return null;
        }

        $append = "";

        if ($splitatnl && ($p = strpos($string, "\n")) !== false) {
            $append = substr($string, $p + 1);
            $string = substr($string, 0, $p);
        }

        if ($this->GetStringWidth($string) < $width) {
            if ($append != "") {
                return array($string, $append);
            } else {
                return array($string);
            }
        }

        $str    = "";
        $strlen = 0;

        while (preg_match("/^(\s*\S*\s*)(.*)$/s", $string,
                $regs) && ($strlen + ($len = $this->GetStringWidth($regs[1]))) < $width - 2) {
            $str .= $regs[1];
            $strlen += $len;

            if ( ! isset($regs[2])) {
                $string = "";
                $regs   = null;

                break;
            }

            $string = $regs[2];

            $regs = null;
        }

        if ($str == "" && ! $allowempty) {
            $str = $regs[1];

            if (isset($regs[2])) {
                $string = $regs[2];
            } else {
                $string = "";
            }
        }

        if ($append != "") {
            if ($string == "") {
                $string = $append;
            } else {
                $string .= "\n" . $append;
            }
        }

        return array($str, $string);
    }

    public function SplitIntoLines($input, $width)
    {
        $inlines = explode("\n", $input);

        $lines = array();
        foreach ($inlines as $line) {
            if ($line == "" || $this->GetStringWidth($line) <= $width) {
                $lines[] = $line;
                continue;
            }

            $set = preg_split("/(\s+)/", $line, null, PREG_SPLIT_DELIM_CAPTURE);

            $lengths = array();

            foreach ($set as $item) {
                $lengths[] = $this->GetStringWidth($item);
            }

            $outline = $set[0];
            $outlen  = $lengths[0];

            for ($i = 1; $i < count($set); $i += 2) {
                $wlen = $lengths[$i];
                $word = $set[$i];

                if (isset($lengths[$i + 1])) {
                    $wlen += $lengths[$i + 1];
                    $word .= $set[$i + 1];
                }

                if ($outlen + $wlen > $width) {
                    $lines[] = $outline;
                    $outline = $set[$i + 1];
                    $outlen  = $lengths[$i + 1];
                } else {
                    $outline .= $word;
                    $outlen += $wlen;
                }
            }

            $lines[] = $outline;
        }

        return $lines;
    }

    private function countChunkedLines($chunks, $width)
    {
        return count($this->getChunkedLineLengths($chunks, $width));
    }

    private function getChunkedLineLengths($chunks, $width)
    {
        $x    = 0;
        $text = "x";

        $curfont = $this->GetCurrentFont();

        $lengths = array();

        foreach ($chunks as $item) {
            $this->SetDefaultFont($item["style"]);

            $text = $item["text"];

            if (($w = $this->GetStringWidth($text)) < $width - $x) {
                $x += $w;
            } else {
                while (count($lines = $this->SplitTextAt($text, $width - $x, false, false)) > 1) {
                    $lengths[] = $x + $this->GetStringWidth($lines[0]);

                    $x    = 0;
                    $text = $lines[1];
                }

                $text = $lines[0];

                $x = $this->GetStringWidth($text);
            }

            if ($item["newlines"] > 0) {
                $lengths[] = $x;

                $x = 0;

                for ($i = 1; $i < $item["newlines"]; $i++) {
                    $lengths[] = 0;
                }
            }
        }

        if (strlen($text) > 0) {
            $lengths[] = $x;
        }

        $this->SetDefaultFont($curfont);

        return $lengths;
    }
}
