<?php

namespace Favicon;

use DOMDocument;
use DOMNode;
use Favicon\Exception\MalformedUrlException;
use Favicon\Exception\UnsupportedUrlSchemeException;
use GuzzleHttp\ClientInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException as SimpleCacheInvalidArgumentException;
use Throwable;

class Favicon
{
    private const GUZZLE_OPTIONS = ['allow_redirects' => true];

    /** @var ClientInterface */
    private $guzzle;

    /** @var CacheInterface */
    private $cache;

    /** @var int */
    private $cacheTtl;

    public function __construct(ClientInterface $guzzle, CacheInterface $cache, int $cacheTtl = 86400)
    {
        $this->guzzle   = $guzzle;
        $this->cache    = $cache;
        $this->cacheTtl = $cacheTtl;
    }

    /**
     * Retrieve the best favicon available at the given URL and return as a URL to the resource.
     *
     * @param string $url
     *
     * @return string|null
     * @throws SimpleCacheInvalidArgumentException
     */
    public function get(string $url): ?string
    {
        $baseUrl = $this->getBaseUrl($url);
        $favicon = $this->cache->get($baseUrl);

        if ($favicon === null) {
            // Try default icon first
            $favicon = $this->findDefaultIcon($baseUrl);

            // Otherwise try parsing the homepage for it
            if ($favicon === null) {
                $favicon = $this->findIconInPage($baseUrl);
            }

            if ($favicon !== null) {
                $this->cache->set($baseUrl, $favicon, $this->cacheTtl);
            }
        }

        return $favicon;
    }

    /**
     * Checks whether the default icon (at /favicon.ico) is present, and if so, return its url.
     */
    private function findDefaultIcon(string $baseUrl): ?string
    {
        $defaultFavicon = sprintf('%s/favicon.ico', $baseUrl);

        try {
            $response = $this->guzzle->request('HEAD', $defaultFavicon, self::GUZZLE_OPTIONS);
            if ($response->getStatusCode() >= 200 && $response <= 299) {
                return $defaultFavicon;
            }
        } catch (Throwable $ex) {
            // Do nothing
        }

        return null;
    }

    /**
     * Analyzes the html on the baseUrl for any icons and returns it, sanitising the URL.
     */
    private function findIconInPage(string $baseUrl): ?string
    {
        $favicon = trim($this->parseIconOffPage($baseUrl));

        if ($favicon === '') {
            return null;
        }

        // Case of protocol-relative URLs
        if (strpos($favicon, '//') === 0) {
            // We're relying here baseUrl has passed by $this->getBaseUrl, which ensures there's a scheme
            $parsedBaseUrl = parse_url($baseUrl);
            $favicon       = sprintf('%s:%s', $parsedBaseUrl['scheme'], $favicon);
        }

        // Make sure the favicon is an absolute URL.
        if ($favicon && filter_var($favicon, FILTER_VALIDATE_URL) === false) {
            $favicon = rtrim($baseUrl, '/') . '/' . ltrim($favicon, '/');
        }

        return $favicon;
    }

    /**
     * Parse HTML on the given URL to find any html'd icons in there.
     */
    private function parseIconOffPage(string $url): string
    {
        try {
            $response = $this->guzzle->request('GET', $url, self::GUZZLE_OPTIONS);
        } catch (Throwable $ex) {
            return '';
        }

        $response->getBody()->rewind();

        $html    = $response->getBody()->getContents();
        $matches = [];

        preg_match('!<head.*?>.*</head>!ims', $html, $matches);

        if (count($matches) === 0) {
            return null;
        }

        $head = $matches[0];

        $dom = new DOMDocument();

        // Use error suppression, because the HTML might be too malformed.
        if (@$dom->loadHTML($head)) {
            $links = $dom->getElementsByTagName('link');
            foreach ($links as $link) {
                /** @var DOMNode $link */
                if ($link->hasAttribute('rel') && strtolower($link->getAttribute('rel')) === 'shortcut icon') {
                    return $link->getAttribute('href');
                }
            }
            foreach ($links as $link) {
                if ($link->hasAttribute('rel') && strtolower($link->getAttribute('rel')) === 'icon') {
                    return $link->getAttribute('href');
                }
            }
            foreach ($links as $link) {
                if ($link->hasAttribute('href') && strpos($link->getAttribute('href'), 'favicon') !== false) {
                    return $link->getAttribute('href');
                }
            }
        }

        return '';
    }

    /**
     * Given any HTTP/HTTPS url, return its base url (eg everything minus its path & query string).
     */
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
