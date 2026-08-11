<?php

declare(strict_types=1);

namespace EnvBuilder\Console\Command;

use EnvBuilder\Exception\EnvBuilderException;
use EnvBuilder\Service\BuildService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class BuildCommand extends Command
{
    public function __construct(private readonly BuildService $buildService = new BuildService())
    {
        parent::__construct('build');
    }

    protected function configure(): void
    {
        $this->setDescription('Compile .env fragments from .env.d into a single .env file.');

        $this
            ->addOption(
                'source',
                's',
                InputOption::VALUE_REQUIRED,
                'Source directory containing .env fragments.',
                '.env.d'
            )
            ->addOption(
                'output',
                'o',
                InputOption::VALUE_REQUIRED,
                'Compiled output file path.',
                '.env'
            )
            ->addOption(
                'dev',
                null,
                InputOption::VALUE_NONE,
                'Include *.env.dev overlays and apply them after each base *.env file.'
            )
            ->addOption(
                'staging',
                null,
                InputOption::VALUE_NONE,
                'Include *.env.staging overlays and apply them after each base *.env file.'
            )
            ->addOption(
                'deploy',
                null,
                InputOption::VALUE_REQUIRED,
                'Remote target in format user@host:/absolute/path for SCP/rsync deployment.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sourceDir = (string) $input->getOption('source');
        $outputFile = (string) $input->getOption('output');
        $includeDev = (bool) $input->getOption('dev');
        $includeStaging = (bool) $input->getOption('staging');

        $deployTarget = $input->getOption('deploy');
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
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));
            return Command::FAILURE;
        }

        $output->writeln(sprintf('<info>Compiled file written to %s</info>', $summary->outputFile));
        $output->writeln(
            sprintf(
                '<comment>Processed %d source files, resolved %d variables.</comment>',
                $summary->sourceCount,
                $summary->variableCount
            )
        );

        if ($summary->deploymentTransport !== null) {
            $output->writeln(
                sprintf('<info>Deployment finished using %s.</info>', strtoupper($summary->deploymentTransport))
            );
        }

        return Command::SUCCESS;
    }
}
