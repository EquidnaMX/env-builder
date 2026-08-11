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
    public function resolve(string $sourceDir, bool $includeDev, bool $includeStaging = false): array
    {
        if ($includeDev && $includeStaging) {
            throw new EnvBuilderException('The development and staging overlays cannot be enabled together.');
        }

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
        $stagingFiles = [];

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

            if (str_ends_with($relativePath, '.env.staging')) {
                $stagingFiles[$relativePath] = $absolutePath;
                continue;
            }

            if (str_ends_with($relativePath, '.env')) {
                $baseFiles[$relativePath] = $absolutePath;
            }
        }

        ksort($baseFiles);
        ksort($devFiles);
        ksort($stagingFiles);

        $ordered = [];
        $consumedOverlays = [];
        $selectedOverlays = $includeDev ? $devFiles : ($includeStaging ? $stagingFiles : []);
        $overlaySuffix = $includeDev ? '.dev' : '.staging';

        $baseOrder = array_keys($baseFiles);
        usort(
            $baseOrder,
            static function (string $left, string $right): int {
                $leftPriority = $left === 'app.env' ? 0 : 1;
                $rightPriority = $right === 'app.env' ? 0 : 1;

                if ($leftPriority !== $rightPriority) {
                    return $leftPriority <=> $rightPriority;
                }

                return strcmp($left, $right);
            }
        );

        foreach ($baseOrder as $relativePath) {
            $absolutePath = $baseFiles[$relativePath];
            $ordered[] = new SourceFile($absolutePath, $relativePath, false);

            $overlayPath = $relativePath . $overlaySuffix;
            if (($includeDev || $includeStaging) && isset($selectedOverlays[$overlayPath])) {
                $ordered[] = new SourceFile($selectedOverlays[$overlayPath], $overlayPath, $includeDev);
                $consumedOverlays[$overlayPath] = true;
            }
        }

        if ($includeDev || $includeStaging) {
            $overlayOrder = array_keys($selectedOverlays);
            usort(
                $overlayOrder,
                static function (string $left, string $right) use ($overlaySuffix): int {
                    $leftPriority = $left === 'app.env' . $overlaySuffix ? 0 : 1;
                    $rightPriority = $right === 'app.env' . $overlaySuffix ? 0 : 1;

                    if ($leftPriority !== $rightPriority) {
                        return $leftPriority <=> $rightPriority;
                    }

                    return strcmp($left, $right);
                }
            );

            foreach ($overlayOrder as $relativePath) {
                if (isset($consumedOverlays[$relativePath])) {
                    continue;
                }

                $absolutePath = $selectedOverlays[$relativePath];
                $ordered[] = new SourceFile($absolutePath, $relativePath, $includeDev);
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
