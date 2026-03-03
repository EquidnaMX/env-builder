<?php

declare(strict_types=1);

namespace EnvBuilder\Env;

use EnvBuilder\Exception\EnvBuilderException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class SourceFileResolver
{
    /**
     * @return list<SourceFile>
     */
    public function resolve(string $sourceDir, bool $includeDev): array
    {
        $normalizedDir = rtrim($sourceDir, DIRECTORY_SEPARATOR . '/');
        if ($normalizedDir === '') {
            $normalizedDir = '.';
        }
        $sourceRoot = realpath($normalizedDir) ?: $normalizedDir;
        $sourceRoot = str_replace('\\', '/', $sourceRoot);
        $sourceRoot = rtrim($sourceRoot, '/');

        if (!is_dir($normalizedDir)) {
            throw new EnvBuilderException(
                sprintf('Source directory "%s" does not exist.', $sourceDir)
            );
        }

        if (!is_readable($normalizedDir)) {
            throw new EnvBuilderException(
                sprintf('Source directory "%s" is not readable.', $sourceDir)
            );
        }

        $baseFiles = [];
        $devFiles = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($normalizedDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $fileInfo */
        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }

            $absolutePath = $fileInfo->getRealPath() ?: $fileInfo->getPathname();
            if (!is_readable($absolutePath)) {
                throw new EnvBuilderException(
                    sprintf('Source file "%s" is not readable.', $absolutePath)
                );
            }

            $normalizedPath = str_replace('\\', '/', $absolutePath);
            if (str_starts_with($normalizedPath, $sourceRoot . '/')) {
                $relativePath = substr($normalizedPath, strlen($sourceRoot) + 1);
            } else {
                $relativePath = basename($normalizedPath);
            }

            if (str_ends_with($relativePath, '.env.dev')) {
                $devFiles[$relativePath] = $absolutePath;
                continue;
            }

            if (str_ends_with($relativePath, '.env')) {
                $baseFiles[$relativePath] = $absolutePath;
            }
        }

        ksort($baseFiles);
        ksort($devFiles);

        $ordered = [];
        $consumedDev = [];

        foreach ($baseFiles as $relativePath => $absolutePath) {
            $ordered[] = new SourceFile($absolutePath, $relativePath, false);

            $devPath = $relativePath . '.dev';
            if ($includeDev && isset($devFiles[$devPath])) {
                $ordered[] = new SourceFile($devFiles[$devPath], $devPath, true);
                $consumedDev[$devPath] = true;
            }
        }

        if ($includeDev) {
            foreach ($devFiles as $relativePath => $absolutePath) {
                if (isset($consumedDev[$relativePath])) {
                    continue;
                }

                $ordered[] = new SourceFile($absolutePath, $relativePath, true);
            }
        }

        if ($ordered === []) {
            throw new EnvBuilderException(
                sprintf('No *.env files were found inside "%s".', $sourceDir)
            );
        }

        return $ordered;
    }
}
