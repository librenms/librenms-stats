<?php

use App\Services\Statistics;
use App\Services\Submissions;
use Illuminate\Support\Facades\Artisan;

Artisan::command('stats:cache-graphs
    {--c|chart=* : Chart ID to cache. Repeat to select multiple charts}
    {--f|force : Refresh cached charts immediately}', function (Statistics $statistics) {
    $charts = $this->option('chart') ?: array_keys($statistics->charts());
    $output = $this->getOutput();

    foreach ($charts as $id) {
        $output->writeln("Executing $id...", $output::VERBOSITY_VERBOSE);
        $start = microtime(true);
        $statistics->chart($id, $this->option('force'), $output->isVeryVerbose() ? $this->line(...) : null);
        $elapsed = number_format(microtime(true) - $start, 4);
        $output->writeln("$id took $elapsed seconds", $output::VERBOSITY_VERBOSE);
    }
})->purpose('Warm the statistics chart cache');

Artisan::command('stats:device-info
    {--o|os= : Filter by exact OS}
    {--i|object-id= : Filter by sysObjectID prefix}
    {--d|description= : Filter by case-sensitive sysDescr substring}
    {--s|sort= : Sort by os, sysObjectID, or sysDescr}
    {--l|limit=30 : Maximum number of results}', function (Statistics $statistics) {
    $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    if ($limit === false) {
        $this->error('The limit must be a non-negative integer.');

        return 1;
    }

    $devices = $statistics->devices(
        $this->option('os'),
        $this->option('object-id'),
        $this->option('description'),
        $this->option('sort') ?? '',
        $limit,
        $this->getOutput()->isVeryVerbose() ? $this->line(...) : null,
    );

    $osWidth = 2;
    $objectIdWidth = 11;
    foreach ($devices as $device) {
        $device->sysObjectID = str_replace('enterprises', '.1.3.6.1.4.1', trim($device->sysObjectID ?? '', '"'));
        $osWidth = max($osWidth, strlen($device->os));
        $objectIdWidth = max($objectIdWidth, strlen($device->sysObjectID));
    }

    $format = "|%5.5s|%-{$osWidth}.{$osWidth}s|%-{$objectIdWidth}.{$objectIdWidth}s| %s |";
    $output = $this->getOutput();
    $output->writeln(sprintf($format, 'Total', 'OS', 'sysObjectID', 'sysDescr'), $output::OUTPUT_RAW);

    foreach ($devices as $device) {
        $output->writeln(sprintf($format, $device->total, $device->os, $device->sysObjectID, $device->sysDescr ?? ''), $output::OUTPUT_RAW);
    }

    return 0;
})->purpose('Query submitted device information');

Artisan::command('stats:purge', function (Submissions $submissions) {
    $submissions->purge();
})->purpose('Delete runs and associated chart data at least six months old');
