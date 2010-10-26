<?php

include_once("pdf/lib/pdfBase.php");

class fallback extends pdfBase {
	public function __construct() {
		parent::__construct();

		$this->setMessage("PDF class not defined");
	}

	public function display($args) {
		return null;
	}
}
