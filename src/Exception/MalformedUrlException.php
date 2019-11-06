<?php

namespace Favicon\Exception;

use Throwable;

class MalformedUrlException extends UrlException
{
    public function __construct(string $url, Throwable $previous = null)
    {
        parent::__construct(sprintf('Malformed url `%s`', $url), 0, $previous);
    }
}
