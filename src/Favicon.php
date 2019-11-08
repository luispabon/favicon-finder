<?php
declare(strict_types=1);

namespace FaviconFinder;

use DOMDocument;
use DOMNode;
use GuzzleHttp\ClientInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException as SimpleCacheInvalidArgumentException;
use Throwable;

/**
 * The favicon finder itself. It will attempt to figure out if a site has a default favicon (eg /favicon.ico) and,
 * if not, it will access the homepage and try to parse a favicon out of it.
 *
 * @package FaviconFinder
 */
class Favicon
{
    private const GUZZLE_OPTIONS    = ['allow_redirects' => true];
    private const DEFAULT_CACHE_TTL = 86400;

    /** @var ClientInterface */
    private $guzzle;

    /** @var CacheInterface */
    private $cache;

    /** @var int */
    private $cacheTtl;

    public function __construct(ClientInterface $guzzle, CacheInterface $cache, int $cacheTtl = self::DEFAULT_CACHE_TTL)
    {
        $this->guzzle   = $guzzle;
        $this->cache    = $cache;
        $this->cacheTtl = $cacheTtl;
    }

    /**
     * Retrieve the best favicon available at the given URL and return as a URL to the resource.
     *
     * Cache any found favicons on the given cache pool when cache ain't hit only, to allow favicons to renew
     * eventually if the source changed.
     *
     * @param string $url
     *
     * @return string|null
     * @throws SimpleCacheInvalidArgumentException
     */
    public function get(string $url): ?string
    {
        $parsedUrl = new Url($url);
        $baseUrl   = $parsedUrl->getBaseUrl();
        $cacheKey  = md5($baseUrl);
        $favicon   = $this->cache->get($cacheKey);

        if ($favicon === null) {
            // Try default icon first
            $favicon = $this->findDefaultIcon($parsedUrl);

            // Otherwise try parsing the homepage for it
            if ($favicon === null) {
                $favicon = $this->findIconInPage($parsedUrl);
            }

            if ($favicon !== null) {
                $this->cache->set($cacheKey, $favicon, $this->cacheTtl);
            }
        }

        return $favicon;
    }

    /**
     * Checks whether the default icon (at /favicon.ico) is present, and if so, return its url.
     */
    private function findDefaultIcon(Url $url): ?string
    {
        $defaultFavicon = sprintf('%s/favicon.ico', $url->getBaseUrl());

        try {
            $statusCode = $this->guzzle
                ->request('HEAD', $defaultFavicon, self::GUZZLE_OPTIONS)
                ->getStatusCode();

            if ($statusCode >= 200 && $statusCode <= 299) {
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
    private function findIconInPage(Url $url): ?string
    {
        $favicon = $this->parseIconOffPage($url->getBaseUrl());

        if ($favicon === '') {
            return null;
        }

        // Case of protocol-relative URLs
        if (strpos($favicon, '//') === 0) {
            $favicon = sprintf('%s:%s', $url->getScheme(), $favicon);
        }

        // Make sure the favicon is an absolute URL.
        if ($favicon && filter_var($favicon, FILTER_VALIDATE_URL) === false) {
            $favicon = rtrim($url->getBaseUrl(), '/') . '/' . ltrim($favicon, '/');
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

        $html    = $response->getBody()->getContents();
        $matches = [];

        preg_match('!<head.*?>.*</head>!ims', $html, $matches);

        if (count($matches) === 0) {
            return '';
        }

        $head = $matches[0];

        $dom = new DOMDocument();

        // Use error suppression, because the HTML might be too malformed.
        $favicon = '';
        if (@$dom->loadHTML($head)) {
            $links = $dom->getElementsByTagName('link');
            foreach ($links as $link) {
                /** @var DOMNode $link */
                switch (true) {
                    case $link->hasAttribute('rel') && strtolower($link->getAttribute('rel')) === 'shortcut icon':
                    case $link->hasAttribute('rel') && strtolower($link->getAttribute('rel')) === 'icon':
                    case $link->hasAttribute('href') && strpos($link->getAttribute('href'), 'favicon') !== false:
                        return $link->getAttribute('href');
                }
            }
        }

        return $favicon;
    }
}
