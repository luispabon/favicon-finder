<?php

namespace Favicon\Exception;

use Throwable;

class NoHostUrlException extends UrlException
{
    public function __construct($url, Throwable $previous = null)
    {
        parent::__construct(sprintf('No host found at url `%s`', $url), 0, $previous);
    }

}