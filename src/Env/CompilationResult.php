<?php

declare(strict_types=1);

namespace EnvBuilder\Env;

final readonly class CompilationResult
{
    /**
     * @param list<string> $processedSources
     * @param array<string, string> $resolvedVariables
     */
    public function __construct(
        public string $content,
        public array $processedSources,
        public array $resolvedVariables
    ) {
    }
}

