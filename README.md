# PHP XML Parser

A PHP XML parser class that provides an easy way to convert XML into native PHP
arrays and back again. It has no dependencies on any external libraries or
extensions bundled with PHP. The entire parser is concisely written in PHP.

This project is actively maintained. It is used in our production code. If you
spot an issue, please let us know through the Issues section on our Github
project page: https://github.com/revnode/xml/issues

## Why

As XML becomes less popular, the need for a parser moves from constant to
infrequent. It makes little sense to keep a parser resident in memory at all times
for functionality that might be used once every few days.

For example, just to get SimpleXML going, you will need to have the libxml2
library installed on your system. You will need xml, libxml, and simplexml
extensions installed for PHP. You will need to keep all those extensions in
memory for each request.

In contrast, this simple parser is less than 500 lines of code and is only
loaded when you need it. It has no dependencies, no required libraries or
extensions, and will work on any modern PHP installation. The price you pay for
that convenience is speed. It is much slower than SimpleXML. See the benchmarking
section for details.

In short, this project makes sense for those who want to simplify their PHP
install and use, have a need for a simple XML parser, but don't much care
about speed.

## Requirements

PHP 5.4.0+

## Install

Just place the xml.php file in a convenient location and include it in your
code.

## Design Goals

* Zero dependencies on external libraries or PHP extensions.
* Provide a parser from and to the XML standard.
* Provide support for the most commonly used parts of the XML standard.
* Maintain a minimal memory footprint during operation, even for large XML files.
* Maintain a codebase that is less than 1000 lines. Currently at less than 500 lines.

## Usage

### XML String to PHP Array

```php
<?php

require 'xml.php';

$xml = new xml('<?xml version="1.0" encoding="ISO-8859-1"?>
<breakfast_menu>
	<food>
		<name>Waffles</name>
	</food>
</breakfast_menu>');
var_dump($xml->data);

?>
```

### XML File to PHP Array

```php
<?php

require 'xml.php';

$xml = new xml('some_xml_file.xml');
var_dump($xml->data);

?>
```

### PHP Array to XML String

```php
<?php

require 'xml.php';

$xml = new xml([
	'breakfast_menu' => [
		[
			'food' => [
				'name' => 'Waffles'
			]
		]
	]
]);
echo $xml;

?>
```

## Benchmarks

A benchmark to measure memory performance for large xml files is on the todo list.

The following is the result of the benchmark code executing. The code is executed
32 times and then averaged. The code was run on an Intel(R) Atom(TM) CPU D510
@ 1.66GHz with 4GB of RAM.

```
simplexml: Parsed 100 line xml file in 0.0033 seconds.
simplexml: Parsed 10000 line xml file in 2.0063 seconds.
xml: Parsed 100 line xml file in 0.0251 seconds.
xml: Parsed 10000 line xml file in 11.8074 seconds.
```

For small files, the simplexml parser is almost 8 times faster. For larger files,
it is 6 times faster.

This is a signficant performance difference. However, if you're using the code
on a file that's not that large, even with the performance degradation, the job
will finish within a fraction of a second.

## Tests

None, for now.

## License

MIT, see LICENSE.md