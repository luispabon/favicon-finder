<?php
declare(strict_types=1);

namespace FaviconFinder\Exception;

use Throwable;

/**
 * @codeCoverageIgnore
 */
class MalformedUrlException extends UrlException
{
    public function __construct(string $url, Throwable $previous = null)
    {
        parent::__construct(sprintf('Malformed url `%s`', $url), 0, $previous);
    }
}
