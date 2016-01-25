<?php

session_cache_limiter("private_no_expire");

require_once("pdf/lib/pdfBase.php");
require_once("lib/util.php");

$items = array();

if (isset($_SERVER["PATH_INFO"]) && $_SERVER["PATH_INFO"] != "") {
    $path = preg_replace("/^\//", "", $_SERVER["PATH_INFO"]);

    if (preg_match("/^(.*)\.(.*?)$/", $path, $regs)) {
        $path = $regs[1];
    }

    $items = explode("/", $path);
}

require_once("lib/libPDF.php");
$docclass = new libPDF();

if ($_SERVER["HTTP_USER_AGENT"] == "contype") {
        header("Content-Type: application/pdf");

        exit;
}

$args = array();
for ($i = 1; $i < count($items); $i++) {
    $args[] = $items[$i];
}

$class = null;
$cls = null;

if (count($items) > 0) {
    $class = current($items);
}

if ($class != null && file_which("pdf/".$class.".php")) {
    require_once("pdf/".$class.".php");

    if (class_exists($class)) {
        $cls = new $class($docclass);

        if (!is_subclass_of($cls, "pdfBase")) {
            $cls = null;
        }
    }
}

if ($cls == null) {
    require_once("pdf/fallback.php");

    $cls = new fallback($class, $docclass);

    if (!is_subclass_of($cls, "pdfBase")) {
        $cls = null;
    }

    $args = $items;
}

$etag = $cls->getETag($args);

if ($etag != null) {
    header("ETag: \"".$etag."\"");
}

$ret = $cls->display($args);

if ($ret != null && $ret != false) {
    $name = $cls->getName();

    if (!$name) {
        $ret = $name;
    }
}

if ($ret === null) {
    $content = "<html><head><title>PDF Page</title></head><body><h1>PDF Not Found</h1><h2>Error Message:</h2><p>".$cls->getMessage()."</p></body></html>";

    header("HTTP/1.1 404 Page Not Found");
} elseif ($ret === FALSE) {
    $content = "<html><head><title>PDF Generation Failed</title></head><body><h1>PDF Generation Failed</h1><h2>Error Message:</h2><p>".$cls->getMessage()."</p></body></html>";

    header("HTTP/1.1 500 Server Error");
} else {
    header("Content-Type: application/pdf");

    $content = $cls->getContent();

    header("Content-Disposition: inline; filename=".$name.".pdf;");
    header("Content-Length: ".strlen($content));
}

print $content;
