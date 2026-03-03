<?php

declare(strict_types=1);

namespace EnvBuilder\Env;

final class EnvCompiler
{
    public function __construct(
        private readonly SourceFileResolver $resolver = new SourceFileResolver(),
        private readonly EnvFileParser $parser = new EnvFileParser()
    ) {
    }

    public function compile(string $sourceDir, bool $includeDev): CompilationResult
    {
        $sourceFiles = $this->resolver->resolve($sourceDir, $includeDev);
        $processedSources = [];
        $resolvedVariables = [];

        $lines = [
            '# Compiled by env-builder. Edit source files in .env.d and rebuild.',
            '',
        ];

        foreach ($sourceFiles as $sourceFile) {
            $entries = $this->parser->parse($sourceFile);
            $processedSources[] = $sourceFile->relativePath;

            if ($entries === []) {
                continue;
            }

            $blockValues = [];
            $blockOrder = [];
            foreach ($entries as $entry) {
                if (array_key_exists($entry->key, $blockValues)) {
                    $blockOrder = array_values(
                        array_filter(
                            $blockOrder,
                            static fn (string $key): bool => $key !== $entry->key
                        )
                    );
                }

                $blockValues[$entry->key] = $entry->value;
                $blockOrder[] = $entry->key;
            }

            $lines[] = sprintf('### [%s] ###', $sourceFile->relativePath);
            foreach ($blockOrder as $key) {
                $value = $blockValues[$key];
                $lines[] = sprintf('%s=%s', $key, $value);
                $resolvedVariables[$key] = $value;
            }

            $lines[] = '';
        }

        $content = rtrim(implode(PHP_EOL, $lines)) . PHP_EOL;

        return new CompilationResult(
            content: $content,
            processedSources: $processedSources,
            resolvedVariables: $resolvedVariables
        );
    }
}

