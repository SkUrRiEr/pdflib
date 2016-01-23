<?php namespace PDFLib\Documents;

class FallbackDocument extends BaseDocument
{
    /**
     * FallbackDocument constructor.
     *
     * @param \PDFLib\Interfaces\DocumentType $classname
     * @param                                 $document
     */
    public function __construct($classname, $document)
    {
        parent::__construct($document);

        $this->setMessage("PDF class not defined");
    }

    /**
     * @param $args
     *
     * @return null
     */
    public function display($args)
    {
        return null;
    }
}
