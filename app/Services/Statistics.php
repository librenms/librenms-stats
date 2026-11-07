<?php

namespace App\Services;

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

        if ($refresh) {
            Cache::forget($id);
        }

        return Cache::remember($id, config('stats.cache_ttl'), function () use ($chart, $debug) {
            $type = $chart['type'];
            $line = $type === 'line';
            $sqlite = DB::getDriverName() === 'sqlite';
            $total = ($sqlite ? ($chart['sqlite_total'] ?? null) : null) ?? $chart['total'] ?? 'SUM(`total`)';
            $value = ($sqlite ? ($chart['sqlite_value_modifier'] ?? null) : null) ?? $chart['value_modifier'] ?? '`value`';
            if ($line) {
                $value = $sqlite ? 'date(`run`.`datetime`)' : "DATE_FORMAT(`run`.`datetime`, '%Y-%m-%d')";
            }

            $group = $chart['group'] ?? '`group`, `final_value`';
            $period = $line ? '3 MONTH' : '24 HOUR';

            $query = DB::table('data')
                ->selectRaw("$total AS `total`, MIN(`group`) AS `group`, MAX(`name`) AS `name`, $value AS `final_value`")
                ->join('run', 'data.run_id', '=', 'run.run_id')
                ->whereIn('group', $chart['data'])
                ->groupByRaw($group);

            if ($sqlite) {
                $cutoff = $line ? now('UTC')->subMonthsNoOverflow(3) : now('UTC')->subHours(24);
                $query->where('run.datetime', '>=', $cutoff);
            } else {
                $query->whereRaw("`run`.`datetime` >= DATE_SUB(NOW(), INTERVAL $period)");
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

            return $query->get()->map(function ($row) use ($type) {
                $label = empty($row->name) ? $row->group : $row->final_value;

                return $type === 'donut'
                    ? ['label' => $label, 'value' => $row->total]
                    : ['y' => $label, 'a' => $row->total];
            })->all();
        });
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
