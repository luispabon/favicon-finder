# Favicon Finder

Simple PHP library to work out the favicon for a site, given an URL.

It currently supports finding the default favicon, if it exists on the host (eg `/favicon.ico`) as well as some basic 
HTML parsing of the homepage to hunt for standard favicon tags. Does not support `apple` type icons or manifests, 
although it can be extended to do so if necessary (PRs welcome).

This library is based on [Arthur Hoaro's work](https://github.com/ArthurHoaro/favicon). 

Here are the changes you can see in this version:

  * Only return favicon paths, when found
  * PSR-16: Simple Cache support
  * More extensive HTML scraping tests
  * PHP 7.2+ support

## Requirements

- [PHP 7.2](http://php.net/)
- [php-xml](http://php.net/manual/fr/refs.xml.php) extension: parse HTML content
- [php-curl](https://www.php.net/manual/en/curl.installation.php)
- [Guzzle](https://github.com/guzzle/guzzle)
- GNU Make (or compatible): This is optional, if you want to contribute and use the Makefile targets available for
    running tests

## Installation

```shell script
composer req luispabon/favicon-finder
```

## Basic usage

```php
require_once('vendor/autoload.php');

$guzzle = new \GuzzleHttp\Client();

// You can use any PSR-16 implementation here - if you have none and don't care
// about caching, simply use the provided dummy cache implementation below
$cache = new \FaviconFinder\DummyCache();

// Cache lifetime in seconds (default is 86400 or 1 day)
$ttl = 60;

$favicon = new \FaviconFinder\Favicon($guzzle, $cache, $ttl);

echo $favicon->get('https://github.com/luispabon/favicon-finder');

// Displays: https://github.com/favicon.ico
var_dump($favicon->get('http://nofavicon.tld'));
// Returns null
```

## Contributing

Fork this repo, do your stuff, send a PR. Tests are mandatory:

  * PHP unit coverage must be 100%
  * Infection MSI must be 100%
  * PHPStan must show no errors 
  
The provided [Makefile](Makefile) has all the basic test targets and is what's in use in CI.
