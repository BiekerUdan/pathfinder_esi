<?php

namespace Exodus4D\ESI\Tests\Unit\Lib;

use Exodus4D\ESI\Lib\Stream\JsonStream;
use Exodus4D\ESI\Lib\WebClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(WebClient::class)]
class WebClientTest extends TestCase
{
    public function testNewRequestCreatesRequestObject(): void
    {
        $request = WebClient::newRequest('GET', '/test');

        $this->assertInstanceOf(Request::class, $request);
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/test', (string)$request->getUri());
    }

    public function testNewResponseCreatesResponseObject(): void
    {
        $response = WebClient::newResponse(200, ['Content-Type' => 'application/json'], '{"test": true}');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertEquals('{"test": true}', (string)$response->getBody());
    }

    public function testNewResponseWithCustomReasonPhrase(): void
    {
        $response = WebClient::newResponse(200, [], null, '1.1', 'Custom Reason');

        $this->assertEquals('Custom Reason', $response->getReasonPhrase());
    }

    public function testNewErrorResponseWithConnectException(): void
    {
        $request = new Request('GET', 'http://example.com');
        $exception = new ConnectException('Connection failed', $request);

        $response = WebClient::newErrorResponse($exception);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Error Response', $response->getReasonPhrase());

        $body = $response->getBody();
        $this->assertInstanceOf(JsonStream::class, $body);

        $content = $body->getDecodedContents();
        $this->assertIsObject($content);
        $this->assertObjectHasProperty('error', $content);
        $this->assertStringContainsString('ConnectException', $content->error);
        $this->assertStringContainsString('Connection failed', $content->error);
    }

    public function testNewErrorResponseWithClientException(): void
    {
        $request = new Request('GET', 'http://example.com');
        $response = new Response(404);
        $exception = new ClientException('Not found', $request, $response);

        $errorResponse = WebClient::newErrorResponse($exception);

        $body = $errorResponse->getBody();
        $content = $body->getDecodedContents();

        $this->assertStringContainsString('ClientException', $content->error);
        $this->assertStringContainsString('HTTP 404', $content->error);
    }

    public function testNewErrorResponseWithServerException(): void
    {
        $request = new Request('GET', 'http://example.com');
        $response = new Response(500);
        $exception = new ServerException('Internal server error', $request, $response);

        $errorResponse = WebClient::newErrorResponse($exception);

        $body = $errorResponse->getBody();
        $content = $body->getDecodedContents();

        $this->assertStringContainsString('ServerException', $content->error);
        $this->assertStringContainsString('HTTP 500', $content->error);
    }

    public function testNewErrorResponseWithRequestException(): void
    {
        $request = new Request('GET', 'http://example.com');
        $exception = new RequestException('Timeout', $request);

        $errorResponse = WebClient::newErrorResponse($exception);

        $body = $errorResponse->getBody();
        $content = $body->getDecodedContents();

        $this->assertStringContainsString('RequestException', $content->error);
        $this->assertStringContainsString('Timeout', $content->error);
    }

    public function testNewErrorResponseWithGenericException(): void
    {
        $exception = new \Exception('Generic error');

        $errorResponse = WebClient::newErrorResponse($exception);

        $body = $errorResponse->getBody();
        $content = $body->getDecodedContents();

        $this->assertStringContainsString('Exception', $content->error);
        $this->assertStringContainsString('Generic error', $content->error);
    }

    public function testNewErrorResponseWithNonJsonFlag(): void
    {
        $exception = new \Exception('Test error');

        $errorResponse = WebClient::newErrorResponse($exception, false);

        $body = $errorResponse->getBody();
        // Should not be JsonStream
        $this->assertNotInstanceOf(JsonStream::class, $body);
        $this->assertStringContainsString('Test error', (string)$body);
    }

    public function testConstructorCreatesClientWithBaseUri(): void
    {
        $client = new WebClient('https://api.example.com');

        // Test that __call works for proxying to the underlying client
        $this->assertNotNull($client);
    }

    public function testConstructorWithCustomConfig(): void
    {
        $config = ['timeout' => 30];
        $client = new WebClient('https://api.example.com', $config);

        $this->assertNotNull($client);
    }

    public function testConstructorWithInitStackCallback(): void
    {
        $stackModified = false;
        $initStack = function(HandlerStack $stack) use (&$stackModified) {
            $stackModified = true;
        };

        $client = new WebClient('https://api.example.com', [], $initStack);

        $this->assertTrue($stackModified);
    }

    public function testNewPoolCreatesPool(): void
    {
        $client = new WebClient('https://api.example.com');
        $requests = [
            new Request('GET', '/test1'),
            new Request('GET', '/test2')
        ];

        $pool = $client->newPool($requests);

        $this->assertInstanceOf(Pool::class, $pool);
    }

    public function testRunBatchExecutesRequests(): void
    {
        $client = new WebClient('https://httpbin.org');
        $requests = [
            new Request('GET', '/status/200')
        ];

        $results = $client->runBatch($requests);

        $this->assertIsArray($results);
        $this->assertCount(1, $results);
    }

    public function testCallProxiesToClient(): void
    {
        $client = new WebClient('https://httpbin.org');

        // Test that __call proxies to the underlying Guzzle client
        // We'll use 'getConfig' which is a valid Guzzle Client method
        $config = $client->getConfig();

        $this->assertIsArray($config);
        $this->assertEquals('https://httpbin.org', $config['base_uri']);
    }

    public function testCallReturnsEmptyArrayForNonexistentMethod(): void
    {
        $client = new WebClient('https://api.example.com');

        $result = $client->nonExistentMethod();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
