<?php

/* PDF Class Interface
 *
 * This is for the RTF / PDF switching stuff
 */

interface libPDFInterface {
	public function getMimeType();
	public function getExtension();
	public function getContent();
}