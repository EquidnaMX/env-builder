<?php

declare(strict_types=1);

namespace EnvBuilder\Tests;

use EnvBuilder\Service\BuildService;
use PHPUnit\Framework\TestCase;

final class BuildServiceCompatibilityTest extends TestCase
{
    public function testLegacyIncludeDevCallStillOverridesBaseValues(): void
    {
        $directory = sys_get_temp_dir() . '/env-builder-compat-' . bin2hex(random_bytes(6));
        mkdir($directory, 0775, true);
        file_put_contents($directory . '/app.env', "APP_DEBUG=false\n");
        file_put_contents($directory . '/app.env.dev', "APP_DEBUG=true\n");
        $output = $directory . '/compiled';

        try {
            $summary = (new BuildService())->build($directory, $output, true);

            self::assertSame(2, $summary->sourceCount);
            self::assertStringContainsString('APP_DEBUG=true', (string) file_get_contents($output));
            self::assertStringNotContainsString('APP_DEBUG=false', (string) file_get_contents($output));
        } finally {
            foreach (glob($directory . '/*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($directory);
        }
    }
}
