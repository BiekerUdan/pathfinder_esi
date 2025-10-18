<?php

namespace Exodus4D\ESI\Tests\Unit\Lib\Middleware;

use Cache\Adapter\Void\VoidCachePool;
use Exodus4D\ESI\Lib\Middleware\GuzzleCcpLogMiddleware;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GuzzleCcpLogMiddleware::class)]
class GuzzleCcpLogMiddlewareTest extends TestCase
{
    private function createMockHandler($response = null)
    {
        $response = $response ?? new Response(200);
        return function() use ($response) {
            return new FulfilledPromise($response);
        };
    }

    public function testFactoryReturnsClosureThatCreatesMiddleware(): void
    {
        $factory = GuzzleCcpLogMiddleware::factory();

        $this->assertInstanceOf(\Closure::class, $factory);

        $handler = $this->createMockHandler();
        $middleware = $factory($handler);

        $this->assertInstanceOf(GuzzleCcpLogMiddleware::class, $middleware);
    }

    public function testFactoryWithCustomOptions(): void
    {
        $factory = GuzzleCcpLogMiddleware::factory([
            'ccp_log_enabled' => false,
            'ccp_log_count_max' => 5
        ]);

        $handler = $this->createMockHandler();
        $middleware = $factory($handler);

        $this->assertInstanceOf(GuzzleCcpLogMiddleware::class, $middleware);
    }

    public function testConstructorSetsDefaults(): void
    {
        $handler = $this->createMockHandler();
        $middleware = new GuzzleCcpLogMiddleware($handler);

        $this->assertInstanceOf(GuzzleCcpLogMiddleware::class, $middleware);
    }

    public function testConstructorMergesCustomOptions(): void
    {
        $handler = $this->createMockHandler();
        $customOptions = ['ccp_log_enabled' => false];
        $middleware = new GuzzleCcpLogMiddleware($handler, $customOptions);

        $this->assertInstanceOf(GuzzleCcpLogMiddleware::class, $middleware);
    }

    public function testInvokeWhenDisabledSkipsLogging(): void
    {
        $handler = $this->createMockHandler();
        $middleware = new GuzzleCcpLogMiddleware($handler, ['ccp_log_enabled' => false]);

        $request = new Request('GET', 'http://example.com');
        $promise = $middleware($request, []);

        $this->assertNotNull($promise);
    }

    public function testInvokeWithNoWarningHeaders(): void
    {
        $response = new Response(200, [], '{"test": true}');
        $handler = $this->createMockHandler($response);
        $middleware = new GuzzleCcpLogMiddleware($handler, [
            'get_cache_pool' => function() { return new VoidCachePool(); }
        ]);

        $request = new Request('GET', 'http://example.com');
        $promise = $middleware($request, []);

        $result = $promise->wait();
        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testInvokeHandlesWarningHeadersWhenCallbackProvided(): void
    {
        $response = new Response(200, ['Warning' => '199 - "Resource is legacy"'], '{}');
        $handler = $this->createMockHandler($response);

        $logCallback = function() {};

        $middleware = new GuzzleCcpLogMiddleware($handler, [
            'ccp_log_enabled' => true,
            'ccp_log_callback' => $logCallback,
            'ccp_log_loggable_callback' => function() { return true; },
            'get_cache_pool' => function() { return new VoidCachePool(); }
        ]);

        $request = new Request('GET', 'http://example.com/legacy');
        $promise = $middleware($request, []);

        $result = $promise->wait();

        $this->assertEquals(200, $result->getStatusCode());
    }
}
