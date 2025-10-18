<?php

namespace Exodus4D\ESI\Tests\Unit\Lib\Middleware\Cache\Storage;

use Exodus4D\ESI\Lib\Middleware\Cache\CacheEntry;
use Exodus4D\ESI\Lib\Middleware\Cache\Storage\VolatileRuntimeStorage;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(VolatileRuntimeStorage::class)]
class VolatileRuntimeStorageTest extends TestCase
{
    private function createCacheEntry(): CacheEntry
    {
        $request = new Request('GET', 'http://example.com/test');
        $response = new Response(200, [], '{"test": true}');
        $staleAt = new \DateTime('+60 seconds');

        return new CacheEntry($request, $response, $staleAt);
    }

    public function testFetchReturnsNullForNonexistentKey(): void
    {
        $storage = new VolatileRuntimeStorage();

        $result = $storage->fetch('nonexistent-key');

        $this->assertNull($result);
    }

    public function testSaveStoresCacheEntry(): void
    {
        $storage = new VolatileRuntimeStorage();
        $entry = $this->createCacheEntry();

        $result = $storage->save('test-key', $entry);

        $this->assertTrue($result);
    }

    public function testFetchReturnsSavedEntry(): void
    {
        $storage = new VolatileRuntimeStorage();
        $entry = $this->createCacheEntry();

        $storage->save('test-key', $entry);
        $fetched = $storage->fetch('test-key');

        $this->assertInstanceOf(CacheEntry::class, $fetched);
        $this->assertSame($entry, $fetched);
    }

    public function testDeleteRemovesEntry(): void
    {
        $storage = new VolatileRuntimeStorage();
        $entry = $this->createCacheEntry();

        $storage->save('test-key', $entry);
        $result = $storage->delete('test-key');

        $this->assertTrue($result);
        $this->assertNull($storage->fetch('test-key'));
    }

    public function testDeleteReturnsFalseForNonexistentKey(): void
    {
        $storage = new VolatileRuntimeStorage();

        $result = $storage->delete('nonexistent-key');

        $this->assertFalse($result);
    }

    public function testMultipleEntriesCanBeStored(): void
    {
        $storage = new VolatileRuntimeStorage();
        $entry1 = $this->createCacheEntry();
        $entry2 = $this->createCacheEntry();

        $storage->save('key1', $entry1);
        $storage->save('key2', $entry2);

        $this->assertSame($entry1, $storage->fetch('key1'));
        $this->assertSame($entry2, $storage->fetch('key2'));
    }

    public function testOverwriteExistingEntry(): void
    {
        $storage = new VolatileRuntimeStorage();
        $entry1 = $this->createCacheEntry();
        $entry2 = $this->createCacheEntry();

        $storage->save('test-key', $entry1);
        $storage->save('test-key', $entry2);

        $fetched = $storage->fetch('test-key');
        $this->assertSame($entry2, $fetched);
        $this->assertNotSame($entry1, $fetched);
    }
}
