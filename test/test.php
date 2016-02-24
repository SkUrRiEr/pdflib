<?php

require __DIR__ . '/../vendor/autoload.php';

include("test/example.php");

$doc = new PDFLib\Test\ExampleDocument();

$doc->display();

print $doc->Output(null, "S");
