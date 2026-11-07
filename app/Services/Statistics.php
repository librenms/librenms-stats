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

    public function publicChart(string $id): array
    {
        $rows = $this->chart($id);
        $chart = $this->charts()[$id] ?? [];
        $displayType = $chart['display_type'] ?? $chart['type'] ?? null;
        $limit = config('stats.visible_categories')[$displayType] ?? null;

        if ($limit === null || ($chart['percent'] ?? false) || count($rows) <= $limit) {
            return $rows;
        }

        $value = $chart['type'] === 'donut' ? 'value' : 'a';
        $hidden = collect($rows)
            ->sortByDesc(fn (array $row) => (float) $row[$value])
            ->slice($limit)
            ->filter(fn (array $row) => (float) $row[$value] === 1.0)
            ->all();

        return array_values(array_diff_key($rows, $hidden));
    }

    public function chart(string $id, bool $refresh = false, ?callable $debug = null): array
    {
        $chart = $this->charts()[$id] ?? null;
        if ($chart === null) {
            return [];
        }

        $version = $chart['type'] === 'line' ? 'v4' : 'v3';
        $cacheKey = "stats:charts:$version:$id";
        $ttl = config('stats.cache_ttl');
        $calculate = function () use ($chart, $debug) {
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

            if ($perInstall) {
                $query = $this->latestDailyData($chart['data'], $value);
            } elseif ($snapshot) {
                $query = $this->latestData($chart['data']);
            } else {
                $query = DB::table('data')->join('run', 'data.run_id', '=', 'run.run_id');
                if ($line) {
                    $this->completedTrendDays($query);
                } elseif ($sqlite) {
                    $query->where('run.datetime', '>=', now('UTC')->subHours(24));
                } else {
                    $query->whereRaw('`run`.`datetime` >= DATE_SUB(NOW(), INTERVAL 24 HOUR)');
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
        };

        if ($refresh) {
            $data = $calculate();
            Cache::put($cacheKey, $data, $ttl);

            return $data;
        }

        return Cache::remember($cacheKey, $ttl, $calculate);
    }

    private function latestDailyData(array $groups, string $day): Builder
    {
        // Find the latest runs before reading their individual metric rows.
        $latest = DB::table('run')
            ->selectRaw('MAX(`run`.`run_id`) AS latest_run_id')
            ->join('hosts', 'hosts.hosts_id', '=', 'run.hosts_id')
            ->whereExists(function (Builder $query) use ($groups) {
                $query->selectRaw('1')->from('data')
                    ->whereColumn('data.run_id', 'run.run_id')
                    ->whereIn('data.group', $groups);
            })
            ->groupBy('hosts.uuid')->groupByRaw($day);

        $this->completedTrendDays($latest);

        return DB::query()->fromSub($latest, 'latest')
            ->join('data', 'data.run_id', '=', 'latest.latest_run_id')
            ->join('run', 'data.run_id', '=', 'run.run_id');
    }

    private function completedTrendDays(Builder $query): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $today = now('UTC')->startOfDay();
            $query->where('run.datetime', '>=', $today->copy()->subMonthsNoOverflow(3))
                ->where('run.datetime', '<', $today);
        } else {
            $query->whereRaw('`run`.`datetime` >= DATE_SUB(CURRENT_DATE, INTERVAL 3 MONTH)')
                ->whereRaw('`run`.`datetime` < CURRENT_DATE');
        }
    }

    private function latestData(array $groups): Builder
    {
        $latest = DB::table('data')
            ->select('data.uuid as snapshot_uuid', 'data.group as snapshot_group')
            ->selectRaw('MAX(`data`.`run_id`) AS latest_run_id')
            ->join('run', 'data.run_id', '=', 'run.run_id')
            ->whereRaw(DB::getDriverName() === 'sqlite'
                ? "`run`.`datetime` >= datetime('now', '-24 hours')"
                : '`run`.`datetime` >= DATE_SUB(NOW(), INTERVAL 24 HOUR)')
            ->whereIn('data.group', $groups)
            ->groupBy('data.uuid', 'data.group');

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
