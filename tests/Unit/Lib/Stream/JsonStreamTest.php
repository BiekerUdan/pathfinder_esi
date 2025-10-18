<?php

namespace Exodus4D\ESI\Tests\Unit\Lib\Stream;

use Exodus4D\ESI\Lib\Stream\JsonStream;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JsonStream::class)]
class JsonStreamTest extends TestCase
{
    public function testGetDecodedContentsReturnsDecodedJson(): void
    {
        $data = ['foo' => 'bar', 'baz' => 123];
        $json = json_encode($data);
        $stream = Utils::streamFor($json);

        $jsonStream = new JsonStream($stream);
        $decoded = $jsonStream->getDecodedContents();

        $this->assertIsObject($decoded);
        $this->assertEquals('bar', $decoded->foo);
        $this->assertEquals(123, $decoded->baz);
    }

    public function testGetDecodedContentsReturnsNullForEmptyString(): void
    {
        $stream = Utils::streamFor('');
        $jsonStream = new JsonStream($stream);

        $decoded = $jsonStream->getDecodedContents();

        $this->assertNull($decoded);
    }

    public function testGetDecodedContentsThrowsExceptionForInvalidJson(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/(Error trying to decode response|json_decode error)/');

        $stream = Utils::streamFor('invalid json {');
        $jsonStream = new JsonStream($stream);

        $jsonStream->getDecodedContents();
    }

    public function testGetDecodedContentsHandlesArray(): void
    {
        $data = [1, 2, 3, 4, 5];
        $json = json_encode($data);
        $stream = Utils::streamFor($json);

        $jsonStream = new JsonStream($stream);
        $decoded = $jsonStream->getDecodedContents();

        $this->assertIsArray($decoded);
        $this->assertCount(5, $decoded);
        $this->assertEquals([1, 2, 3, 4, 5], $decoded);
    }

    public function testGetDecodedContentsHandlesNestedStructures(): void
    {
        $data = [
            'user' => [
                'name' => 'Test User',
                'age' => 30,
                'roles' => ['admin', 'user']
            ]
        ];
        $json = json_encode($data);
        $stream = Utils::streamFor($json);

        $jsonStream = new JsonStream($stream);
        $decoded = $jsonStream->getDecodedContents();

        $this->assertIsObject($decoded);
        $this->assertEquals('Test User', $decoded->user->name);
        $this->assertEquals(30, $decoded->user->age);
        $this->assertIsArray($decoded->user->roles);
        $this->assertContains('admin', $decoded->user->roles);
    }

    public function testImplementsStreamInterface(): void
    {
        $stream = Utils::streamFor('{"test": true}');
        $jsonStream = new JsonStream($stream);

        // Should have all stream methods available
        $this->assertTrue($jsonStream->isReadable());
        $this->assertTrue($jsonStream->isSeekable());
    }
}
