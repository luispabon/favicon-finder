A library to determine a site's favicon
=======================================

This library is based on [Chris Shiflett work](https://github.com/shiflett/favicon). 

Here are the changes you can see in this version:

  * Cover more use case to find favicons
  * Various technical changes and improvements
  * Composer support
  * Unit tests
  * Not found favicon now return false (default action isn't this lib responsability)

Requirements
------------

- [PHP] (http://php.net/)

Composer
-----

Use [Composer](https://getcomposer.org) by adding the following lines in your `composer.json`:

    "require": {
        "arthurhoaro/favicon": "dev-master"
    },

Usage
-----

    <?php

    require_once('vendor/autoload.php');

    $favicon = new \Favicon\Favicon();

    echo $favicon->get('http://hoa.ro');
    // Displays: http://hoa.ro/themes/hoaro/img/favicon.png
    
