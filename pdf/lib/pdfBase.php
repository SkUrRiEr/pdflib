<?php

include_once("lib/libPDF.php");

abstract class pdfBase extends libPDF {
	private $name;
	private $message;

	public function __construct($orientation = "P", $unit = "mm", $format = "A4") {
		parent::__construct($orientation, $unit, $format);

		$this->name = FALSE;
		$this->message = "No reason specified.";
	}

	abstract public function display($args);

	protected function setName($name) {
		$this->name = $name;
	}

	public function getName() {
		return $this->name;
	}

	protected function setMessage($message) {
		$this->message = $message;
	}

	public function getMessage() {
		return $this->message;
	}
}

?>
