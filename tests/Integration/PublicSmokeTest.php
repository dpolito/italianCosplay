<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

final class PublicSmokeTest extends TestCase
{
    public function testConfiguredPublicPagesReturnHtmlWithoutPhpFatalMarkers(): void
    {
        $baseUrl = getenv('IC_TEST_BASE_URL') ?: '';

        if ($baseUrl === '') {
            self::markTestSkipped('Set IC_TEST_BASE_URL to run HTTP smoke tests against local Docker or a test server.');
        }

        $paths = ['/', '/eventi-cosplay', '/blog', '/login', '/register'];

        foreach ($paths as $path) {
            $body = @file_get_contents(rtrim($baseUrl, '/') . $path);

            self::assertNotFalse($body, "Failed to request {$path}");
            self::assertNotSame('', trim((string) $body), "Empty response for {$path}");
            self::assertStringNotContainsString('Fatal error', (string) $body);
            self::assertStringNotContainsString('Uncaught', (string) $body);
            self::assertStringContainsString('<html', strtolower((string) $body), "Missing HTML marker for {$path}");
        }
    }
}
