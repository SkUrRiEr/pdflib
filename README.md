# pdflib
A basic PHP PDF library based on FPDF

The goal of this libary is to add basic typesetting primitives, basic HTML support and additional features to FPDF to simplify the document-specific code required. It also provides an object oriented class hierarchy for adding document classes, a file structure to put them in and a basic top-level script for browsers.


## pdf.php

pdf.php is a top-level interface to the libary and a set of hierarchical document classes which is expected to be a stand-alone way of viewing PDFs in a browser. It takes care of selecting the document class, handling some of Internet Explorer's peculiarities, client-side caching, and passing parameters.

## Installation
This expects FPDF to be unzipped to /fpdf

## Contributing
All contributions must contain a signed-off-by line in accordance with the Developer Certificate of Origin: http://developercertificate.org/

All contributions must be licensed under the LGPL 2.1.

## Usage / API
pdf/test.php is a basic test case and provides a good reference for how a pdf class should work.

It's output can be found by loading:

http://site/path/to/dir/pdf.php/test

in a browser.

The PDF file produced is also saved in the top directory.
