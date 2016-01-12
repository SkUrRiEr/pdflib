<?php

include_once("pdf/lib/pdfBase.php");

class fallback extends pdfBase
{
    public function __construct($classname, $docclass)
    {
        parent::__construct($docclass);

        $this->setMessage("PDF class not defined");
    }

    public function display($args)
    {
        return null;
    }
}
