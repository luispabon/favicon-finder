A library to determine a site's favicon
=======================================

Requirements
------------

- [PHP] (http://php.net/)

Usage
-----

    <?php

    include 'favicon.php';

    $favicon = new Favicon();

    echo $favicon->get('http://shiflett.org/');

    ?>
    
Composer
-----

You can also use [Composer](https://getcomposer.org) by adding the following lines in your `composer.json`:

    repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/ArthurHoaro/favicon.git"
        }
    ],
    "require": {
        "arthurhoaro/favicon": "dev-master"
    },
