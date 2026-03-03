<?php

declare(strict_types=1);

$root = __DIR__;
$distDir = $root . '/dist';
$pharPath = $distDir . '/env-builder.phar';
$pharAlias = 'env-builder.phar';

if (!file_exists($root . '/vendor/autoload.php')) {
    fwrite(STDERR, "Missing vendor/autoload.php. Run composer install first.\n");
    exit(1);
}

if (ini_get('phar.readonly') === '1') {
    fwrite(STDERR, "phar.readonly is enabled. Run with: php -d phar.readonly=0 build.php\n");
    exit(1);
}

if (!is_dir($distDir) && !mkdir($distDir, 0775, true) && !is_dir($distDir)) {
    fwrite(STDERR, "Cannot create dist directory.\n");
    exit(1);
}

if (file_exists($pharPath) && !unlink($pharPath)) {
    fwrite(STDERR, "Cannot overwrite existing PHAR: {$pharPath}\n");
    exit(1);
}

$phar = new Phar($pharPath, 0, $pharAlias);
$phar->startBuffering();

$includePaths = ['bin', 'src', 'vendor', 'composer.json', 'LICENSE', 'README.md'];

foreach ($includePaths as $path) {
    $absolute = $root . DIRECTORY_SEPARATOR . $path;
    if (!file_exists($absolute)) {
        continue;
    }

    if (is_file($absolute)) {
        $phar->addFile($absolute, str_replace('\\', '/', $path));
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($absolute, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $fileInfo) {
        if (!$fileInfo->isFile()) {
            continue;
        }

        $filePath = $fileInfo->getPathname();
        $localPath = str_replace('\\', '/', substr($filePath, strlen($root) + 1));
        $phar->addFile($filePath, $localPath);
    }
}

$stub = <<<'PHP'
#!/usr/bin/env php
<?php
declare(strict_types=1);

Phar::mapPhar('env-builder.phar');
require 'phar://env-builder.phar/bin/env-builder';
__HALT_COMPILER();
PHP;

$phar->setSignatureAlgorithm(Phar::SHA512);
$phar->setStub($stub);
$phar->stopBuffering();

@chmod($pharPath, 0755);

fwrite(STDOUT, "PHAR created at: {$pharPath}\n");

