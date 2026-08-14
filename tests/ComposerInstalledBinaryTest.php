<?php

declare(strict_types=1);

namespace EnvBuilder\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class ComposerInstalledBinaryTest extends TestCase
{
    public function testBinaryUsesComposerInjectedAutoloader(): void
    {
        $root = dirname(__DIR__);
        $fixture = sys_get_temp_dir() . '/env-builder-composer-binary-' . bin2hex(random_bytes(8));
        $binaryDirectory = $fixture . '/vendor/equidna/env-builder/bin';

        self::assertTrue(mkdir($binaryDirectory, 0775, true));
        self::assertTrue(copy($root . '/bin/env-builder', $binaryDirectory . '/env-builder'));

        $command = sprintf(
            '$GLOBALS["_composer_autoload_path"] = %s; $GLOBALS["argv"] = ["env-builder", "--version"]; require %s;',
            var_export($root . '/vendor/autoload.php', true),
            var_export($binaryDirectory . '/env-builder', true),
        );
        $process = new Process([PHP_BINARY, '-r', $command]);

        try {
            $process->mustRun();
            self::assertStringContainsString('env-builder', $process->getOutput());
        } finally {
            $this->removeDirectory($fixture);
        }
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($path);
    }
}
