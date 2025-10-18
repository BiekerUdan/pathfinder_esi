<?php

namespace Exodus4D\ESI\Tests\Integration;

use Exodus4D\ESI\Client\EveScout\EveScout;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(EveScout::class)]
class EveScoutClientTest extends BaseIntegrationTest
{
    private EveScout $eveScoutClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->eveScoutClient = new EveScout('https://api.eve-scout.com');
    }

    public function testGetTheraConnectionsReturnsData(): void
    {
        $result = $this->eveScoutClient->send('getTheraConnections');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('connections', $result, 'Response should contain connections array');
    }

    public function testTheraConnectionsHaveValidStructure(): void
    {
        $result = $this->eveScoutClient->send('getTheraConnections');

        $this->assertIsArray($result);
        $this->assertNotEmpty($result['connections'], 'Should have at least one connection');

        // Get the first connection to validate structure
        $firstConnection = reset($result['connections']);

        // Required top-level fields
        $this->assertArrayHasKey('id', $firstConnection, 'Connection should have id');
        $this->assertArrayHasKey('type', $firstConnection, 'Connection should have type');
        $this->assertArrayHasKey('state', $firstConnection, 'Connection should have state');
        $this->assertArrayHasKey('eol', $firstConnection, 'Connection should have eol status');

        // Source system fields
        $this->assertArrayHasKey('source', $firstConnection, 'Connection should have source');
        $this->assertIsArray($firstConnection['source']);
        $this->assertArrayHasKey('id', $firstConnection['source'], 'Source should have id');
        $this->assertArrayHasKey('name', $firstConnection['source'], 'Source should have name');

        // Target system fields
        $this->assertArrayHasKey('target', $firstConnection, 'Connection should have target');
        $this->assertIsArray($firstConnection['target']);
        $this->assertArrayHasKey('id', $firstConnection['target'], 'Target should have id');
        $this->assertArrayHasKey('name', $firstConnection['target'], 'Target should have name');
        $this->assertArrayHasKey('region', $firstConnection['target'], 'Target should have region');

        // Target region fields
        $this->assertIsArray($firstConnection['target']['region']);
        $this->assertArrayHasKey('id', $firstConnection['target']['region'], 'Target region should have id');
        $this->assertArrayHasKey('name', $firstConnection['target']['region'], 'Target region should have name');

        // Signature fields
        $this->assertArrayHasKey('sourceSignature', $firstConnection, 'Connection should have sourceSignature');
        $this->assertArrayHasKey('targetSignature', $firstConnection, 'Connection should have targetSignature');
        $this->assertIsArray($firstConnection['sourceSignature']);
        $this->assertIsArray($firstConnection['targetSignature']);

        // Wormhole type should be set on one of the signatures
        $hasWormholeType =
            (isset($firstConnection['sourceSignature']['type']) && !empty($firstConnection['sourceSignature']['type'])) ||
            (isset($firstConnection['targetSignature']['type']) && !empty($firstConnection['targetSignature']['type']));
        $this->assertTrue($hasWormholeType, 'Either source or target signature should have wormhole type');

        // Timestamp fields
        $this->assertArrayHasKey('created', $firstConnection, 'Connection should have created timestamp');
        $this->assertArrayHasKey('updated', $firstConnection, 'Connection should have updated timestamp');

        // Character fields
        $this->assertArrayHasKey('character', $firstConnection, 'Connection should have character');
        $this->assertIsArray($firstConnection['character']);
    }

    public function testTheraConnectionsHaveValidDataTypes(): void
    {
        $result = $this->eveScoutClient->send('getTheraConnections');

        $this->assertNotEmpty($result['connections'], 'Should have at least one connection');

        foreach ($result['connections'] as $connectionId => $connection) {
            // Validate ID (may be int or string depending on JSON decoder)
            $this->assertTrue(
                is_int($connection['id']) || is_string($connection['id']),
                'Connection id should be integer or string'
            );
            // Convert to int for comparison
            $idValue = is_string($connection['id']) ? (int)$connection['id'] : $connection['id'];
            $this->assertEquals($connectionId, $idValue, 'Connection ID should match array key');

            // Validate type is string
            $this->assertIsString($connection['type'], 'Connection type should be string');
            $this->assertContains($connection['type'], ['wh', 'wormhole'], 'Connection type should be "wh" or "wormhole"');

            // Validate EOL status
            $this->assertIsString($connection['eol'], 'EOL status should be string');
            $this->assertContains($connection['eol'], ['critical', 'fresh'], 'EOL should be either "critical" or "fresh"');

            // Validate source system (can be Thera or Turnur)
            $this->assertIsInt($connection['source']['id'], 'Source system ID should be integer');
            $this->assertGreaterThan(0, $connection['source']['id'], 'Source system ID should be positive');
            $this->assertIsString($connection['source']['name'], 'Source system name should be string');
            $this->assertNotEmpty($connection['source']['name'], 'Source system name should not be empty');
            // Common sources are Thera (31000005) or Turnur (30002086)
            $this->assertContains($connection['source']['name'], ['Thera', 'Turnur'],
                'Source should be a known wormhole staging system');

            // Validate target system
            $this->assertIsInt($connection['target']['id'], 'Target system ID should be integer');
            $this->assertGreaterThan(0, $connection['target']['id'], 'Target system ID should be positive');
            $this->assertIsString($connection['target']['name'], 'Target system name should be string');
            $this->assertNotEmpty($connection['target']['name'], 'Target system name should not be empty');

            // Validate target region
            $this->assertIsInt($connection['target']['region']['id'], 'Target region ID should be integer');
            $this->assertGreaterThan(0, $connection['target']['region']['id'], 'Target region ID should be positive');
            $this->assertIsString($connection['target']['region']['name'], 'Target region name should be string');
            $this->assertNotEmpty($connection['target']['region']['name'], 'Target region name should not be empty');

            // Validate signature names
            if (isset($connection['sourceSignature']['name'])) {
                $this->assertIsString($connection['sourceSignature']['name'], 'Source signature name should be string');
                $this->assertMatchesRegularExpression('/^[A-Z]{3}-\d{3}$/', $connection['sourceSignature']['name'],
                    'Source signature should match format XXX-123');
            }
            if (isset($connection['targetSignature']['name'])) {
                $this->assertIsString($connection['targetSignature']['name'], 'Target signature name should be string');
                $this->assertMatchesRegularExpression('/^[A-Z]{3}-\d{3}$/', $connection['targetSignature']['name'],
                    'Target signature should match format XXX-123');
            }

            // Validate wormhole type (should be on one of the signatures)
            if (isset($connection['sourceSignature']['type'])) {
                $this->assertIsString($connection['sourceSignature']['type'], 'Source signature type should be string');
                $this->assertNotEmpty($connection['sourceSignature']['type'], 'Source signature type should not be empty');
            }
            if (isset($connection['targetSignature']['type'])) {
                $this->assertIsString($connection['targetSignature']['type'], 'Target signature type should be string');
                $this->assertNotEmpty($connection['targetSignature']['type'], 'Target signature type should not be empty');
            }

            // Test only first 3 connections to keep test fast
            if (array_search($connectionId, array_keys($result['connections'])) >= 2) {
                break;
            }
        }
    }

    public function testTheraConnectionsHaveValidTimestamps(): void
    {
        $result = $this->eveScoutClient->send('getTheraConnections');

        $this->assertNotEmpty($result['connections'], 'Should have at least one connection');

        $firstConnection = reset($result['connections']);

        // Validate created timestamp
        $this->assertIsString($firstConnection['created'], 'Created timestamp should be string');
        $this->assertNotEmpty($firstConnection['created'], 'Created timestamp should not be empty');

        // Validate updated timestamp
        $this->assertIsString($firstConnection['updated'], 'Updated timestamp should be string');
        $this->assertNotEmpty($firstConnection['updated'], 'Updated timestamp should not be empty');

        // Validate timestamps are valid ISO 8601 format (with milliseconds)
        $createdTime = \DateTime::createFromFormat('Y-m-d\TH:i:s.u\Z', $firstConnection['created']);
        $this->assertNotFalse($createdTime, 'Created timestamp should be valid ISO 8601 format');

        $updatedTime = \DateTime::createFromFormat('Y-m-d\TH:i:s.u\Z', $firstConnection['updated']);
        $this->assertNotFalse($updatedTime, 'Updated timestamp should be valid ISO 8601 format');

        // Updated should be >= created
        $this->assertGreaterThanOrEqual(
            $createdTime->getTimestamp(),
            $updatedTime->getTimestamp(),
            'Updated timestamp should be greater than or equal to created timestamp'
        );
    }

    public function testTheraConnectionsHaveValidWormholeData(): void
    {
        $result = $this->eveScoutClient->send('getTheraConnections');

        $this->assertNotEmpty($result['connections'], 'Should have at least one connection');

        $firstConnection = reset($result['connections']);

        // Check for wormhole-specific fields
        $this->assertArrayHasKey('wh_type', $firstConnection, 'Connection should have wh_type');
        $this->assertIsString($firstConnection['wh_type'], 'Wormhole type should be string');
        $this->assertNotEmpty($firstConnection['wh_type'], 'Wormhole type should not be empty');

        $this->assertArrayHasKey('remaining_hours', $firstConnection, 'Connection should have remaining_hours');
        $this->assertIsInt($firstConnection['remaining_hours'], 'Remaining hours should be integer');
        $this->assertGreaterThanOrEqual(0, $firstConnection['remaining_hours'], 'Remaining hours should be non-negative');
        $this->assertLessThanOrEqual(24, $firstConnection['remaining_hours'], 'Remaining hours should be <= 24 (typical WH lifetime)');

        $this->assertArrayHasKey('expires_at', $firstConnection, 'Connection should have expires_at');
        $this->assertIsString($firstConnection['expires_at'], 'Expires timestamp should be string');
        $this->assertNotEmpty($firstConnection['expires_at'], 'Expires timestamp should not be empty');

        // Validate EOL logic: critical if <= 4 hours remaining
        if ($firstConnection['remaining_hours'] <= 4) {
            $this->assertEquals('critical', $firstConnection['eol'], 'EOL should be "critical" when <= 4 hours remaining');
        } else {
            $this->assertEquals('fresh', $firstConnection['eol'], 'EOL should be "fresh" when > 4 hours remaining');
        }
    }

    public function testTheraConnectionsHaveValidState(): void
    {
        $result = $this->eveScoutClient->send('getTheraConnections');

        $this->assertNotEmpty($result['connections'], 'Should have at least one connection');

        foreach ($result['connections'] as $connection) {
            $this->assertArrayHasKey('state', $connection, 'Connection should have state');
            $this->assertIsArray($connection['state'], 'State should be array');
            $this->assertArrayHasKey('name', $connection['state'], 'State should have name');
            // State name is returned from API (may be string or int)
            $this->assertNotEmpty($connection['state']['name'], 'State name should not be empty');

            // Test only first connection to keep test fast
            break;
        }
    }

    public function testTheraConnectionsHaveCharacterInfo(): void
    {
        $result = $this->eveScoutClient->send('getTheraConnections');

        $this->assertNotEmpty($result['connections'], 'Should have at least one connection');

        $firstConnection = reset($result['connections']);

        $this->assertArrayHasKey('character', $firstConnection, 'Connection should have character');
        $this->assertIsArray($firstConnection['character'], 'Character should be array');
        $this->assertArrayHasKey('id', $firstConnection['character'], 'Character should have id');
        $this->assertArrayHasKey('name', $firstConnection['character'], 'Character should have name');

        $this->assertIsInt($firstConnection['character']['id'], 'Character ID should be integer');
        $this->assertGreaterThan(0, $firstConnection['character']['id'], 'Character ID should be positive');

        $this->assertIsString($firstConnection['character']['name'], 'Character name should be string');
        $this->assertNotEmpty($firstConnection['character']['name'], 'Character name should not be empty');
    }

    public function testMultipleConnectionsAreReturned(): void
    {
        $result = $this->eveScoutClient->send('getTheraConnections');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('connections', $result);

        // Thera typically has many active connections
        $connectionCount = count($result['connections']);
        $this->assertGreaterThan(1, $connectionCount, 'Should have multiple connections');
        $this->assertLessThan(500, $connectionCount, 'Connection count should be reasonable (< 500)');

        echo "\n[INFO] Found {$connectionCount} active Thera connections\n";
    }

    public function testConnectionsHaveUniqueIds(): void
    {
        $result = $this->eveScoutClient->send('getTheraConnections');

        $this->assertNotEmpty($result['connections'], 'Should have at least one connection');

        $connectionIds = array_keys($result['connections']);
        $uniqueIds = array_unique($connectionIds);

        $this->assertCount(
            count($connectionIds),
            $uniqueIds,
            'All connection IDs should be unique'
        );
    }

    public function testResponseTimeIsReasonable(): void
    {
        $startTime = microtime(true);

        $result = $this->eveScoutClient->send('getTheraConnections');

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $this->assertIsArray($result);
        $this->assertLessThan(10.0, $duration, 'API response should be received within 10 seconds');

        echo "\n[INFO] API response time: " . number_format($duration, 3) . " seconds\n";
    }

    public function testTheraConnectionsShowVarietyOfRegions(): void
    {
        $result = $this->eveScoutClient->send('getTheraConnections');

        $this->assertNotEmpty($result['connections'], 'Should have at least one connection');

        $regions = [];
        foreach ($result['connections'] as $connection) {
            $regionId = $connection['target']['region']['id'];
            $regionName = $connection['target']['region']['name'];
            $regions[$regionId] = $regionName;
        }

        $regionCount = count($regions);
        $this->assertGreaterThan(1, $regionCount, 'Connections should span multiple regions');

        echo "\n[INFO] Connections span {$regionCount} different regions\n";
        echo "[INFO] Sample regions: " . implode(', ', array_slice($regions, 0, 5)) . "\n";
    }

    public function testTheraConnectionsIncludeSignatureInformation(): void
    {
        $result = $this->eveScoutClient->send('getTheraConnections');

        $this->assertNotEmpty($result['connections'], 'Should have at least one connection');

        $signaturesWithNames = 0;
        $signaturesWithTypes = 0;

        foreach ($result['connections'] as $connection) {
            if (!empty($connection['sourceSignature']['name']) || !empty($connection['targetSignature']['name'])) {
                $signaturesWithNames++;
            }
            if (!empty($connection['sourceSignature']['type']) || !empty($connection['targetSignature']['type'])) {
                $signaturesWithTypes++;
            }
        }

        $this->assertGreaterThan(0, $signaturesWithNames, 'At least some connections should have signature names');
        $this->assertGreaterThan(0, $signaturesWithTypes, 'At least some connections should have signature types');

        echo "\n[INFO] {$signaturesWithNames} connections have signature names\n";
        echo "[INFO] {$signaturesWithTypes} connections have signature types\n";
    }
}
