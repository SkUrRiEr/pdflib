<?php namespace PDFLib\Interfaces;

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
