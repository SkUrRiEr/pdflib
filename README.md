# pdflib
A basic PHP PDF library based on FPDF

The goal of this libary is to add basic typesetting primitives, basic HTML support and additional features to FPDF to simplify the document-specific code required. It also provides an object oriented class hierarchy for adding document classes, a file structure to put them in and a basic top-level script for browsers.


## Installation
The dependencies are stored in `composer.json`, so install composer (see https://getcomposer.org/ for instructions) then run:

```
composer install
```

The GD extension is also required to deinterlace interlaced PNGs.


## Contributing
All contributions must contain a signed-off-by line in accordance with the Developer Certificate of Origin: http://developercertificate.org/

All contributions must be licensed under the LGPL 2.1.

## Usage / API
`test/example.php` is a basic test case and provides a good reference for how a pdf class should work.

It's output can be found by running:

`php test/test.php > test.pdf`

The PDF file produced should be visually identical to the one saved in the top directory as `example.pdf`.
