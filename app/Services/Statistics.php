<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Statistics
{
    public function charts(): array
    {
        return config('charts');
    }

    public function submitterCount(): int
    {
        return Cache::remember('submitter_count', config('stats.cache_ttl'), fn () => DB::table('run')
            ->whereRaw(DB::getDriverName() === 'sqlite'
                ? "`datetime` >= datetime('now', '-24 hours')"
                : '`datetime` >= DATE_SUB(NOW(), INTERVAL 24 HOUR)')
            ->distinct()->count('hosts_id'));
    }

    public function chart(string $id, bool $refresh = false, ?callable $debug = null): array
    {
        $chart = $this->charts()[$id] ?? null;
        if ($chart === null) {
            return [];
        }

        $cacheKey = 'stats:charts:v3:'.$id;
        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, config('stats.cache_ttl'), function () use ($chart, $debug) {
            if ($chart['adoption'] ?? false) {
                return $this->featureAdoption(config('stats.adoption_features'), $debug);
            }

            $type = $chart['type'];
            $line = $type === 'line';
            $sqlite = DB::getDriverName() === 'sqlite';
            $snapshot = $chart['snapshot'] ?? false;
            $perInstall = $chart['per_install'] ?? false;
            $total = $snapshot
                ? 'COUNT(DISTINCT `data`.`uuid`)'
                : (($sqlite ? ($chart['sqlite_total'] ?? null) : null) ?? $chart['total'] ?? 'SUM(`total`)');
            $value = ($sqlite ? ($chart['sqlite_value_modifier'] ?? null) : null) ?? $chart['value_modifier'] ?? '`value`';
            if ($line) {
                $value = $sqlite ? 'date(`run`.`datetime`)' : "DATE_FORMAT(`run`.`datetime`, '%Y-%m-%d')";
            }

            $group = $chart['group'] ?? '`group`, `final_value`';
            $period = $line ? '3 MONTH' : '24 HOUR';

            if ($snapshot || $perInstall) {
                $query = $this->latestData($chart['data'], daily: $perInstall);
                if ($perInstall) {
                    $query->join('run', 'data.run_id', '=', 'run.run_id');
                }
            } else {
                $query = DB::table('data')->join('run', 'data.run_id', '=', 'run.run_id');
                if ($sqlite) {
                    $cutoff = $line ? now('UTC')->subMonthsNoOverflow(3) : now('UTC')->subHours(24);
                    $query->where('run.datetime', '>=', $cutoff);
                } else {
                    $query->whereRaw("`run`.`datetime` >= DATE_SUB(NOW(), INTERVAL $period)");
                }
            }

            $query
                ->selectRaw("$total AS `total`, MIN(`group`) AS `group`, MAX(`name`) AS `name`, $value AS `final_value`")
                ->where('data.name', '!=', Submissions::EMPTY_GROUP)
                ->whereIn('group', $chart['data'])
                ->groupByRaw($group);

            if ($perInstall) {
                $query->selectRaw('COUNT(DISTINCT `data`.`uuid`) AS installs');
            }

            if (isset($chart['sql_limit'])) {
                $query->whereRaw($chart['sql_limit']);
            }

            if ($type === 'bar') {
                $query->orderByRaw('`final_value` * 1, `final_value`');
            }

            if ($debug !== null) {
                $debug($query->toRawSql());
            }

            return $query->get()->map(function ($row) use ($type, $perInstall) {
                $label = empty($row->name) ? $row->group : $row->final_value;

                if ($perInstall) {
                    return ['y' => $label, 'a' => $row->total, 'installs' => $row->installs];
                }

                return $type === 'donut'
                    ? ['label' => $label, 'value' => $row->total]
                    : ['y' => $label, 'a' => $row->total];
            })->all();
        });
    }

    private function latestData(array $groups, bool $daily = false): Builder
    {
        $sqlite = DB::getDriverName() === 'sqlite';
        $period = $daily ? '3 MONTH' : '24 HOUR';
        $latest = DB::table('data')
            ->select('data.uuid as snapshot_uuid', 'data.group as snapshot_group')
            ->selectRaw('MAX(`data`.`run_id`) AS latest_run_id')
            ->join('run', 'data.run_id', '=', 'run.run_id')
            ->whereIn('data.group', $groups)
            ->groupBy('data.uuid', 'data.group');

        if ($sqlite) {
            $cutoff = $daily ? now('UTC')->subMonthsNoOverflow(3) : now('UTC')->subHours(24);
            $latest->where('run.datetime', '>=', $cutoff);
        } else {
            $latest->whereRaw("`run`.`datetime` >= DATE_SUB(NOW(), INTERVAL $period)");
        }
        if ($daily) {
            $latest->groupByRaw($sqlite ? 'date(`run`.`datetime`)' : "DATE_FORMAT(`run`.`datetime`, '%Y-%m-%d')");
        }

        return DB::table('data')->joinSub($latest, 'latest', function ($join) {
            $join->on('data.uuid', '=', 'latest.snapshot_uuid')
                ->on('data.group', '=', 'latest.snapshot_group')
                ->on('data.run_id', '=', 'latest.latest_run_id');
        });
    }

    private function featureAdoption(array $features, ?callable $debug): array
    {
        $groups = array_unique(array_merge(...array_values($features)));
        $query = $this->latestData($groups)
            ->select('data.uuid', 'data.group')
            ->selectRaw('SUM(`data`.`total`) AS total')
            ->groupBy('data.uuid', 'data.group');

        if ($debug !== null) {
            $debug($query->toRawSql());
        }

        $reports = $query->get();
        $points = [];

        foreach ($features as $label => $groups) {
            $featureReports = $reports->whereIn('group', $groups);
            $reported = $featureReports->pluck('uuid')->unique()->count();
            $active = $featureReports->where('total', '>', 0)->pluck('uuid')->unique()->count();
            $points[] = [
                'y' => $label,
                'a' => $reported > 0 ? 100 * $active / $reported : null,
                'active' => $active,
                'reported' => $reported,
            ];
        }

        return $points;
    }

    public function devices(?string $os, ?string $objectId, ?string $description, string $sort, int $limit, ?callable $debug = null): array
    {
        $columns = $sort === 'sysObjectID' ? ['os', 'sysObjectID'] : ['os', 'sysObjectID', 'sysDescr'];
        $query = DB::table('devinfo')->select($columns)->selectRaw('SUM(`count`) AS total')
            ->groupBy(...$columns)->limit($limit);

        if ($os !== null) {
            $query->where('os', $os);
        }

        if ($description !== null) {
            $query->whereLike('sysDescr', "%$description%", caseSensitive: true);
        }

        if ($objectId !== null) {
            $objectId = preg_replace('/^1/', '.1', $objectId);
            $named = str_replace('.1.3.6.1.4.1', 'enterprises', $objectId);
            $numeric = str_replace('enterprises', '.1.3.6.1.4.1', $objectId);
            $query->where(function ($query) use ($named, $numeric) {
                $query->where('sysObjectID', 'like', "$named%")
                    ->orWhere('sysObjectID', 'like', "$numeric%")
                    ->orWhere('sysObjectID', 'like', '"'.$numeric.'%');
            });
        }

        if (in_array($sort, ['os', 'sysDescr'], true)) {
            $query->orderBy($sort);
        }

        $query->orderByDesc('total');
        if ($debug !== null) {
            $debug($query->toRawSql());
        }

        return $query->get()->all();
    }
}
