<?php
declare(strict_types=1);

namespace FaviconFinder;

use FaviconFinder\Exception\MalformedUrlException;
use FaviconFinder\Exception\NoHostUrlException;
use FaviconFinder\Exception\UnsupportedUrlSchemeException;
use PHPUnit\Framework\TestCase;

class UrlTest extends TestCase
{
    /**
     * @test
     * @dataProvider dodgyUrlsDataProvider
     */
    public function dodgyUrlsAreFerretedOut(string $dodgyUrl, string $expectedExceptionClass): void
    {
        $this->expectException($expectedExceptionClass);
        new Url($dodgyUrl);
    }

    /**
     * @test
     * @dataProvider goodUrlsDataProvider
     */
    public function goodUrlsAreParsedOutCorrectly(string $url, string $expectedBaseUrl, string $expectedScheme): void
    {
        $url = new Url($url);

        self::assertSame($expectedBaseUrl, $url->getBaseUrl());
        self::assertSame($expectedScheme, $url->getScheme());
    }

    public function dodgyUrlsDataProvider(): array
    {
        return [
            'only path'      => [
                'url'             => 'asdasd',
                'exception class' => NoHostUrlException::class,
            ],
            'invalid scheme' => [
                'url'             => 's3://foo.com',
                'exception class' => UnsupportedUrlSchemeException::class,
            ],
            'no host'        => [
                'url'             => 'http://',
                'exception class' => MalformedUrlException::class,
            ],
            'empty url'      => [
                'url'             => '',
                'exception class' => NoHostUrlException::class,
            ],
        ];
    }

    public function goodUrlsDataProvider(): array
    {
        return [
            'simple url'                         => [
                'url'      => 'http://domain.tld',
                'base url' => 'http://domain.tld',
                'scheme'   => 'http',
            ],
            'simple https url'                   => [
                'url'      => 'https://domain.tld',
                'base url' => 'https://domain.tld',
                'scheme'   => 'https',
            ],
            'url with trailing slash'            => [
                'url'      => 'http://domain.tld/',
                'base url' => 'http://domain.tld',
                'scheme'   => 'http',
            ],
            'url with port'                      => [
                'url'      => 'http://domain.tld:8080',
                'base url' => 'http://domain.tld:8080',
                'scheme'   => 'http',
            ],
            'user without password'              => [
                'url'      => 'https://user@domain.tld',
                'base url' => 'https://user@domain.tld',
                'scheme'   => 'https',
            ],
            'user password'                      => [
                'url'      => 'http://user:password@domain.tld',
                'base url' => 'http://user:password@domain.tld',
                'scheme'   => 'http',
            ],
            'url with unused info'               => [
                'url'      => 'https://domain.tld/index.php?foo=bar&bar=foo#foobar',
                'base url' => 'https://domain.tld',
                'scheme'   => 'https',
            ],
            'url with path and uppercase schema' => [
                'url'      => 'HTTP://domain.tld/my/super/path',
                'base url' => 'http://domain.tld',
                'scheme'   => 'http',
            ],
        ];
    }
}
