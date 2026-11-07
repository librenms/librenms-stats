<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class Submissions
{
    public function record(string $uuid, array $groups, ?array $devices): void
    {
        DB::transaction(function () use ($uuid, $groups, $devices) {
            $hosts = DB::table('hosts');
            $hostId = (clone $hosts)->where('uuid', $uuid)->orderBy('hosts_id')->lockForUpdate()->value('hosts_id');
            $now = DB::raw('CURRENT_TIMESTAMP');

            if ($hostId === null) {
                $hostId = $hosts->insertGetId(['uuid' => $uuid, 'first_added' => $now, 'last_updated' => $now]);
            } else {
                $hosts->where('hosts_id', $hostId)->update(['last_updated' => $now]);
            }

            $runId = DB::table('run')->insertGetId(['hosts_id' => $hostId, 'datetime' => $now]);

            foreach ($groups as $group => $entries) {
                foreach ($entries as $entry) {
                    $keys = array_keys($entry);
                    $values = array_values($entry);

                    DB::table('data')->insert([
                        'uuid' => $uuid,
                        'run_id' => $runId,
                        'group' => $group,
                        'total' => $values[0],
                        'name' => $keys[1] ?? '',
                        'value' => $values[1] ?? '',
                    ]);
                }
            }

            if ($devices !== null) {
                DB::table('devinfo')->where('uuid', $uuid)->delete();

                foreach ($devices as $device) {
                    DB::table('devinfo')->insert([
                        'uuid' => $uuid,
                        'count' => $device['count'],
                        'os' => $device['os'],
                        'sysDescr' => $device['sysDescr'] ?? null,
                        'sysObjectID' => $device['sysObjectID'] ?? null,
                    ]);
                }
            }
        }, 3);
    }

    public function clear(string $uuid): void
    {
        DB::transaction(function () use ($uuid) {
            $hosts = DB::table('hosts')->where('uuid', $uuid);
            $hostIds = (clone $hosts)->lockForUpdate()->pluck('hosts_id');
            DB::table('run')->whereIn('hosts_id', $hostIds)->delete();
            DB::table('data')->where('uuid', $uuid)->delete();
            DB::table('devinfo')->where('uuid', $uuid)->delete();
            $hosts->delete();
        }, 3);
    }

    public function purge(): void
    {
        $runs = DB::table('run');
        if (DB::getDriverName() === 'sqlite') {
            $runs->where('datetime', '<=', now('UTC')->subMonthsNoOverflow(6));
        } else {
            $runs->whereRaw('`datetime` <= NOW() - INTERVAL 6 MONTH');
        }

        $runs->chunkById(500, function ($runs) {
            $ids = $runs->pluck('run_id');
            DB::transaction(function () use ($ids) {
                DB::table('data')->whereIn('run_id', $ids)->delete();
                DB::table('run')->whereIn('run_id', $ids)->delete();
            }, 3);
        }, 'run_id');
    }
}
