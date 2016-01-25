# pdflib
A basic PHP PDF library based on FPDF

The goal of this libary is to add basic typesetting primitives, basic HTML support and additional features to FPDF to simplify the document-specific code required. It also provides an object oriented class hierarchy for adding document classes, a file structure to put them in and a basic top-level script for browsers.


## pdf.php

pdf.php is a top-level interface to the libary and a set of hierarchical document classes which is expected to be a stand-alone way of viewing PDFs in a browser. It takes care of selecting the document class, handling some of Internet Explorer's peculiarities, client-side caching, and passing parameters.

### Usage
pdf.php is called like:

`http://site/path/to/dir/pdf.php[/name][/params][.pdf][?params=values...]`

(items in square brackets are optional)

The `.pdf` after the params is optional and will, if it exists, be stripped
out of the parameters.

Upon being called like so, page.php will attempt to include the file
`pdf/name.php` and if found, it will instansiate the class named `name`.

If no suitable file or class could be found, or the name is omitted, the file
`pdf/fallback.php` will be loaded instead. This class will be used as if it
were the class for the named pdf. In this case, the params as passed to
`->display()` will be all path components specified in the URL.

This class must be a subclass of the `pdfBase` class.

The `->display()` method of the selected class will be called with the params
as specified in the URL as an array as it's only parameter.

If the PDF is successfully generated, this method must return true. If there
is no data, it must return `null`, and if there is an error, it must return
`false`.

The `->getName()` method of the selected class will then be called with no
parameters.

This method will either return a string naming the PDF. (e.g. for a PDF
called `example.pdf`, this method will return `example`) `false` if there
is an error or `null` if there is no data.

If `->display()` or `->getName()` returns false, then a 500 class error page
will be generated with the string returned from `->getMessage()`. If either of
them return `null`, then a 404 page will be generated with the string from
`->getMessage()`. Otherwise, the `->Output()` method will be called on the class
to get the PDF data.

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
