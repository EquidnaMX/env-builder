<?php

declare(strict_types=1);

namespace EnvBuilder\Deploy;

use EnvBuilder\Exception\DeploymentException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

final class EnvDeployer
{
    public function deploy(string $localFile, string $target): DeploymentResult
    {
        $absoluteFile = realpath($localFile);
        if ($absoluteFile === false || !is_file($absoluteFile) || !is_readable($absoluteFile)) {
            throw new DeploymentException(
                sprintf('Compiled file "%s" does not exist or is not readable.', $localFile)
            );
        }

        [$remoteHost, $remotePath] = $this->parseTarget($target);
        $remoteRef = sprintf('%s:%s', $remoteHost, $remotePath);

        $finder = new ExecutableFinder();
        $rsync = $finder->find('rsync');
        if ($rsync !== null) {
            return $this->run(
                command: [$rsync, '-az', '--chmod=F600', $absoluteFile, $remoteRef],
                transport: 'rsync'
            );
        }

        $scp = $finder->find('scp');
        if ($scp === null) {
            throw new DeploymentException('Neither "rsync" nor "scp" was found in PATH.');
        }

        return $this->run(
            command: [$scp, $absoluteFile, $remoteRef],
            transport: 'scp'
        );
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function parseTarget(string $target): array
    {
        if (!preg_match('/^([^:]+):(.+)$/', $target, $matches)) {
            throw new DeploymentException(
                'Invalid --deploy target. Expected format user@host:/absolute/path/.env'
            );
        }

        return [$matches[1], $matches[2]];
    }

    /**
     * @param list<string> $command
     */
    private function run(array $command, string $transport): DeploymentResult
    {
        $process = new Process($command);
        $process->setTimeout(180);
        $process->run();

        if (!$process->isSuccessful()) {
            $errorOutput = trim($process->getErrorOutput());
            $stdOutput = trim($process->getOutput());
            $message = $errorOutput !== '' ? $errorOutput : $stdOutput;
            if ($message === '') {
                $message = 'Unknown deployment error.';
            }

            throw new DeploymentException(
                sprintf('%s deployment failed: %s', strtoupper($transport), $message)
            );
        }

        return new DeploymentResult($transport, trim($process->getOutput()));
    }
}

