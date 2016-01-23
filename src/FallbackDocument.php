<?php namespace PDFLib;

class FallbackDocument extends BaseDocument
{
    /**
     * FallbackDocument constructor.
     *
     * @param Interfaces\DocumentType $classname
     * @param                         $docclass
     */
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
