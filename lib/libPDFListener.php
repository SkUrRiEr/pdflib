<?php

/* PDF Listener
 *
 * Events from the PDF processing are sent to this class so it can process
 * them.
 *
 * At the moment this is headers and footers.
 */

interface libPDFListener
{
    public function onHeader();
    public function onFooter();
}
