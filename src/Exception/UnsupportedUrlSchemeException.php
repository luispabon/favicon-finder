<?php
declare(strict_types=1);

namespace FaviconFinder\Exception;

use Throwable;

class UnsupportedUrlSchemeException extends UrlException
{
    public function __construct(string $url, ?string $scheme, Throwable $previous = null)
    {
        parent::__construct(sprintf('Scheme `%s` unsupported at url `%s`', $scheme, $url), 0, $previous);
    }
}
