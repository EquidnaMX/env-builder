<?php

declare(strict_types=1);

namespace EnvBuilder\Env;

use EnvBuilder\Exception\InvalidEnvFileException;

final class EnvFileParser
{
    /**
     * @return list<EnvEntry>
     */
    public function parse(SourceFile $sourceFile): array
    {
        $lines = file($sourceFile->absolutePath, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            throw new InvalidEnvFileException(
                sprintf('Could not read "%s".', $sourceFile->relativePath)
            );
        }

        $entries = [];
        foreach ($lines as $lineNumber => $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with(ltrim($line), '#')) {
                continue;
            }

            $content = ltrim($line);
            if (str_starts_with($content, 'export ')) {
                $content = substr($content, 7);
            }

            $separatorPos = strpos($content, '=');
            if ($separatorPos === false) {
                throw new InvalidEnvFileException(
                    sprintf(
                        'Invalid line in "%s" at line %d. Expected KEY=VALUE.',
                        $sourceFile->relativePath,
                        $lineNumber + 1
                    )
                );
            }

            $key = trim(substr($content, 0, $separatorPos));
            $value = substr($content, $separatorPos + 1);

            if ($key === '') {
                throw new InvalidEnvFileException(
                    sprintf(
                        'Empty key in "%s" at line %d.',
                        $sourceFile->relativePath,
                        $lineNumber + 1
                    )
                );
            }

            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)) {
                throw new InvalidEnvFileException(
                    sprintf(
                        'Invalid key "%s" in "%s" at line %d.',
                        $key,
                        $sourceFile->relativePath,
                        $lineNumber + 1
                    )
                );
            }

            $entries[] = new EnvEntry($key, $value, $lineNumber + 1);
        }

        return $entries;
    }
}

