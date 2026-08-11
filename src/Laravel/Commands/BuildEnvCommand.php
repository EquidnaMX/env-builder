<?php

declare(strict_types=1);

namespace EnvBuilder\Laravel\Commands;

use EnvBuilder\Exception\EnvBuilderException;
use EnvBuilder\Service\BuildService;
use Illuminate\Console\Command;

final class BuildEnvCommand extends Command
{
    protected $signature = 'env-builder:build
                            {--source=.env.d : Source directory containing .env fragments}
                            {--output=.env : Compiled output file}
                            {--dev : Include *.env.dev overlays}
                            {--staging : Include *.env.staging overlays}
                            {--deploy= : Deploy target user@host:/absolute/path/.env}';

    protected $description = 'Compile .env fragments from .env.d and optionally deploy the resulting file.';

    public function __construct(private readonly BuildService $buildService = new BuildService())
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $sourceDir = (string) $this->option('source');
        $outputFile = (string) $this->option('output');
        $includeDev = (bool) $this->option('dev');
        $includeStaging = (bool) $this->option('staging');

        $deployTarget = $this->option('deploy');
        if (!is_string($deployTarget)) {
            $deployTarget = null;
        }

        try {
            $summary = $this->buildService->build(
                sourceDir: $sourceDir,
                outputFile: $outputFile,
                includeDev: $includeDev,
                deployTarget: $deployTarget,
                includeStaging: $includeStaging
            );
        } catch (EnvBuilderException $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $this->info(sprintf('Compiled file written to %s', $summary->outputFile));
        $this->line(
            sprintf(
                'Processed %d source files, resolved %d variables.',
                $summary->sourceCount,
                $summary->variableCount
            )
        );

        if ($summary->deploymentTransport !== null) {
            $this->info(sprintf('Deployment finished using %s.', strtoupper($summary->deploymentTransport)));
        }

        return self::SUCCESS;
    }
}
