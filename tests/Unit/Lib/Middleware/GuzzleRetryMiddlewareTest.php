<?php

namespace Exodus4D\ESI\Tests\Unit\Lib\Middleware;

use Exodus4D\ESI\Lib\Middleware\GuzzleRetryMiddleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GuzzleRetryMiddleware::class)]
class GuzzleRetryMiddlewareTest extends TestCase
{
    public function testFactoryReturnsClosureWithDefaults(): void
    {
        $middleware = GuzzleRetryMiddleware::factory();

        $this->assertInstanceOf(\Closure::class, $middleware);
    }

    public function testFactoryReturnsClosureWithCustomOptions(): void
    {
        $middleware = GuzzleRetryMiddleware::factory([
            'retry_enabled' => false,
            'max_retry_attempts' => 5,
            'retry_on_status' => [500, 502, 503]
        ]);

        $this->assertInstanceOf(\Closure::class, $middleware);
    }

    public function testFactoryMergesDefaultsWithProvidedOptions(): void
    {
        $customOptions = [
            'max_retry_attempts' => 10,
            'default_retry_multiplier' => 1.5
        ];

        $middleware = GuzzleRetryMiddleware::factory($customOptions);

        $this->assertInstanceOf(\Closure::class, $middleware);
    }

    public function testFactoryWithLoggingDisabled(): void
    {
        $middleware = GuzzleRetryMiddleware::factory([
            'retry_log_error' => false
        ]);

        $this->assertInstanceOf(\Closure::class, $middleware);
    }

    public function testFactoryWithLoggingEnabled(): void
    {
        $logCalled = false;
        $logCallback = function() use (&$logCalled) {
            $logCalled = true;
        };

        $middleware = GuzzleRetryMiddleware::factory([
            'retry_log_error' => true,
            'retry_log_callback' => $logCallback
        ]);

        $this->assertInstanceOf(\Closure::class, $middleware);
    }

    public function testFactoryWithCustomRetryCallback(): void
    {
        $customCallback = function() {
            // Custom retry logic
        };

        $middleware = GuzzleRetryMiddleware::factory([
            'retry_log_error' => true,
            'on_retry_callback' => $customCallback
        ]);

        $this->assertInstanceOf(\Closure::class, $middleware);
    }

    public function testGetLogMessageFormatsWithRequest(): void
    {
        $request = new Request('GET', 'http://example.com/test');
        $response = new Response(503, [], 'Service Unavailable');

        $reflection = new \ReflectionClass(GuzzleRetryMiddleware::class);
        $method = $reflection->getMethod('getLogMessage');
        $method->setAccessible(true);

        $format = '[{attempt}/{maxRetry}] RETRY FAILED {method} {target} HTTP/{version} → {code} {phrase}';
        $result = $method->invoke(null, $format, $request, 3, 5, $response);

        $this->assertStringContainsString('[3/5]', $result);
        $this->assertStringContainsString('RETRY FAILED', $result);
        $this->assertStringContainsString('GET', $result);
        $this->assertStringContainsString('503', $result);
        $this->assertStringContainsString('Service Unavailable', $result);
    }

    public function testGetLogMessageFormatsWithoutResponse(): void
    {
        $request = new Request('POST', 'http://example.com/api/endpoint');

        $reflection = new \ReflectionClass(GuzzleRetryMiddleware::class);
        $method = $reflection->getMethod('getLogMessage');
        $method->setAccessible(true);

        $format = '[{attempt}/{maxRetry}] RETRY FAILED {method} {target} HTTP/{version} → {code} {phrase}';
        $result = $method->invoke(null, $format, $request, 2, 3, null);

        $this->assertStringContainsString('[2/3]', $result);
        $this->assertStringContainsString('POST', $result);
        $this->assertStringContainsString('NULL', $result);
    }

    public function testGetLogMessageReplacesAllPlaceholders(): void
    {
        $request = new Request('PUT', 'http://example.com/resource/123', [], null, '1.1');
        $response = new Response(429, [], 'Too Many Requests');

        $reflection = new \ReflectionClass(GuzzleRetryMiddleware::class);
        $method = $reflection->getMethod('getLogMessage');
        $method->setAccessible(true);

        $format = 'Attempt {attempt}/{maxRetry}: {method} {target} HTTP/{version} - Status: {code} {phrase}';
        $result = $method->invoke(null, $format, $request, 1, 2, $response);

        $this->assertStringContainsString('Attempt 1/2', $result);
        $this->assertStringContainsString('PUT', $result);
        $this->assertStringContainsString('/resource/123', $result);
        $this->assertStringContainsString('HTTP/1.1', $result);
        $this->assertStringContainsString('Status: 429', $result);
        $this->assertStringContainsString('Too Many Requests', $result);
    }

    public function testGetRetryCallbackIsCallableWhenLogEnabled(): void
    {
        $reflection = new \ReflectionClass(GuzzleRetryMiddleware::class);
        $method = $reflection->getMethod('getRetryCallback');
        $method->setAccessible(true);

        $callback = $method->invoke(null);

        $this->assertIsCallable($callback);
    }

    public function testGetRetryCallbackExecutesWithValidParameters(): void
    {
        $logCalled = false;
        $logData = null;
        $logMessage = null;

        $logCallback = function($file, $level, $message, $data) use (&$logCalled, &$logData, &$logMessage) {
            $logCalled = true;
            $logData = $data;
            $logMessage = $message;
        };

        $reflection = new \ReflectionClass(GuzzleRetryMiddleware::class);
        $method = $reflection->getMethod('getRetryCallback');
        $method->setAccessible(true);

        $callback = $method->invoke(null);

        $request = new Request('GET', 'http://example.com/test');
        $response = new Response(503);

        $options = [
            'retry_log_error' => true,
            'max_retry_attempts' => 2,
            'retry_loggable_callback' => null,
            'retry_log_callback' => $logCallback,
            'retry_log_file' => 'test_retry',
            'retry_log_format' => '[{attempt}/{maxRetry}] RETRY FAILED {method} {target}'
        ];

        // Simulate reaching max retry attempts
        $callback(2, 1.0, $request, $options, $response);

        $this->assertTrue($logCalled);
        $this->assertNotNull($logData);
        $this->assertArrayHasKey('url', $logData);
        $this->assertArrayHasKey('retryAttempt', $logData);
        $this->assertEquals(2, $logData['retryAttempt']);
    }

    public function testGetRetryCallbackDoesNotLogBeforeMaxAttempts(): void
    {
        $logCalled = false;

        $logCallback = function() use (&$logCalled) {
            $logCalled = true;
        };

        $reflection = new \ReflectionClass(GuzzleRetryMiddleware::class);
        $method = $reflection->getMethod('getRetryCallback');
        $method->setAccessible(true);

        $callback = $method->invoke(null);

        $request = new Request('GET', 'http://example.com/test');

        $options = [
            'retry_log_error' => true,
            'max_retry_attempts' => 5,
            'retry_log_callback' => $logCallback,
            'retry_log_file' => 'test',
            'retry_log_format' => 'test'
        ];

        // Attempt 3 out of 5 - should not log yet
        $callback(3, 1.0, $request, $options, null);

        $this->assertFalse($logCalled);
    }

    public function testGetRetryCallbackRespectsLoggableCallback(): void
    {
        $logCalled = false;

        $loggableCallback = function($request) {
            // Return false to prevent logging
            return false;
        };

        $logCallback = function() use (&$logCalled) {
            $logCalled = true;
        };

        $reflection = new \ReflectionClass(GuzzleRetryMiddleware::class);
        $method = $reflection->getMethod('getRetryCallback');
        $method->setAccessible(true);

        $callback = $method->invoke(null);

        $request = new Request('GET', 'http://example.com/test');

        $options = [
            'retry_log_error' => true,
            'max_retry_attempts' => 2,
            'retry_loggable_callback' => $loggableCallback,
            'retry_log_callback' => $logCallback,
            'retry_log_file' => 'test',
            'retry_log_format' => 'test'
        ];

        // Simulate reaching max attempts, but loggable callback returns false
        $callback(2, 1.0, $request, $options, null);

        $this->assertFalse($logCalled);
    }
}
