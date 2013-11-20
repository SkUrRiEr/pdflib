<?php

/* PDF Class Interface
 *
 * This is for the RTF / PDF switching stuff
 */

include_once("lib/libPDFListener.php");

interface libPDFInterface {
	public function getMimeType();
	public function getExtension();
	public function getContent();
	public function addListener(libPDFListener $class);
}
