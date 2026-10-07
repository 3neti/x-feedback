<?php

declare(strict_types=1);

it('publishes an empty queue manifest while x-feedback has no queued workloads', function (): void {
    $root = dirname(__DIR__, 2);
    $composer = json_decode(file_get_contents($root.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
    $manifestPath = data_get($composer, 'extra.settlement-os.queue-manifest');

    expect($manifestPath)->toBe('resources/settlement-os/queues.php');

    $manifest = require $root.'/'.$manifestPath;
    $queuedSources = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/src'));

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php'
            && str_contains(file_get_contents($file->getPathname()), 'ShouldQueue')) {
            $queuedSources[] = $file->getPathname();
        }
    }

    expect($manifest)->toMatchArray([
        'schema' => 'settlement-os.queue-topology.v1',
        'package' => '3neti/x-feedback',
        'lanes' => [],
        'status' => 'no-queued-workloads',
    ])->and($queuedSources)->toBe([]);
});
