<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$excludedDirectories = [
    $root . '/vendor',
    $root . '/node_modules',
    $root . '/public_assets/uploads',
    $root . '/storage/cache',
    $root . '/storage/logs',
];

$iterator = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        static function (SplFileInfo $file) use ($excludedDirectories): bool {
            $path = $file->getPathname();

            foreach ($excludedDirectories as $excludedDirectory) {
                if (str_starts_with($path, $excludedDirectory)) {
                    return false;
                }
            }

            if ($file->isDir()) {
                return true;
            }

            return $file->getExtension() === 'php';
        }
    )
);

$checked = 0;

foreach ($iterator as $file) {
    if (!$file instanceof SplFileInfo || !$file->isFile()) {
        continue;
    }

    $checked++;
    $path = $file->getPathname();
    $command = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($path) . ' 2>&1';
    exec($command, $output, $exitCode);

    if ($exitCode !== 0) {
        fwrite(STDERR, "PHP lint failed: {$path}\n" . implode("\n", $output) . "\n");
        exit(1);
    }
}

echo "PHP lint .............. OK ({$checked} files)\n";
