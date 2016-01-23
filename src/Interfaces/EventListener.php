<?php namespace PDFLib\Interfaces;

/*
 * Events from the PDF processing are sent to this class so it can process them.
 */

interface EventListener
{
    /**
     * @return void
     */
    public function onHeader();

    /**
     * @return void
     */
    public function onFooter();
}
