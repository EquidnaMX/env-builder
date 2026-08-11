<?php

declare(strict_types=1);

namespace EnvBuilder\Tests;

use EnvBuilder\Env\SourceFileResolver;
use EnvBuilder\Exception\EnvBuilderException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SourceFileResolverTest extends TestCase
{
    private string $sourceDir;

    protected function setUp(): void
    {
        $this->sourceDir = sys_get_temp_dir() . '/env-builder-unit-' . bin2hex(random_bytes(6));
        mkdir($this->sourceDir, 0775, true);

        foreach ([
            'aaa.env' => "A=base\n",
            'app.env' => "APP=base\n",
            'app.env.dev' => "APP=dev\n",
            'app.env.staging' => "APP=staging\n",
            'database.env' => "DB=base\n",
            'database.env.dev' => "DB=dev\n",
            'database.env.staging' => "DB=staging\n",
            'orphan.env.dev' => "DEV_ONLY=1\n",
            'orphan.env.staging' => "STAGING_ONLY=1\n",
        ] as $name => $content) {
            file_put_contents($this->sourceDir . '/' . $name, $content);
        }
    }

    protected function tearDown(): void
    {
        foreach (glob($this->sourceDir . '/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->sourceDir);
    }

    /** @return iterable<string, array{bool, bool, list<string>}> */
    public static function environments(): iterable
    {
        yield 'base' => [false, false, ['app.env', 'aaa.env', 'database.env']];
        yield 'development' => [true, false, [
            'app.env', 'app.env.dev', 'aaa.env', 'database.env', 'database.env.dev', 'orphan.env.dev',
        ]];
        yield 'staging' => [false, true, [
            'app.env', 'app.env.staging', 'aaa.env', 'database.env', 'database.env.staging',
            'orphan.env.staging',
        ]];
    }

    #[DataProvider('environments')]
    public function testItSelectsAndOrdersTheRequestedEnvironment(
        bool $includeDev,
        bool $includeStaging,
        array $expected
    ): void {
        $files = (new SourceFileResolver())->resolve($this->sourceDir, $includeDev, $includeStaging);

        self::assertSame($expected, array_map(static fn ($file): string => $file->relativePath, $files));
    }

    public function testItRejectsDevelopmentAndStagingTogether(): void
    {
        $this->expectException(EnvBuilderException::class);
        $this->expectExceptionMessage('cannot be enabled together');

        (new SourceFileResolver())->resolve($this->sourceDir, true, true);
    }
}
