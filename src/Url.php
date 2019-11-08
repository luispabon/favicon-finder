<?php
declare(strict_types=1);

namespace FaviconFinder;

use FaviconFinder\Exception\MalformedUrlException;
use FaviconFinder\Exception\NoHostUrlException;
use FaviconFinder\Exception\UnsupportedUrlSchemeException;

class Url
{
    /** @var string */
    private $baseUrl;

    /** @var string */
    private $scheme;

    public function __construct(string $url)
    {
        $parsed = parse_url($url);
        if ($parsed === false) {
            throw new MalformedUrlException($url);
        }

        $host = $parsed['host'] ?? null;
        if ($host === null) {
            throw new NoHostUrlException($url);
        }

        $scheme = strtolower($parsed['scheme'] ?? '');
        if ($scheme !== 'http' && $scheme !== 'https') {
            throw new UnsupportedUrlSchemeException($url, $scheme);
        }

        // Username and password
        $userPass = '';
        if (isset($parsed['user']) === true) {
            $userPass = sprintf(
                '%s%s@',
                $parsed['user'],
                isset($parsed['pass']) ? ":{$parsed['pass']}" : ''
            );
        }

        $port = $parsed['port'] ?? '';
        if ($port !== '') {
            $port = sprintf(':%s', $port);
        }

        $this->baseUrl = sprintf('%s://%s%s%s', $scheme, $userPass, $host, $port);
        $this->scheme = $scheme;
    }

    /**
     * @return string
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}
