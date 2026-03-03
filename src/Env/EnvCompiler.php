<?php

declare(strict_types=1);

namespace EnvBuilder\Env;

final class EnvCompiler
{
    public function __construct(
        private readonly SourceFileResolver $resolver = new SourceFileResolver(),
        private readonly EnvFileParser $parser = new EnvFileParser()
    ) {}

    public function compile(string $sourceDir, bool $includeDev): CompilationResult
    {
        $sourceFiles = $this->resolver->resolve($sourceDir, $includeDev);
        $processedSources = [];
        $resolvedVariables = [];
        $keyOrigins = [];
        $blocks = [];

        $lines = [
            '# Compiled by env-builder. Edit source files in .env.d and rebuild.',
            '',
        ];

        foreach ($sourceFiles as $blockIndex => $sourceFile) {
            $entries = $this->parser->parse($sourceFile);
            $processedSources[] = $sourceFile->relativePath;
            $blocks[$blockIndex] = [
                'source' => $sourceFile->relativePath,
                'values' => [],
            ];

            if ($entries === []) {
                continue;
            }

            foreach ($entries as $entry) {
                if (isset($keyOrigins[$entry->key])) {
                    $originBlock = $keyOrigins[$entry->key];
                    unset($blocks[$originBlock]['values'][$entry->key]);
                }

                if (array_key_exists($entry->key, $blocks[$blockIndex]['values'])) {
                    unset($blocks[$blockIndex]['values'][$entry->key]);
                }

                $blocks[$blockIndex]['values'][$entry->key] = $entry->value;
                $keyOrigins[$entry->key] = $blockIndex;
                $resolvedVariables[$entry->key] = $entry->value;
            }
        }

        foreach ($blocks as $block) {
            if ($block['values'] === []) {
                continue;
            }

            $lines[] = sprintf('### [%s] ###', $block['source']);
            foreach ($block['values'] as $key => $value) {
                $lines[] = sprintf('%s=%s', $key, $value);
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
