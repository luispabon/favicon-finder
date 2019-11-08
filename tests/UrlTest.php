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
}
