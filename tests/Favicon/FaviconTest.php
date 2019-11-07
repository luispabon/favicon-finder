<?php

namespace Favicon;

use Favicon\Exception\UrlException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
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

    /**
     * @test
     * @dataProvider goodUrlsDataProvider
     */
    public function successDefaultFaviconNoCache(string $url, string $expectedBaseUrl, int $statusCode): void
    {
        $expectedCacheKey = md5($expectedBaseUrl);
        $expectedFavicon  = $expectedBaseUrl . '/favicon.ico';

        $this->cacheMock
            ->expects(self::once())
            ->method('get')
            ->with($expectedCacheKey)
            ->willReturn(null);

        $this->guzzleMock
            ->expects(self::once())
            ->method('request')
            ->with('HEAD', $expectedFavicon)
            ->willReturn(new Response($statusCode));

        $this->cacheMock
            ->expects(self::once())
            ->method('set')
            ->with($expectedCacheKey, $expectedFavicon, self::TTL);


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
                'url'            => 'http://domain.tld',
                'base_url'       => 'http://domain.tld',
                'success_status' => 200,
            ],
            'simple https url'        => [
                'url'            => 'https://domain.tld',
                'base_url'       => 'https://domain.tld',
                'success_status' => 201, // You never know what crappy servers might be out there
            ],
            'url with trailing slash' => [
                'url'            => 'http://domain.tld/',
                'base_url'       => 'http://domain.tld',
                'success_status' => 202,
            ],
            'url with port'           => [
                'url'            => 'http://domain.tld:8080',
                'base_url'       => 'http://domain.tld:8080',
                'success_status' => 203,
            ],
            'user without password'   => [
                'url'            => 'http://user@domain.tld',
                'base_url'       => 'http://user@domain.tld',
                'success_status' => 204,
            ],
            'user password'           => [
                'url'            => 'http://user:password@domain.tld',
                'base_url'       => 'http://user:password@domain.tld',
                'success_status' => 205,
            ],
            'url with unused info'    => [
                'url'            => 'http://domain.tld/index.php?foo=bar&bar=foo#foobar',
                'base_url'       => 'http://domain.tld',
                'success_status' => 206,
            ],
            'url with path'           => [
                'url'            => 'http://domain.tld/my/super/path',
                'base_url'       => 'http://domain.tld',
                'success_status' => 299,
            ],
        ];
    }
}
