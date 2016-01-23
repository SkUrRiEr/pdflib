<?php namespace PDFLib\Interfaces;

interface DocumentType
{
    /**
     * @return string
     */
    public function getMimeType();

    /**
     * @return string
     */
    public function getExtension();

    /**
     * @return string
     */
    public function getContent();

    /**
     * @param EventListener $class
     *
     * @return void
     */
    public function addListener(EventListener $class);
}
