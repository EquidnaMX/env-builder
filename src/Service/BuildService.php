<?php

declare(strict_types=1);

namespace EnvBuilder\Service;

use EnvBuilder\Deploy\EnvDeployer;
use EnvBuilder\Env\EnvCompiler;
use EnvBuilder\Exception\EnvBuilderException;

final class BuildService
{
    public function __construct(
        private readonly EnvCompiler $compiler = new EnvCompiler(),
        private readonly EnvDeployer $deployer = new EnvDeployer()
    ) {
    }

    public function build(
        string $sourceDir,
        string $outputFile,
        bool $includeDev,
        ?string $deployTarget = null
    ): BuildSummary {
        $result = $this->compiler->compile($sourceDir, $includeDev);
        $this->writeOutput($outputFile, $result->content);

        $deploymentTransport = null;
        if ($deployTarget !== null && $deployTarget !== '') {
            $deployment = $this->deployer->deploy($outputFile, $deployTarget);
            $deploymentTransport = $deployment->transport;
        }

        return new BuildSummary(
            outputFile: $outputFile,
            sourceCount: count($result->processedSources),
            variableCount: count($result->resolvedVariables),
            deploymentTransport: $deploymentTransport
        );
    }

    private function writeOutput(string $outputFile, string $content): void
    {
        $targetDirectory = dirname($outputFile);
        if ($targetDirectory !== '.' && !is_dir($targetDirectory)) {
            if (!mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
                throw new EnvBuilderException(
                    sprintf('Unable to create output directory "%s".', $targetDirectory)
                );
            }
        }

        if ($targetDirectory !== '.' && !is_writable($targetDirectory)) {
            throw new EnvBuilderException(
                sprintf('Output directory "%s" is not writable.', $targetDirectory)
            );
        }

        if (file_put_contents($outputFile, $content) === false) {
            throw new EnvBuilderException(
                sprintf('Failed to write compiled file to "%s".', $outputFile)
            );
        }
    }
}

