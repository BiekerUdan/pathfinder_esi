<?php

namespace Exodus4D\ESI\Tests\Integration;

use Exodus4D\ESI\Client\GitHub\GitHub;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(GitHub::class)]
class GitHubClientTest extends BaseIntegrationTest
{
    private GitHub $githubClient;
    private string $testProject = 'exodus4d/pathfinder';

    protected function setUp(): void
    {
        parent::setUp();
        $this->githubClient = new GitHub('https://api.github.com');
    }

    public function testGetProjectReleasesReturnsData(): void
    {
        $result = $this->githubClient->send('getProjectReleases', $this->testProject, 5);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result, 'Should return at least one release');
    }

    public function testGetSingleRelease(): void
    {
        $result = $this->githubClient->send('getProjectReleases', $this->testProject, 1);

        $this->assertIsArray($result);
        $this->assertCount(1, $result, 'Should return exactly one release');
    }

    public function testGetMultipleReleases(): void
    {
        $count = 3;
        $result = $this->githubClient->send('getProjectReleases', $this->testProject, $count);

        $this->assertIsArray($result);
        $this->assertCount($count, $result, "Should return exactly {$count} releases");
    }

    public function testReleaseHasValidStructure(): void
    {
        $result = $this->githubClient->send('getProjectReleases', $this->testProject, 1);

        $this->assertNotEmpty($result);
        $release = $result[0];

        // Required fields
        $this->assertArrayHasKey('id', $release, 'Release should have id');
        $this->assertArrayHasKey('name', $release, 'Release should have name');
        $this->assertArrayHasKey('prerelease', $release, 'Release should have prerelease flag');
        $this->assertArrayHasKey('publishedAt', $release, 'Release should have publishedAt timestamp');
        $this->assertArrayHasKey('url', $release, 'Release should have url');
        $this->assertArrayHasKey('urlTarBall', $release, 'Release should have urlTarBall');
        $this->assertArrayHasKey('urlZipBall', $release, 'Release should have urlZipBall');
        $this->assertArrayHasKey('body', $release, 'Release should have body (release notes)');
    }

    public function testReleaseHasValidDataTypes(): void
    {
        $result = $this->githubClient->send('getProjectReleases', $this->testProject, 1);

        $this->assertNotEmpty($result);
        $release = $result[0];

        // Validate data types
        $this->assertIsInt($release['id'], 'Release ID should be integer');
        $this->assertGreaterThan(0, $release['id'], 'Release ID should be positive');

        $this->assertIsString($release['name'], 'Release name should be string');
        $this->assertNotEmpty($release['name'], 'Release name should not be empty');

        $this->assertIsBool($release['prerelease'], 'Prerelease flag should be boolean');

        $this->assertIsString($release['publishedAt'], 'Published timestamp should be string');
        $this->assertNotEmpty($release['publishedAt'], 'Published timestamp should not be empty');

        $this->assertIsString($release['url'], 'URL should be string');
        $this->assertStringStartsWith('https://github.com/', $release['url'], 'URL should be GitHub URL');

        $this->assertIsString($release['urlTarBall'], 'Tarball URL should be string');
        $this->assertStringStartsWith('https://api.github.com/', $release['urlTarBall'], 'Tarball URL should be GitHub API URL');

        $this->assertIsString($release['urlZipBall'], 'Zipball URL should be string');
        $this->assertStringStartsWith('https://api.github.com/', $release['urlZipBall'], 'Zipball URL should be GitHub API URL');

        $this->assertIsString($release['body'], 'Release body should be string');
    }

    public function testReleaseTimestampIsValid(): void
    {
        $result = $this->githubClient->send('getProjectReleases', $this->testProject, 1);

        $this->assertNotEmpty($result);
        $release = $result[0];

        // Validate timestamp format (GitHub uses ISO 8601)
        $timestamp = \DateTime::createFromFormat(\DateTime::ATOM, $release['publishedAt']);
        $this->assertNotFalse($timestamp, 'Published timestamp should be valid ISO 8601 format');

        // Timestamp should be in the past
        $now = new \DateTime();
        $this->assertLessThan($now->getTimestamp(), $timestamp->getTimestamp(),
            'Published timestamp should be in the past');
    }

    public function testReleasesAreOrderedByDate(): void
    {
        $result = $this->githubClient->send('getProjectReleases', $this->testProject, 5);

        $this->assertGreaterThanOrEqual(2, count($result), 'Need at least 2 releases to test ordering');

        $previousTimestamp = null;
        foreach ($result as $release) {
            $timestamp = \DateTime::createFromFormat(\DateTime::ATOM, $release['publishedAt']);
            $this->assertNotFalse($timestamp);

            if ($previousTimestamp !== null) {
                $this->assertLessThanOrEqual(
                    $previousTimestamp->getTimestamp(),
                    $timestamp->getTimestamp(),
                    'Releases should be ordered by date (newest first)'
                );
            }

            $previousTimestamp = $timestamp;
        }
    }

    public function testReleaseNameMatchesPathfinderPattern(): void
    {
        $result = $this->githubClient->send('getProjectReleases', $this->testProject, 1);

        $this->assertNotEmpty($result);
        $release = $result[0];

        // Pathfinder releases typically follow vX.Y.Z pattern
        $this->assertMatchesRegularExpression(
            '/^v?\d+\.\d+\.\d+/',
            $release['name'],
            'Release name should follow version pattern (e.g., v2.1.0)'
        );

        echo "\n[INFO] Latest release: {$release['name']}\n";
        echo "[INFO] Published: {$release['publishedAt']}\n";
    }

    public function testReleaseUrlsAreAccessible(): void
    {
        $result = $this->githubClient->send('getProjectReleases', $this->testProject, 1);

        $this->assertNotEmpty($result);
        $release = $result[0];

        // Test HTML URL format
        $this->assertStringContainsString($this->testProject, $release['url'],
            'HTML URL should contain project name');
        $this->assertStringContainsString('/releases/', $release['url'],
            'HTML URL should contain /releases/ path');

        // Test Tarball URL format
        $this->assertStringContainsString('/tarball/', $release['urlTarBall'],
            'Tarball URL should contain /tarball/ path');

        // Test Zipball URL format
        $this->assertStringContainsString('/zipball/', $release['urlZipBall'],
            'Zipball URL should contain /zipball/ path');
    }

    public function testReleaseBodyContainsMarkdown(): void
    {
        $result = $this->githubClient->send('getProjectReleases', $this->testProject, 1);

        $this->assertNotEmpty($result);
        $release = $result[0];

        // Body should contain typical markdown elements
        $body = $release['body'];
        $hasMarkdownElements = (
            str_contains($body, '#') ||      // Headers
            str_contains($body, '*') ||      // Lists or emphasis
            str_contains($body, '-') ||      // Lists
            str_contains($body, '[') ||      // Links
            str_contains($body, '`') ||      // Code
            str_contains($body, 'http')      // URLs
        );

        $this->assertTrue($hasMarkdownElements, 'Release body should contain markdown elements');
    }

    public function testMarkdownToHtmlBasicConversion(): void
    {
        $markdown = "# Test Header\n\nThis is **bold** text.";
        $result = $this->githubClient->send('markdownToHtml', $this->testProject, $markdown);

        $this->assertIsString($result);
        $this->assertNotEmpty($result, 'HTML output should not be empty');

        // Should contain HTML tags (GitHub adds dir="auto" attribute)
        $this->assertStringContainsString('<h1', $result, 'Should convert # to <h1>');
        $this->assertStringContainsString('Test Header', $result, 'Should contain header text');
        $this->assertStringContainsString('<strong>bold</strong>', $result, 'Should convert **bold** to <strong>');
    }

    public function testMarkdownToHtmlWithLists(): void
    {
        $markdown = "- Item 1\n- Item 2\n- Item 3";
        $result = $this->githubClient->send('markdownToHtml', $this->testProject, $markdown);

        $this->assertIsString($result);
        $this->assertStringContainsString('<ul', $result, 'Should create unordered list');
        $this->assertStringContainsString('<li>Item 1</li>', $result, 'Should convert list items');
        $this->assertStringContainsString('</ul>', $result, 'Should close unordered list');
    }

    public function testMarkdownToHtmlWithCode(): void
    {
        $markdown = "Code example:\n\n```php\n\$var = 'test';\n```";
        $result = $this->githubClient->send('markdownToHtml', $this->testProject, $markdown);

        $this->assertIsString($result);
        // GitHub wraps code in <div class="highlight"> instead of just <code>
        $this->assertStringContainsString('highlight', $result, 'Should convert code blocks with highlighting');
        $this->assertStringContainsString('test', $result, 'Should contain code content');
    }

    public function testMarkdownToHtmlWithLinks(): void
    {
        $markdown = "Check out [GitHub](https://github.com)";
        $result = $this->githubClient->send('markdownToHtml', $this->testProject, $markdown);

        $this->assertIsString($result);
        $this->assertStringContainsString('<a href', $result, 'Should convert links to <a> tags');
        $this->assertStringContainsString('https://github.com', $result, 'Should preserve URL');
        $this->assertStringContainsString('GitHub', $result, 'Should preserve link text');
    }

    public function testMarkdownToHtmlWithEmptyString(): void
    {
        $markdown = "";
        $result = $this->githubClient->send('markdownToHtml', $this->testProject, $markdown);

        $this->assertIsString($result);
        $this->assertEmpty($result, 'Empty markdown should return empty HTML');
    }

    public function testMarkdownToHtmlWithComplexContent(): void
    {
        $markdown = <<<MARKDOWN
# Release Notes v2.1.0

## Features
- Added new feature A
- Improved feature B

## Bug Fixes
- Fixed issue #123
- Resolved bug with **API calls**

## Code Example
```javascript
console.log('Hello World');
```

For more info, visit [documentation](https://example.com).
MARKDOWN;

        $result = $this->githubClient->send('markdownToHtml', $this->testProject, $markdown);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);

        // Validate various elements were converted (GitHub adds attributes like dir="auto")
        $this->assertStringContainsString('<h1', $result, 'Should convert H1 headers');
        $this->assertStringContainsString('<h2', $result, 'Should convert H2 headers');
        $this->assertStringContainsString('<ul', $result, 'Should convert lists');
        $this->assertStringContainsString('highlight', $result, 'Should convert code blocks');
        $this->assertStringContainsString('<a href', $result, 'Should convert links');
        $this->assertStringContainsString('<strong>API calls</strong>', $result, 'Should convert bold text');
    }

    public function testResponseTimeIsReasonable(): void
    {
        $startTime = microtime(true);

        $result = $this->githubClient->send('getProjectReleases', $this->testProject, 1);

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $this->assertIsArray($result);
        $this->assertLessThan(10.0, $duration, 'API response should be received within 10 seconds');

        echo "\n[INFO] GitHub API response time: " . number_format($duration, 3) . " seconds\n";
    }

    public function testMarkdownConversionResponseTime(): void
    {
        $markdown = "# Test\n\nThis is a test of markdown conversion performance.";

        $startTime = microtime(true);

        $result = $this->githubClient->send('markdownToHtml', $this->testProject, $markdown);

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $this->assertIsString($result);
        $this->assertLessThan(10.0, $duration, 'Markdown conversion should complete within 10 seconds');

        echo "\n[INFO] Markdown conversion time: " . number_format($duration, 3) . " seconds\n";
    }

    public function testPreleasesFlagVariety(): void
    {
        $result = $this->githubClient->send('getProjectReleases', $this->testProject, 10);

        $this->assertNotEmpty($result);

        $stableReleases = array_filter($result, fn($r) => !$r['prerelease']);
        $prereleases = array_filter($result, fn($r) => $r['prerelease']);

        // Log the breakdown
        echo "\n[INFO] Found " . count($stableReleases) . " stable releases\n";
        echo "[INFO] Found " . count($prereleases) . " pre-releases\n";

        // At least one should be a stable release
        $this->assertNotEmpty($stableReleases, 'Should have at least one stable release');
    }
}
