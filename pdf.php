<?php

require __DIR__ . '/vendor/autoload.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_cache_limiter("private_no_expire");

use PDFLib\BaseDocument;

$items = array();

if (isset($_SERVER["PATH_INFO"]) && $_SERVER["PATH_INFO"] != "") {
    $path = preg_replace("/^\//", "", $_SERVER["PATH_INFO"]);

    if (preg_match("/^(.*)\.(.*?)$/", $path, $regs)) {
        $path = $regs[1];
    }

    $items = explode("/", $path);
}

// @TODO: Add factory to load docclass
$PDFLib = new PDFLib\PDFLib();

if ($_SERVER["HTTP_USER_AGENT"] == "contype") {
        header("Content-Type: application/pdf");

        exit;
}

$args = array();
for ($i = 1; $i < count($items); $i++) {
    $args[] = $items[$i];
}

$className = null;
$document = null;

if (count($items) > 0) {
    $className = ucfirst(current($items));
}

$namespacedClassName = "PDFLib\\{$className}Document";

if ($className != null && class_exists($namespacedClassName)) {
    $document = new $namespacedClassName($PDFLib);

    if (! is_subclass_of($document, \PDFLib\BaseDocument::class)) {
        $document = new \PDFLib\FallbackDocument($className, $PDFLib);
    }
}

$etag = $document->getETag($args);

if ($etag != null) {
    header("ETag: \"".$etag."\"");
}

$ret = $document->display($args);

if ($ret != null && $ret != false) {
    $name = $document->getName();

    if (!$name) {
        $ret = $name;
    }
}

if ($ret === null) {
    $content = "<html><head><title>PDF Page</title></head><body><h1>PDF Not Found</h1><h2>Error Message:</h2><p>".$document->getMessage()."</p></body></html>";

    header("HTTP/1.1 404 Page Not Found");
} elseif ($ret === FALSE) {
    $content = "<html><head><title>PDF Generation Failed</title></head><body><h1>PDF Generation Failed</h1><h2>Error Message:</h2><p>".$document->getMessage()."</p></body></html>";

    header("HTTP/1.1 500 Server Error");
} else {
    header("Content-Type: application/pdf");

    $content = $document->getContent();

    header("Content-Disposition: inline; filename=".$name.".pdf;");
    header("Content-Length: ".strlen($content));
}

print $content;
