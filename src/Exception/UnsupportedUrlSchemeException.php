<?php

namespace Favicon\Exception;

class UnsupportedUrlSchemeException extends UrlException
{
    public function __construct(string $url, ?string $scheme, \Throwable $previous = null)
    {
        parent::__construct(sprintf('Scheme `%s` unsupported at url `%s`', $scheme, $url), 0, $previous);
    }
}
