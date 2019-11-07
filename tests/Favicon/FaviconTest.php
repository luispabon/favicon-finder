<?php

namespace Favicon;

use Favicon\Exception\UrlException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

class FaviconTest extends TestCase
{
    private const TTL             = 984343;
    private const FIXTURES_FOLDER = __DIR__ . '/fixtures';

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

    /**
     * @test
     * @dataProvider htmlSuccessFixturesDataProvider
     */
    public function successInPageIconAfterNotFound(
        string $url,
        string $expectedBaseUrl,
        string $html,
        string $expectedFavicon
    ): void {
        $expectedCacheKey       = md5($expectedBaseUrl);
        $expectedDefaultFavicon = $expectedBaseUrl . '/favicon.ico';

        $this->cacheMock
            ->expects(self::once())
            ->method('get')
            ->with($expectedCacheKey)
            ->willReturn(null);

        $this->guzzleMock
            ->expects(self::at(0))
            ->method('request')
            ->with('HEAD', $expectedDefaultFavicon)
            ->willReturn(new Response(404));

        $this->guzzleMock
            ->expects(self::at(1))
            ->method('request')
            ->with('GET', $expectedBaseUrl)
            ->willReturn(new Response(200, [], $html));

        $this->cacheMock
            ->expects(self::once())
            ->method('set')
            ->with($expectedCacheKey, $expectedFavicon, self::TTL);

        self::assertSame($expectedFavicon, $this->instance->get($url));
    }

    /**
     * @test
     * @dataProvider htmlSuccessFixturesDataProvider
     */
    public function successInPageIconAfterDefaultIconException(
        string $url,
        string $expectedBaseUrl,
        string $html,
        string $expectedFavicon
    ): void {
        $expectedCacheKey       = md5($expectedBaseUrl);
        $expectedDefaultFavicon = $expectedBaseUrl . '/favicon.ico';

        $exception = $this->getMockBuilder(ClientException::class)
                          ->disableOriginalConstructor()
                          ->getMock();

        $this->cacheMock
            ->expects(self::once())
            ->method('get')
            ->with($expectedCacheKey)
            ->willReturn(null);

        $this->guzzleMock
            ->expects(self::at(0))
            ->method('request')
            ->with('HEAD', $expectedDefaultFavicon)
            ->willThrowException($exception);

        $this->guzzleMock
            ->expects(self::at(1))
            ->method('request')
            ->with('GET', $expectedBaseUrl)
            ->willReturn(new Response(200, [], $html));

        $this->cacheMock
            ->expects(self::once())
            ->method('set')
            ->with($expectedCacheKey, $expectedFavicon, self::TTL);

        self::assertSame($expectedFavicon, $this->instance->get($url));
    }

    /**
     * @test
     * @dataProvider htmlFailureFixturesDataProvider
     */
    public function pageHasNoIcons(
        string $url,
        string $expectedBaseUrl,
        string $html
    ): void {
        $expectedCacheKey       = md5($expectedBaseUrl);
        $expectedDefaultFavicon = $expectedBaseUrl . '/favicon.ico';

        $exception = $this->getMockBuilder(ClientException::class)
                          ->disableOriginalConstructor()
                          ->getMock();

        $this->cacheMock
            ->expects(self::once())
            ->method('get')
            ->with($expectedCacheKey)
            ->willReturn(null);

        $this->guzzleMock
            ->expects(self::at(0))
            ->method('request')
            ->with('HEAD', $expectedDefaultFavicon)
            ->willThrowException($exception);

        $this->guzzleMock
            ->expects(self::at(1))
            ->method('request')
            ->with('GET', $expectedBaseUrl)
            ->willReturn(new Response(200, [], $html));

        $this->cacheMock
            ->expects(self::never())
            ->method('set');

        self::assertNull($this->instance->get($url));
    }

    /**
     * @test
     */
    public function exceptionThrownWhenFetchingPageIsHandled(): void
    {
        $url                    = 'http://foo';
        $expectedCacheKey       = md5($url);
        $expectedDefaultFavicon = $url . '/favicon.ico';

        $exception = $this->getMockBuilder(ClientException::class)
                          ->disableOriginalConstructor()
                          ->getMock();

        $this->cacheMock
            ->expects(self::once())
            ->method('get')
            ->with($expectedCacheKey)
            ->willReturn(null);

        $this->guzzleMock
            ->expects(self::at(0))
            ->method('request')
            ->with('HEAD', $expectedDefaultFavicon)
            ->willReturn(new Response(404));

        $this->guzzleMock
            ->expects(self::at(1))
            ->method('request')
            ->with('GET', $url)
            ->willThrowException($exception);

        $this->cacheMock
            ->expects(self::never())
            ->method('set');

        self::assertNull($this->instance->get($url));
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
                'base url'       => 'http://domain.tld',
                'success status' => 200,
            ],
            'simple https url'        => [
                'url'            => 'https://domain.tld',
                'base url'       => 'https://domain.tld',
                'success status' => 201, // You never know what crappy servers might be out there
            ],
            'url with trailing slash' => [
                'url'            => 'http://domain.tld/',
                'base url'       => 'http://domain.tld',
                'success status' => 202,
            ],
            'url with port'           => [
                'url'            => 'http://domain.tld:8080',
                'base url'       => 'http://domain.tld:8080',
                'success status' => 203,
            ],
            'user without password'   => [
                'url'            => 'http://user@domain.tld',
                'base url'       => 'http://user@domain.tld',
                'success status' => 204,
            ],
            'user password'           => [
                'url'            => 'http://user:password@domain.tld',
                'base url'       => 'http://user:password@domain.tld',
                'success status' => 205,
            ],
            'url with unused info'    => [
                'url'            => 'http://domain.tld/index.php?foo=bar&bar=foo#foobar',
                'base url'       => 'http://domain.tld',
                'success status' => 206,
            ],
            'url with path'           => [
                'url'            => 'http://domain.tld/my/super/path',
                'base url'       => 'http://domain.tld',
                'success status' => 299,
            ],
        ];
    }

    public function htmlSuccessFixturesDataProvider(): array
    {
        return [
            'rel icon'                         => [
                'url'           => 'https://foobar/',
                'base url'      => 'https://foobar',
                'html'          => file_get_contents(self::FIXTURES_FOLDER . '/rel_icon.html'),
                'expected icon' => 'https://foobar/d00d.png',
            ],
            'rel shortcut icon'                => [
                'url'           => 'https://bungo/foo/bar',
                'base url'      => 'https://bungo',
                'html'          => file_get_contents(self::FIXTURES_FOLDER . '/rel_shortcut_icon.html'),
                'expected icon' => 'https://bungo/AWESOME_FAVICON.png',
            ],
            'href favicon'                     => [
                'url'           => 'https://dodgy/foo?bar',
                'base url'      => 'https://dodgy',
                'html'          => file_get_contents(self::FIXTURES_FOLDER . '/href_favicon.html'),
                'expected icon' => 'https://dodgy/favicon_yeah.png',
            ],
            'protocol relative rel icon http'  => [
                'url'           => 'http://foobar/',
                'base url'      => 'http://foobar',
                'html'          => file_get_contents(self::FIXTURES_FOLDER . '/proto_rel_icon.html'),
                'expected icon' => 'http://foobar/foo/proto_rel.png',
            ],
            'protocol relative rel icon https' => [
                'url'           => 'https://foobar/',
                'base url'      => 'https://foobar',
                'html'          => file_get_contents(self::FIXTURES_FOLDER . '/proto_rel_icon.html'),
                'expected icon' => 'https://foobar/foo/proto_rel.png',
            ],
        ];
    }

    public function htmlFailureFixturesDataProvider(): array
    {
        return [
            'no icon'           => [
                'url'      => 'https://foobar/',
                'base url' => 'https://foobar',
                'html'     => file_get_contents(self::FIXTURES_FOLDER . '/no_icon.html'),
            ],
            'rel shortcut icon' => [
                'url'      => 'https://bungo/foo/bar',
                'base url' => 'https://bungo',
                'html'     => file_get_contents(self::FIXTURES_FOLDER . '/no_head.html'),
            ],
        ];
    }
}
