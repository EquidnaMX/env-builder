<?php

declare(strict_types=1);

namespace EnvBuilder\Deploy;

final readonly class DeploymentResult
{
    public function __construct(
        public string $transport,
        public string $output
    ) {
    }
}

