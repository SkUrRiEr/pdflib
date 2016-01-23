<?php namespace PDFLib;

use PDFLib\Interfaces\DocumentType;
use PDFLib\Interfaces\EventListener;

abstract class BaseDocument implements EventListener
{
    private $name;
    private $message;
    private $pdf;

    public function __construct(DocumentType $pdf)
    {
        $this->pdf = $pdf;

        $pdf->addListener($this);

        $this->name = false;
        $this->message = "No reason specified.";
    }

    abstract public function display($args);

    protected function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    protected function setMessage($message)
    {
        $this->message = $message;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getDocClass()
    {
        return $this->pdf;
    }

    public function getETag($args)
    {
        return null;
    }

    public function onHeader()
    {
    }

    public function onFooter()
    {
    }

    // Pass through method calls to $this->pdf
    public function __call($method, $arguments)
    {
        if (method_exists($this->pdf, $method)) {
            return call_user_method_array($method, $this->pdf, $arguments);
        }

        throw new Exception("Method not found");
    }

    // Pass through property access to $this->pdf
    public function __isset($property)
    {
        return isset($this->pdf->$property);
    }

    public function __set($name, $value)
    {
        if (property_exists($this->pdf, $name)) {
            $this->pdf->$name = $value;

            return;
        } else {
            $this->$name = $value;
        }
    }

    public function __get($name)
    {
        if (property_exists($this->pdf, $name)) {
            return $this->pdf->$name;
        }

        throw new Exception("Property not found");
    }
}
