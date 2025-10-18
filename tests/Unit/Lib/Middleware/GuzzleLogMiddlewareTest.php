<?php

namespace Exodus4D\ESI\Tests\Unit\Lib\Middleware;

use Exodus4D\ESI\Lib\Middleware\GuzzleLogMiddleware;
use Exodus4D\ESI\Lib\Stream\JsonStream;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\RejectedPromise;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\TransferStats;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GuzzleLogMiddleware::class)]
class GuzzleLogMiddlewareTest extends TestCase
{
    private function createMockHandler($response = null)
    {
        $response = $response ?? new Response(200);
        return function() use ($response) {
            return new FulfilledPromise($response);
        };
    }

    private function createRejectedHandler($exception)
    {
        return function() use ($exception) {
            return new RejectedPromise($exception);
        };
    }

    public function testFactoryReturnsClosureThatCreatesMiddleware(): void
    {
        $factory = GuzzleLogMiddleware::factory();

        $this->assertInstanceOf(\Closure::class, $factory);

        $handler = $this->createMockHandler();
        $middleware = $factory($handler);

        $this->assertInstanceOf(GuzzleLogMiddleware::class, $middleware);
    }

    public function testFactoryWithCustomOptions(): void
    {
        $factory = GuzzleLogMiddleware::factory([
            'log_enabled' => false,
            'log_5xx' => false
        ]);

        $handler = $this->createMockHandler();
        $middleware = $factory($handler);

        $this->assertInstanceOf(GuzzleLogMiddleware::class, $middleware);
    }

    public function testConstructorSetsDefaults(): void
    {
        $handler = $this->createMockHandler();
        $middleware = new GuzzleLogMiddleware($handler);

        $this->assertInstanceOf(GuzzleLogMiddleware::class, $middleware);
    }

    public function testConstructorMergesCustomOptions(): void
    {
        $handler = $this->createMockHandler();
        $customOptions = ['log_enabled' => false];
        $middleware = new GuzzleLogMiddleware($handler, $customOptions);

        $this->assertInstanceOf(GuzzleLogMiddleware::class, $middleware);
    }

    public function testInvokeWhenDisabledSkipsLogging(): void
    {
        $handler = $this->createMockHandler();
        $middleware = new GuzzleLogMiddleware($handler, ['log_enabled' => false]);

        $request = new Request('GET', 'http://example.com/test');
        $promise = $middleware($request, []);

        $this->assertNotNull($promise);
    }

    public function testInvokeWithLoggableCallbackDisables(): void
    {
        $handler = $this->createMockHandler();
        $loggableCallback = function() { return false; };

        $middleware = new GuzzleLogMiddleware($handler, [
            'log_enabled' => true,
            'log_loggable_callback' => $loggableCallback
        ]);

        $request = new Request('GET', 'http://example.com/test');
        $promise = $middleware($request, []);

        $this->assertNotNull($promise);
    }

    public function testInvokeWithLoggableCallbackEnables(): void
    {
        $handler = $this->createMockHandler();
        $loggableCallback = function() { return true; };

        $middleware = new GuzzleLogMiddleware($handler, [
            'log_enabled' => true,
            'log_loggable_callback' => $loggableCallback
        ]);

        $request = new Request('GET', 'http://example.com/test');
        $promise = $middleware($request, []);

        $this->assertNotNull($promise);
    }

    public function testInvokeSetsUpStatsCallback(): void
    {
        $handler = $this->createMockHandler();
        $middleware = new GuzzleLogMiddleware($handler, [
            'log_enabled' => true,
            'log_stats' => true
        ]);

        $request = new Request('GET', 'http://example.com/test');
        $promise = $middleware($request, []);

        $this->assertNotNull($promise);
    }

    public function testOnFulfilledCallsLogWhenEnabled(): void
    {
        $logCalled = false;
        $logCallback = function() use (&$logCalled) {
            $logCalled = true;
        };

        $response = new Response(500);
        $handler = $this->createMockHandler($response);

        $middleware = new GuzzleLogMiddleware($handler, [
            'log_enabled' => true,
            'log_5xx' => true,
            'log_callback' => $logCallback
        ]);

        $request = new Request('GET', 'http://example.com/test');
        $promise = $middleware($request, []);
        $result = $promise->wait();

        $this->assertEquals(500, $result->getStatusCode());
        $this->assertTrue($logCalled);
    }

    public function testOnFulfilledSkipsLogWhenDisabled(): void
    {
        $logCalled = false;
        $logCallback = function() use (&$logCalled) {
            $logCalled = true;
        };

        $response = new Response(500);
        $handler = $this->createMockHandler($response);

        $middleware = new GuzzleLogMiddleware($handler, [
            'log_enabled' => false,
            'log_callback' => $logCallback
        ]);

        $request = new Request('GET', 'http://example.com/test');
        $promise = $middleware($request, []);
        $result = $promise->wait();

        $this->assertEquals(500, $result->getStatusCode());
        $this->assertFalse($logCalled);
    }

    public function testOnRejectedLogsException(): void
    {
        $logCalled = false;
        $logCallback = function() use (&$logCalled) {
            $logCalled = true;
        };

        $request = new Request('GET', 'http://example.com/test');
        $exception = new RequestException('Error', $request);
        $handler = $this->createRejectedHandler($exception);

        $middleware = new GuzzleLogMiddleware($handler, [
            'log_enabled' => true,
            'log_error' => true,
            'log_callback' => $logCallback
        ]);

        $promise = $middleware($request, []);

        try {
            $promise->wait();
        } catch (\Exception $e) {
            // Expected
        }

        $this->assertTrue($logCalled);
    }

    public function testOnRejectedWithRequestExceptionAndResponse(): void
    {
        $logCalled = false;
        $logCallback = function() use (&$logCalled) {
            $logCalled = true;
        };

        $request = new Request('GET', 'http://example.com/test');
        $response = new Response(500);
        $exception = new RequestException('Error', $request, $response);
        $handler = $this->createRejectedHandler($exception);

        $middleware = new GuzzleLogMiddleware($handler, [
            'log_enabled' => true,
            'log_error' => true,
            'log_callback' => $logCallback
        ]);

        $promise = $middleware($request, []);

        try {
            $promise->wait();
        } catch (\Exception $e) {
            // Expected
        }

        $this->assertTrue($logCalled);
    }

    public function testLogRequest(): void
    {
        $reflection = new \ReflectionClass(GuzzleLogMiddleware::class);
        $method = $reflection->getMethod('logRequest');
        $method->setAccessible(true);

        $handler = $this->createMockHandler();
        $middleware = new GuzzleLogMiddleware($handler);

        $request = new Request('GET', 'http://example.com/test/path');
        $result = $method->invoke($middleware, $request, ['log_request_headers' => false]);

        $this->assertArrayHasKey('method', $result);
        $this->assertEquals('GET', $result['method']);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('host', $result);
        $this->assertArrayHasKey('path', $result);
    }

    public function testLogRequestWithHeaders(): void
    {
        $reflection = new \ReflectionClass(GuzzleLogMiddleware::class);
        $method = $reflection->getMethod('logRequest');
        $method->setAccessible(true);

        $handler = $this->createMockHandler();
        $middleware = new GuzzleLogMiddleware($handler);

        $request = new Request('GET', 'http://example.com/test', ['X-Custom' => 'value']);
        $result = $method->invoke($middleware, $request, ['log_request_headers' => true]);

        $this->assertArrayHasKey('requestHeaders', $result);
    }

    public function testLogResponse(): void
    {
        $reflection = new \ReflectionClass(GuzzleLogMiddleware::class);
        $method = $reflection->getMethod('logResponse');
        $method->setAccessible(true);

        $handler = $this->createMockHandler();
        $middleware = new GuzzleLogMiddleware($handler);

        $response = new Response(200, ['Content-Length' => '100'], 'test');
        $result = $method->invoke($middleware, $response, ['log_response_headers' => false]);

        $this->assertArrayHasKey('code', $result);
        $this->assertEquals(200, $result['code']);
        $this->assertArrayHasKey('phrase', $result);
        $this->assertArrayHasKey('version', $result);
    }

    public function testLogResponseWithHeaders(): void
    {
        $reflection = new \ReflectionClass(GuzzleLogMiddleware::class);
        $method = $reflection->getMethod('logResponse');
        $method->setAccessible(true);

        $handler = $this->createMockHandler();
        $middleware = new GuzzleLogMiddleware($handler);

        $response = new Response(200, ['X-Custom' => 'value']);
        $result = $method->invoke($middleware, $response, ['log_response_headers' => true]);

        $this->assertArrayHasKey('responseHeaders', $result);
    }

    public function testLogReasonWithRequestException(): void
    {
        $reflection = new \ReflectionClass(GuzzleLogMiddleware::class);
        $method = $reflection->getMethod('logReason');
        $method->setAccessible(true);

        $handler = $this->createMockHandler();
        $middleware = new GuzzleLogMiddleware($handler);

        $request = new Request('GET', 'http://example.com/test');
        $exception = new RequestException('Error message', $request);

        $result = $method->invoke($middleware, $exception);

        $this->assertArrayHasKey('errno', $result);
        $this->assertArrayHasKey('error', $result);
    }

    public function testLogReasonWithGenericException(): void
    {
        $reflection = new \ReflectionClass(GuzzleLogMiddleware::class);
        $method = $reflection->getMethod('logReason');
        $method->setAccessible(true);

        $handler = $this->createMockHandler();
        $middleware = new GuzzleLogMiddleware($handler);

        $exception = new \Exception('Generic error');

        $result = $method->invoke($middleware, $exception);

        $this->assertArrayHasKey('errno', $result);
        $this->assertEquals('NULL', $result['errno']);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Generic error', $result['error']);
    }

    public function testLogCacheWithHeader(): void
    {
        $reflection = new \ReflectionClass(GuzzleLogMiddleware::class);
        $method = $reflection->getMethod('logCache');
        $method->setAccessible(true);

        $handler = $this->createMockHandler();
        $middleware = new GuzzleLogMiddleware($handler);

        $response = new Response(200, ['X-Guzzle-Cache' => 'HIT']);
        $result = $method->invoke($middleware, $response, ['log_cache_header' => 'X-Guzzle-Cache']);

        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('HIT', $result['status']);
    }

    public function testLogCacheWithoutHeader(): void
    {
        $reflection = new \ReflectionClass(GuzzleLogMiddleware::class);
        $method = $reflection->getMethod('logCache');
        $method->setAccessible(true);

        $handler = $this->createMockHandler();
        $middleware = new GuzzleLogMiddleware($handler);

        $response = new Response(200);
        $result = $method->invoke($middleware, $response, ['log_cache_header' => 'X-Guzzle-Cache']);

        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('NULL', $result['status']);
    }

    public function testGetErrorMessageFromJsonResponseBody(): void
    {
        $reflection = new \ReflectionClass(GuzzleLogMiddleware::class);
        $method = $reflection->getMethod('getErrorMessageFromResponseBody');
        $method->setAccessible(true);

        $handler = $this->createMockHandler();
        $middleware = new GuzzleLogMiddleware($handler);

        $response = new Response(400, ['Content-Type' => 'application/json'], '{"error":"Something went wrong"}');
        $result = $method->invoke($middleware, $response);

        $this->assertEquals('Something went wrong', $result);
    }

    public function testGetErrorMessageFromTextResponseBody(): void
    {
        $reflection = new \ReflectionClass(GuzzleLogMiddleware::class);
        $method = $reflection->getMethod('getErrorMessageFromResponseBody');
        $method->setAccessible(true);

        $handler = $this->createMockHandler();
        $middleware = new GuzzleLogMiddleware($handler);

        $response = new Response(400, ['Content-Type' => 'text/plain'], 'Error text');
        $result = $method->invoke($middleware, $response);

        $this->assertEquals('Error text', $result);
    }

    public function testCheckStatusCodeWithLogAllStatus(): void
    {
        $reflection = new \ReflectionClass(GuzzleLogMiddleware::class);
        $method = $reflection->getMethod('checkStatusCode');
        $method->setAccessible(true);

        $handler = $this->createMockHandler();
        $middleware = new GuzzleLogMiddleware($handler);

        $result = $method->invoke($middleware, ['log_all_status' => true], 200);
        $this->assertTrue($result);
    }

    public function testCheckStatusCodeWithLogOffStatus(): void
    {
        $reflection = new \ReflectionClass(GuzzleLogMiddleware::class);
        $method = $reflection->getMethod('checkStatusCode');
        $method->setAccessible(true);

        $handler = $this->createMockHandler();
        $middleware = new GuzzleLogMiddleware($handler);

        $result = $method->invoke($middleware, ['log_all_status' => false, 'log_off_status' => [404]], 404);
        $this->assertFalse($result);
    }

    public function testCheckStatusCodeWithLogOnStatus(): void
    {
        $reflection = new \ReflectionClass(GuzzleLogMiddleware::class);
        $method = $reflection->getMethod('checkStatusCode');
        $method->setAccessible(true);

        $handler = $this->createMockHandler();
        $middleware = new GuzzleLogMiddleware($handler);

        $result = $method->invoke($middleware, [
            'log_all_status' => false,
            'log_off_status' => [],
            'log_on_status' => [418],
            'log_4xx' => false
        ], 418);
        $this->assertTrue($result);
    }

    public function testCheckStatusCodeWith5xx(): void
    {
        $reflection = new \ReflectionClass(GuzzleLogMiddleware::class);
        $method = $reflection->getMethod('checkStatusCode');
        $method->setAccessible(true);

        $handler = $this->createMockHandler();
        $middleware = new GuzzleLogMiddleware($handler);

        $result = $method->invoke($middleware, [
            'log_all_status' => false,
            'log_off_status' => [],
            'log_on_status' => [],
            'log_5xx' => true
        ], 500);
        $this->assertTrue($result);
    }

    public function testIs2xx(): void
    {
        $reflection = new \ReflectionClass(GuzzleLogMiddleware::class);
        $method = $reflection->getMethod('is2xx');
        $method->setAccessible(true);

        $handler = $this->createMockHandler();
        $middleware = new GuzzleLogMiddleware($handler);

        $this->assertTrue($method->invoke($middleware, 200));
        $this->assertTrue($method->invoke($middleware, 204));
        $this->assertFalse($method->invoke($middleware, 404));
    }

    public function testIs4xx(): void
    {
        $reflection = new \ReflectionClass(GuzzleLogMiddleware::class);
        $method = $reflection->getMethod('is4xx');
        $method->setAccessible(true);

        $handler = $this->createMockHandler();
        $middleware = new GuzzleLogMiddleware($handler);

        $this->assertTrue($method->invoke($middleware, 404));
        $this->assertTrue($method->invoke($middleware, 400));
        $this->assertFalse($method->invoke($middleware, 200));
    }

    public function testIs5xx(): void
    {
        $reflection = new \ReflectionClass(GuzzleLogMiddleware::class);
        $method = $reflection->getMethod('is5xx');
        $method->setAccessible(true);

        $handler = $this->createMockHandler();
        $middleware = new GuzzleLogMiddleware($handler);

        $this->assertTrue($method->invoke($middleware, 500));
        $this->assertTrue($method->invoke($middleware, 503));
        $this->assertFalse($method->invoke($middleware, 200));
    }

    public function testGetLogMessage(): void
    {
        $reflection = new \ReflectionClass(GuzzleLogMiddleware::class);
        $method = $reflection->getMethod('getLogMessage');
        $method->setAccessible(true);

        $handler = $this->createMockHandler();
        $middleware = new GuzzleLogMiddleware($handler);

        $logData = [
            'request' => [
                'method' => 'GET',
                'url' => 'http://example.com',
                'host' => 'example.com',
                'path' => '/test',
                'target' => '/test',
                'version' => '1.1'
            ],
            'response' => [
                'code' => 200,
                'phrase' => 'OK',
                'res_header_content-length' => '100'
            ]
        ];

        $format = '{method} {target} HTTP/{version} → {code} {phrase} {res_header_content-length}';
        $result = $method->invoke($middleware, $format, $logData);

        $this->assertStringContainsString('GET', $result);
        $this->assertStringContainsString('/test', $result);
        $this->assertStringContainsString('200', $result);
        $this->assertStringContainsString('OK', $result);
    }

    public function testMergeOptions(): void
    {
        $reflection = new \ReflectionClass(GuzzleLogMiddleware::class);
        $method = $reflection->getMethod('mergeOptions');
        $method->setAccessible(true);

        $handler = $this->createMockHandler();
        $middleware = new GuzzleLogMiddleware($handler);

        $options = [
            'log_enabled' => true,
            'log_on_status' => [200],
            'log_off_status' => [404]
        ];

        $optionsNew = [
            'log_enabled' => false,
            'log_on_status' => [201],
            'log_off_status' => [500]
        ];

        $result = $method->invoke($middleware, $options, $optionsNew);

        $this->assertFalse($result['log_enabled']);
        $this->assertContains(200, $result['log_on_status']);
        $this->assertContains(201, $result['log_on_status']);
        $this->assertContains(404, $result['log_off_status']);
        $this->assertContains(500, $result['log_off_status']);
    }

    public function testLogWith2xxResponse(): void
    {
        $logData = null;
        $logCallback = function($action, $level, $message, $data, $tag) use (&$logData) {
            $logData = compact('action', 'level', 'message', 'data', 'tag');
        };

        $response = new Response(200);
        $handler = $this->createMockHandler($response);

        $middleware = new GuzzleLogMiddleware($handler, [
            'log_enabled' => true,
            'log_2xx' => true,
            'log_callback' => $logCallback
        ]);

        $request = new Request('GET', 'http://example.com/test');
        $promise = $middleware($request, []);
        $result = $promise->wait();

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertNotNull($logData);
        $this->assertEquals('info', $logData['level']);
        $this->assertEquals('success', $logData['tag']);
    }

    public function testLogWith4xxResponse(): void
    {
        $logData = null;
        $logCallback = function($action, $level, $message, $data, $tag) use (&$logData) {
            $logData = compact('action', 'level', 'message', 'data', 'tag');
        };

        $response = new Response(404);
        $handler = $this->createMockHandler($response);

        $middleware = new GuzzleLogMiddleware($handler, [
            'log_enabled' => true,
            'log_4xx' => true,
            'log_callback' => $logCallback
        ]);

        $request = new Request('GET', 'http://example.com/test');
        $promise = $middleware($request, []);
        $result = $promise->wait();

        $this->assertEquals(404, $result->getStatusCode());
        $this->assertNotNull($logData);
        $this->assertEquals('error', $logData['level']);
        $this->assertEquals('warning', $logData['tag']);
    }

    public function testLogWith5xxResponse(): void
    {
        $logData = null;
        $logCallback = function($action, $level, $message, $data, $tag) use (&$logData) {
            $logData = compact('action', 'level', 'message', 'data', 'tag');
        };

        $response = new Response(500);
        $handler = $this->createMockHandler($response);

        $middleware = new GuzzleLogMiddleware($handler, [
            'log_enabled' => true,
            'log_5xx' => true,
            'log_callback' => $logCallback
        ]);

        $request = new Request('GET', 'http://example.com/test');
        $promise = $middleware($request, []);
        $result = $promise->wait();

        $this->assertEquals(500, $result->getStatusCode());
        $this->assertNotNull($logData);
        $this->assertEquals('critical', $logData['level']);
        $this->assertEquals('warning', $logData['tag']);
    }

    public function testLogWithCacheInfo(): void
    {
        $logData = null;
        $logCallback = function($action, $level, $message, $data, $tag) use (&$logData) {
            $logData = compact('action', 'level', 'message', 'data', 'tag');
        };

        $response = new Response(200, ['X-Guzzle-Cache' => 'HIT']);
        $handler = $this->createMockHandler($response);

        $middleware = new GuzzleLogMiddleware($handler, [
            'log_enabled' => true,
            'log_2xx' => true,
            'log_cache' => true,
            'log_cache_header' => 'X-Guzzle-Cache',
            'log_callback' => $logCallback
        ]);

        $request = new Request('GET', 'http://example.com/test');
        $promise = $middleware($request, []);
        $result = $promise->wait();

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertNotNull($logData);
        $this->assertArrayHasKey('cache', $logData['data']);
        $this->assertEquals('HIT', $logData['data']['cache']['status']);
    }
}
