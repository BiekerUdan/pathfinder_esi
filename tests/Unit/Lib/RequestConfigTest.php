<?php

namespace Exodus4D\ESI\Tests\Unit\Lib;

use Exodus4D\ESI\Lib\RequestConfig;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;

class RequestConfigTest extends TestCase
{
    public function testConstructorStoresRequestAndOptions(): void
    {
        $request = new Request('GET', '/test');
        $options = ['timeout' => 30];

        $config = new RequestConfig($request, $options);

        $this->assertInstanceOf(RequestConfig::class, $config);
    }

    public function testGetRequestReturnsRequest(): void
    {
        $request = new Request('GET', '/test');
        $config = new RequestConfig($request, []);

        $this->assertSame($request, $config->getRequest());
    }

    public function testGetOptionsReturnsOptions(): void
    {
        $request = new Request('GET', '/test');
        $options = ['timeout' => 30, 'headers' => ['X-Custom' => 'value']];

        $config = new RequestConfig($request, $options);

        $this->assertEquals($options, $config->getOptions());
    }

    public function testGetFormatterReturnsNullByDefault(): void
    {
        $request = new Request('GET', '/test');
        $config = new RequestConfig($request, []);

        $this->assertNull($config->getFormatter());
    }

    public function testGetFormatterReturnsCallable(): void
    {
        $request = new Request('GET', '/test');
        $formatter = function($body) {
            return ['formatted' => true];
        };

        $config = new RequestConfig($request, [], $formatter);

        $this->assertSame($formatter, $config->getFormatter());
        $this->assertIsCallable($config->getFormatter());
    }

    public function testFormatterCanBeExecuted(): void
    {
        $request = new Request('GET', '/test');
        $formatter = function($body) {
            return ['count' => count((array)$body)];
        };

        $config = new RequestConfig($request, [], $formatter);

        $body = (object)['a' => 1, 'b' => 2, 'c' => 3];
        $result = call_user_func($config->getFormatter(), $body);

        $this->assertEquals(['count' => 3], $result);
    }

    public function testConstructorWithComplexOptions(): void
    {
        $request = new Request('POST', '/api/test');
        $options = [
            'json' => ['foo' => 'bar'],
            'headers' => [
                'Authorization' => 'Bearer token',
                'Content-Type' => 'application/json'
            ],
            'timeout' => 60,
            'allow_redirects' => false
        ];

        $config = new RequestConfig($request, $options);

        $storedOptions = $config->getOptions();
        $this->assertArrayHasKey('json', $storedOptions);
        $this->assertArrayHasKey('headers', $storedOptions);
        $this->assertEquals(60, $storedOptions['timeout']);
        $this->assertFalse($storedOptions['allow_redirects']);
    }
}
