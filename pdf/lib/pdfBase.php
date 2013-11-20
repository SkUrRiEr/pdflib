<?php

include_once("lib/libPDFInterface.php");
include_once("lib/libPDFListener.php");

abstract class pdfBase implements libPDFListener {
	private $name;
	private $message;
	private $pdf;

	public function __construct(libPDFInterface $pdf) {
		$this->pdf = $pdf;

		$pdf->addListener($this);

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

	public function getDocClass() {
		return $this->pdf;
	}

	public function getETag($args) {
		return null;
	}

	public function onHeader() {
	}

	public function onFooter() {
	}

	// Pass through method calls to $this->pdf
	public function __call($method, $arguments) {
		if( method_exists($this->pdf, $method) )
			return call_user_method_array($method, $this->pdf, $arguments);

		throw new Exception("Method not found");
	}

	// Pass through property access to $this->pdf
	public function __isset($property) {
		return isset($this->pdf->$property);
	}

	public function __set($name, $value) {
		if( property_exists($this->pdf, $name) ) {
			$this->pdf->$name = $value;

			return;
		}

		throw new Exception("Property not found");
	}

	public function __get($name) {
		if( property_exists($this->pdf, $name) )
			return $this->pdf->$name;

		throw new Exception("Property not found");
	}
}
