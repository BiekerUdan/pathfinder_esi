<?php

namespace Exodus4D\ESI\Tests\Unit\Lib\Middleware\Cache;

use Exodus4D\ESI\Lib\Middleware\Cache\CacheEntry;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CacheEntry::class)]
class CacheEntryTest extends TestCase
{
    private function createRequest(): Request
    {
        return new Request('GET', 'http://example.com/test');
    }

    private function createResponse(array $headers = [], string $body = '{"test": true}'): Response
    {
        return new Response(200, $headers, $body);
    }

    public function testConstructorCreatesEntry(): void
    {
        $request = $this->createRequest();
        $response = $this->createResponse();
        $staleAt = new \DateTime('+60 seconds');

        $entry = new CacheEntry($request, $response, $staleAt);

        $this->assertInstanceOf(CacheEntry::class, $entry);
    }

    public function testGetResponseReturnsResponseWithAgeHeader(): void
    {
        $request = $this->createRequest();
        $response = $this->createResponse();
        $staleAt = new \DateTime('+60 seconds');

        $entry = new CacheEntry($request, $response, $staleAt);
        $returnedResponse = $entry->getResponse();

        $this->assertTrue($returnedResponse->hasHeader('Age'));
        $this->assertGreaterThanOrEqual(0, (int)$returnedResponse->getHeaderLine('Age'));
    }

    public function testGetOriginalResponseReturnsUnmodifiedResponse(): void
    {
        $request = $this->createRequest();
        $response = $this->createResponse();
        $staleAt = new \DateTime('+60 seconds');

        $entry = new CacheEntry($request, $response, $staleAt);
        $originalResponse = $entry->getOriginalResponse();

        $this->assertSame($response, $originalResponse);
        $this->assertFalse($originalResponse->hasHeader('Age'));
    }

    public function testGetOriginalRequestReturnsRequest(): void
    {
        $request = $this->createRequest();
        $response = $this->createResponse();
        $staleAt = new \DateTime('+60 seconds');

        $entry = new CacheEntry($request, $response, $staleAt);

        $this->assertSame($request, $entry->getOriginalRequest());
    }

    public function testIsFreshWhenNotStale(): void
    {
        $request = $this->createRequest();
        $response = $this->createResponse();
        $staleAt = new \DateTime('+60 seconds');

        $entry = new CacheEntry($request, $response, $staleAt);

        $this->assertTrue($entry->isFresh());
        $this->assertFalse($entry->isStale());
    }

    public function testIsStaleWhenPastStaleDate(): void
    {
        $request = $this->createRequest();
        $response = $this->createResponse();
        $staleAt = new \DateTime('-1 second');

        $entry = new CacheEntry($request, $response, $staleAt);

        $this->assertTrue($entry->isStale());
        $this->assertFalse($entry->isFresh());
    }

    public function testGetStaleAgeReturnsZeroWhenFresh(): void
    {
        $request = $this->createRequest();
        $response = $this->createResponse();
        $staleAt = new \DateTime('+60 seconds');

        $entry = new CacheEntry($request, $response, $staleAt);

        $this->assertLessThanOrEqual(0, $entry->getStaleAge());
    }

    public function testGetStaleAgeReturnsPositiveWhenStale(): void
    {
        $request = $this->createRequest();
        $response = $this->createResponse();
        $staleAt = new \DateTime('-10 seconds');

        $entry = new CacheEntry($request, $response, $staleAt);

        $this->assertGreaterThan(0, $entry->getStaleAge());
        $this->assertGreaterThanOrEqual(10, $entry->getStaleAge());
    }

    public function testHasValidationInformationWithEtag(): void
    {
        $request = $this->createRequest();
        $response = $this->createResponse(['Etag' => '"abc123"']);
        $staleAt = new \DateTime('+60 seconds');

        $entry = new CacheEntry($request, $response, $staleAt);

        $this->assertTrue($entry->hasValidationInformation());
    }

    public function testHasValidationInformationWithLastModified(): void
    {
        $request = $this->createRequest();
        $response = $this->createResponse(['Last-Modified' => 'Wed, 21 Oct 2015 07:28:00 GMT']);
        $staleAt = new \DateTime('+60 seconds');

        $entry = new CacheEntry($request, $response, $staleAt);

        $this->assertTrue($entry->hasValidationInformation());
    }

    public function testHasValidationInformationReturnsFalseWithoutHeaders(): void
    {
        $request = $this->createRequest();
        $response = $this->createResponse();
        $staleAt = new \DateTime('+60 seconds');

        $entry = new CacheEntry($request, $response, $staleAt);

        $this->assertFalse($entry->hasValidationInformation());
    }

    public function testGetVaryHeadersReturnsEmptyArrayWhenNoVary(): void
    {
        $request = $this->createRequest();
        $response = $this->createResponse();
        $staleAt = new \DateTime('+60 seconds');

        $entry = new CacheEntry($request, $response, $staleAt);

        $this->assertEmpty($entry->getVaryHeaders());
    }

    public function testGetVaryHeadersReturnsArrayOfHeaders(): void
    {
        $request = $this->createRequest();
        $response = $this->createResponse(['Vary' => 'Accept, Accept-Encoding']);
        $staleAt = new \DateTime('+60 seconds');

        $entry = new CacheEntry($request, $response, $staleAt);

        $varyHeaders = $entry->getVaryHeaders();
        $this->assertIsArray($varyHeaders);
        $this->assertNotEmpty($varyHeaders);
        // The parseHeader function parses the Vary header and flattens it
        // The exact format depends on the parser implementation
        $this->assertGreaterThan(0, count($varyHeaders));
    }

    public function testIsVaryEqualsReturnsTrueForSameRequest(): void
    {
        $request = $this->createRequest();
        $response = $this->createResponse(['Vary' => 'Accept']);
        $staleAt = new \DateTime('+60 seconds');

        $entry = new CacheEntry($request, $response, $staleAt);

        $this->assertTrue($entry->isVaryEquals($request));
    }

    public function testGetAgeReturnsPositiveInteger(): void
    {
        $request = $this->createRequest();
        $response = $this->createResponse();
        $staleAt = new \DateTime('+60 seconds');

        $entry = new CacheEntry($request, $response, $staleAt);

        usleep(100000); // Sleep 100ms

        $this->assertGreaterThanOrEqual(0, $entry->getAge());
    }

    public function testStaleIfErrorParsedFromCacheControl(): void
    {
        $request = $this->createRequest();
        $response = $this->createResponse(['Cache-Control' => 'max-age=60, stale-if-error=300']);
        $staleAt = new \DateTime('+60 seconds');

        $entry = new CacheEntry($request, $response, $staleAt);

        // Entry should allow serving stale content on error for 300 seconds after stale
        $this->assertTrue($entry->serveStaleIfError());
    }

    public function testStaleWhileRevalidateParsedFromCacheControl(): void
    {
        $request = $this->createRequest();
        $response = $this->createResponse(['Cache-Control' => 'max-age=60, stale-while-revalidate=300']);
        $staleAt = new \DateTime('+60 seconds');

        $entry = new CacheEntry($request, $response, $staleAt);

        // Entry should allow serving stale content while revalidating for 300 seconds after stale
        $this->assertTrue($entry->staleWhileValidate());
    }

    public function testSerializationPreservesData(): void
    {
        $request = $this->createRequest();
        $response = $this->createResponse([], '{"foo": "bar"}');
        $staleAt = new \DateTime('+60 seconds');

        $entry = new CacheEntry($request, $response, $staleAt);

        $serialized = serialize($entry);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(CacheEntry::class, $unserialized);
        $this->assertEquals('{"foo": "bar"}', (string)$unserialized->getOriginalResponse()->getBody());
    }

    public function testGetStaleAtReturnsDateTime(): void
    {
        $request = $this->createRequest();
        $response = $this->createResponse();
        $staleAt = new \DateTime('+60 seconds');

        $entry = new CacheEntry($request, $response, $staleAt);

        $this->assertInstanceOf(\DateTime::class, $entry->getStaleAt());
        $this->assertEquals($staleAt->getTimestamp(), $entry->getStaleAt()->getTimestamp());
    }

    public function testGetTTLWithValidationInformationReturnsZero(): void
    {
        $request = $this->createRequest();
        $response = $this->createResponse(['Etag' => '"abc123"']);
        $staleAt = new \DateTime('+60 seconds');

        $entry = new CacheEntry($request, $response, $staleAt);

        // Should return 0 (infinite) when validation information is present
        $this->assertEquals(0, $entry->getTTL());
    }

    public function testGetTTLWithStaleIfErrorReturnsCorrectValue(): void
    {
        $request = $this->createRequest();
        $response = $this->createResponse();
        $staleAt = new \DateTime('+60 seconds');
        $staleIfErrorTo = new \DateTime('+120 seconds');

        $entry = new CacheEntry($request, $response, $staleAt, $staleIfErrorTo);

        $ttl = $entry->getTTL();
        // TTL should be approximately 120 seconds (to staleIfErrorTo)
        $this->assertGreaterThan(100, $ttl);
        $this->assertLessThanOrEqual(121, $ttl);
    }

    public function testGetTTLWithoutStaleIfErrorReturnsStaleTime(): void
    {
        $request = $this->createRequest();
        $response = $this->createResponse();
        $staleAt = new \DateTime('+60 seconds');

        $entry = new CacheEntry($request, $response, $staleAt);

        $ttl = $entry->getTTL();
        // TTL should be approximately 60 seconds (to staleAt)
        $this->assertGreaterThan(50, $ttl);
        $this->assertLessThanOrEqual(61, $ttl);
    }

    public function testIsVaryEqualsWithAbsentHeaders(): void
    {
        $request1 = new Request('GET', 'http://example.com/test');
        $response = $this->createResponse(['Vary' => 'Accept']);
        $staleAt = new \DateTime('+60 seconds');

        $entry = new CacheEntry($request1, $response, $staleAt);

        $request2 = new Request('GET', 'http://example.com/test');

        // Should return true when both requests don't have the Vary header
        $this->assertTrue($entry->isVaryEquals($request2));
    }

    public function testIsVaryEqualsWithMatchingHeaders(): void
    {
        $request1 = new Request('GET', 'http://example.com/test', ['Accept' => 'application/json']);
        $response = $this->createResponse(['Vary' => 'Accept']);
        $staleAt = new \DateTime('+60 seconds');

        $entry = new CacheEntry($request1, $response, $staleAt);

        $request2 = new Request('GET', 'http://example.com/test', ['Accept' => 'application/json']);

        // Should return true because Accept headers match
        $this->assertTrue($entry->isVaryEquals($request2));
    }

    public function testServeStaleIfErrorReturnsFalseWhenNotSet(): void
    {
        $request = $this->createRequest();
        $response = $this->createResponse();
        $staleAt = new \DateTime('-10 seconds');

        $entry = new CacheEntry($request, $response, $staleAt);

        $this->assertFalse($entry->serveStaleIfError());
    }

    public function testStaleWhileValidateReturnsFalseWhenNotSet(): void
    {
        $request = $this->createRequest();
        $response = $this->createResponse();
        $staleAt = new \DateTime('-10 seconds');

        $entry = new CacheEntry($request, $response, $staleAt);

        $this->assertFalse($entry->staleWhileValidate());
    }
}
