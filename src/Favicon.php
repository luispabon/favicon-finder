<?php

namespace Favicon;

use Favicon\Exception\MalformedUrlException;
use Favicon\Exception\UnsupportedUrlSchemeException;
use GuzzleHttp\ClientInterface;
use Psr\SimpleCache\CacheInterface;

class Favicon
{
    /** @var ClientInterface */
    private $guzzle;

    /** @var CacheInterface */
    private $cache;

    /** @var int */
    private $cacheTtl;

    public function __construct(ClientInterface $client, CacheInterface $cache, int $cacheTtl = 86400)
    {
        $this->guzzle   = $client;
        $this->cache    = $cache;
        $this->cacheTtl = $cacheTtl;
    }

    /**
     * Retrieve the best favicon available at the given URL and return as a URL to the resource.
     *
     * @param string $url
     *
     * @return string|null
     */
    public function get(string $url): ?string
    {

    }

    private function getBaseUrl(string $url): string
    {
        $parsed = parse_url($url);
        if ($parsed === false) {
            throw new MalformedUrlException($url);
        }

        if ($parsed === false) {
            throw new MalformedUrlException($url);
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

        // parse_url will fail if there's no host
        $host = $parsed['host'];

        $port = $parsed['port'] ?? '';
        if ($port !== '') {
            $port = sprintf('%s:', $port);
        }

        return sprintf('%s://%s%s%s', $scheme, $userPass, $host, $port);
    }
}
