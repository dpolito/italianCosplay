<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class EnvironmentConfigurationTest extends TestCase
{
    public function testEnvFileIsIgnoredByGit(): void
    {
        $gitignore = file_get_contents(APP_ROOT . '/.gitignore');

        self::assertIsString($gitignore);
        self::assertStringContainsString('.env', $gitignore);
        self::assertStringContainsString('/public_assets/uploads/', $gitignore);
        self::assertStringContainsString('/storage/logs/', $gitignore);
    }

    public function testTestingDatabaseNameMustLookLikeTestDatabaseWhenConfigured(): void
    {
        $databaseName = getenv('DB_NAME') ?: getenv('DB_DATABASE') ?: '';

        if ($databaseName === '') {
            self::markTestSkipped('No DB_NAME/DB_DATABASE configured for testing.');
        }

        self::assertMatchesRegularExpression('/(test|testing|_test)$/i', $databaseName);
    }
}
