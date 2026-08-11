<?php

declare(strict_types=1);

namespace EnvBuilder\Tests;

use EnvBuilder\Console\Command\BuildCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class BuildCommandTest extends TestCase
{
    public function testItRejectsDevelopmentAndStagingFlagsTogether(): void
    {
        $tester = new CommandTester(new BuildCommand());

        $status = $tester->execute(['--dev' => true, '--staging' => true]);

        self::assertSame(1, $status);
        self::assertStringContainsString('cannot be enabled together', $tester->getDisplay());
    }
}
