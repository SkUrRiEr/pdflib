<?php namespace PDFLib\Documents;

use Exception;
use PDFLib\Interfaces\DocumentType;
use PDFLib\Interfaces\EventListener;

abstract class BaseDocument implements EventListener
{
    /**
     * @var bool
     */
    private $name;

    /**
     * @var string
     */
    private $message;

    /**
     * @var DocumentType
     */
    private $pdf;

    public function __construct(DocumentType $pdf)
    {
        $this->pdf = $pdf;

        $pdf->addListener($this);

        $this->name    = false;
        $this->message = "No reason specified.";
    }

    /**
     * @param $args
     *
     * @return mixed
     */
    abstract public function display($args);

    /**
     * @param string $name
     */
    protected function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return bool|string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $message
     */
    protected function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return DocumentType
     */
    public function getDocClass()
    {
        return $this->pdf;
    }

    /**
     * @param $args
     *
     * @return null
     */
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

    /**
     * Pass through method calls to $this->pdf
     *
     * @param $method
     * @param $arguments
     *
     * @return mixed
     * @throws Exception
     */
    public function __call($method, $arguments)
    {
        if (method_exists($this->pdf, $method)) {
            return call_user_func_array([$this->pdf, $method], $arguments);
        }

        throw new Exception("Method not found");
    }

    /**
     * Pass through property access to $this->pdf
     *
     * @param $property
     *
     * @return bool
     */
    public function __isset($property)
    {
        return isset($this->pdf->$property);
    }

    /**
     * When setting a property, check the DocumentType class first, and set it there.
     *
     * Then fallback to the BaseDocument instance.
     *
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        if (property_exists($this->pdf, $name)) {
            $this->pdf->$name = $value;

            return;
        } else {
            $this->$name = $value;
        }
    }

    /**
     * When getting a property, check the DocumentType class and return from there.
     *
     * Then throw an exception if  not found.
     *
     * @param $name
     *
     * @return mixed
     * @throws Exception
     */
    public function __get($name)
    {
        if (property_exists($this->pdf, $name)) {
            return $this->pdf->$name;
        }

        throw new Exception("Property not found");
    }
}
