<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class DeployScriptTest extends TestCase
{
    public function testDeployScriptRequiresConfirmationBeforeProductionChanges(): void
    {
        $script = file_get_contents(APP_ROOT . '/scripts/deploy.sh');

        self::assertIsString($script);
        self::assertStringContainsString('Pubblicare queste modifiche su ItalianCosplay.it? [y/N]', $script);
        self::assertStringContainsString('Deploy cancelled.', $script);
    }

    public function testDeployScriptRunsTestsBeforePushAndUpload(): void
    {
        $script = file_get_contents(APP_ROOT . '/scripts/deploy.sh');

        self::assertIsString($script);
        self::assertLessThan(
            strpos($script, 'commit_and_push_if_needed'),
            strpos($script, 'run_tests')
        );
        self::assertLessThan(
            strpos($script, 'upload_files'),
            strpos($script, 'confirm_production')
        );
    }

    public function testDeployScriptExcludesSensitiveAndRuntimePaths(): void
    {
        $script = file_get_contents(APP_ROOT . '/scripts/deploy.sh');

        self::assertIsString($script);
        self::assertStringContainsString(':(exclude).env', $script);
        self::assertStringContainsString(':(exclude)public_assets/uploads', $script);
        self::assertStringContainsString(':(exclude)storage/logs', $script);
    }
}
