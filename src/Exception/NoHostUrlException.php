<?php
declare(strict_types=1);

namespace FaviconFinder\Exception;

use Throwable;

/**
 * @codeCoverageIgnore
 */
class NoHostUrlException extends UrlException
{
    public function __construct($url, Throwable $previous = null)
    {
        parent::__construct(sprintf('No host found at url `%s`', $url), 0, $previous);
    }

}
