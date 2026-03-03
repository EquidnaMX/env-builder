<?php

declare(strict_types=1);

namespace EnvBuilder\Env;

final readonly class EnvEntry
{
    public function __construct(
        public string $key,
        public string $value,
        public int $line
    ) {
    }
}

