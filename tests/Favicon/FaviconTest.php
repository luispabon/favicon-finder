<?php

namespace Favicon;

use Favicon\Exception\UrlException;
use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

class FaviconTest extends TestCase
{
    private const TTL = 984343;

    /**
     * @var Favicon
     */
    private $instance;

    /**
     * @var ClientInterface|MockObject
     */
    private $guzzleMock;

    /**
     * @var CacheInterface|MockObject
     */
    private $cacheMock;

    public function setUp(): void
    {
        $this->guzzleMock = $this->getMockBuilder(ClientInterface::class)->getMock();
        $this->cacheMock  = $this->getMockBuilder(CacheInterface::class)->getMock();
        $this->instance   = new Favicon($this->guzzleMock, $this->cacheMock, self::TTL);
    }

    public function tearDown(): void
    {
        $this->guzzleMock = null;
        $this->cacheMock  = null;
        $this->instance   = null;
    }

    /**
     * @test
     * @dataProvider dodgyUrlsDataProvider
     */
    public function dodgyUrlsAreFerretedOut(string $dodgyUrl): void
    {
        $this->expectException(UrlException::class);
        $this->instance->get($dodgyUrl);
    }

    /**
     * @test
     * @dataProvider goodUrlsDataProvider
     */
    public function successWithCache(string $url, string $expectedBaseUrl): void
    {
        $expectedCacheKey = md5($expectedBaseUrl);
        $expectedFavicon  = $expectedBaseUrl . '/favicon.ico';

        $this->cacheMock
            ->expects(self::once())
            ->method('get')
            ->with($expectedCacheKey)
            ->willReturn($expectedFavicon);

        $this->cacheMock
            ->expects(self::never())
            ->method('set');

        self::assertSame($expectedFavicon, $this->instance->get($url));
    }

    public function dodgyUrlsDataProvider(): array
    {
        return [
            'only path'      => ['asdasd'],
            'invalid scheme' => ['s3://foo.com'],
            'no host'        => ['http://'],
            'empty url'      => [''],
        ];
    }

    public function goodUrlsDataProvider(): array
    {
        return [
            'simple url'              => [
                'url'      => 'http://domain.tld',
                'base_url' => 'http://domain.tld',
            ],
            'simple https url'        => [
                'url'      => 'https://domain.tld',
                'base_url' => 'https://domain.tld',
            ],
            'url with trailing slash' => [
                'url'      => 'http://domain.tld/',
                'base_url' => 'http://domain.tld',
            ],
            'url with port'           => [
                'url'      => 'http://domain.tld:8080',
                'base_url' => 'http://domain.tld:8080',
            ],
            'user without password'   => [
                'url'      => 'http://user@domain.tld',
                'base_url' => 'http://user@domain.tld',
            ],
            'user password'           => [
                'url'      => 'http://user:password@domain.tld',
                'base_url' => 'http://user:password@domain.tld',
            ],
            'url with unused info'    => [
                'url'      => 'http://domain.tld/index.php?foo=bar&bar=foo#foobar',
                'base_url' => 'http://domain.tld',
            ],
            'url with path'           => [
                'url'      => 'http://domain.tld/my/super/path',
                'base_url' => 'http://domain.tld',
            ],
        ];
    }

}
