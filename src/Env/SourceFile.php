<?php

declare(strict_types=1);

namespace EnvBuilder\Env;

final readonly class SourceFile
{
    public function __construct(
        public string $absolutePath,
        public string $relativePath,
        public bool $isDevOverlay
    ) {
    }
}

