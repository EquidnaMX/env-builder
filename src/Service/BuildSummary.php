<?php

declare(strict_types=1);

namespace EnvBuilder\Service;

final readonly class BuildSummary
{
    public function __construct(
        public string $outputFile,
        public int $sourceCount,
        public int $variableCount,
        public ?string $deploymentTransport
    ) {
    }
}

