<?php

namespace App\Http\Controllers;

use App\Services\Statistics;
use App\Services\Submissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StatsController
{
    public function dashboard(Statistics $statistics)
    {
        $charts = $statistics->charts();

        foreach ($charts as $id => &$chart) {
            $chart['anchor'] = substr($id, strlen('draw-'));
            $chart['title'] ??= ucwords(str_replace('_', ' ', $chart['anchor']));
            $chart['options'] = $this->chartOptions($id, $chart['type']);
        }

        return view('dashboard', ['charts' => $charts, 'submitters' => $statistics->submitterCount()]);
    }

    public function chart(Request $request, Statistics $statistics)
    {
        $id = $request->input('id');

        return is_string($id) && isset($statistics->charts()[$id])
            ? response()->json($statistics->chart($id))
            : response('');
    }

    public function store(Request $request, Submissions $submissions)
    {
        $data = $request->input('data');
        $payload = is_string($data) ? json_decode($data, true) : null;

        if (!is_array($payload) || empty($payload['uuid']) || empty($payload['data'])) {
            return response('');
        }

        $validator = Validator::make($payload, [
            'uuid' => ['required', 'string', 'max:64'],
            'data' => ['required', 'array'],
            'data.*' => ['array'],
            'data.*.*' => ['array', 'min:1'],
            'info' => ['nullable', 'array'],
            'info.*' => ['array'],
            'info.*.count' => ['required', 'integer'],
            'info.*.os' => ['required', 'string', 'max:64'],
            'info.*.sysDescr' => ['nullable', 'string', 'max:255'],
            'info.*.sysObjectID' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response('', 422);
        }

        foreach ($payload['data'] as $group => $entries) {
            if (mb_strlen((string) $group) > 255) {
                return response('', 422);
            }

            foreach ($entries as $entry) {
                $keys = array_keys($entry);
                $values = array_values($entry);
                $value = $values[1] ?? '';

                if (filter_var($values[0], FILTER_VALIDATE_INT) === false
                    || !is_scalar($value)
                    || mb_strlen((string) $value) > 255
                    || mb_strlen((string) ($keys[1] ?? '')) > 255) {
                    return response('', 422);
                }
            }
        }

        $submissions->record($payload['uuid'], $payload['data'], $payload['info'] ?? null);

        return response('');
    }

    public function clear(Request $request, Submissions $submissions)
    {
        $uuid = $request->input('uuid');

        if (is_string($uuid) && !empty($uuid)) {
            $submissions->clear($uuid);
        }

        return response('');
    }

    private function chartOptions(string $id, string $type): array
    {
        $options = ['element' => $id, 'data' => [], 'resize' => true];

        if ($type === 'donut') {
            $options['data'] = [['label' => '', 'data' => '']];

            return $options;
        }

        $options += [
            'xkey' => 'y',
            'xLabelAngle' => 90,
            'ykeys' => ['a'],
            'labels' => [str_contains($id, 'percent') ? 'Percent' : 'Total'],
        ];

        return $type === 'bar' ? $options + ['hideHover' => false, 'barRatio' => 0.4] : $options;
    }
}
