<?php

declare(strict_types=1);

namespace EnvBuilder\Console;

use EnvBuilder\Console\Command\BuildCommand;
use Symfony\Component\Console\Application as SymfonyApplication;

final class Application extends SymfonyApplication
{
    public function __construct()
    {
        parent::__construct('env-builder', '1.1.0');

        $this->add(new BuildCommand());
    }
}
