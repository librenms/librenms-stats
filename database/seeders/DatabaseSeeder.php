<?php

namespace Database\Seeders;

use App\Services\Submissions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    private const METRICS = [
        'type' => ['type', ['network' => 35, 'server' => 18, 'storage' => 5, 'firewall' => 4]],
        'os' => ['os', ['ios' => 22, 'linux' => 16, 'junos' => 10, 'generic' => 7, 'arubaos' => 5, 'fortigate' => 4, 'eos' => 3, 'routeros' => 3, 'nxos' => 2, 'vyos' => 1]],
        'snmp_version' => ['snmpver', ['v2c' => 39, 'v3' => 26]],
        'port_type' => ['ifType', [6 => 720, 161 => 65, 53 => 30, 135 => 12]],
        'port_ifspeed' => ['ifSpeed', [100 => 80, 1000 => 520, 2500 => 30, 10000 => 95, 25000 => 18, 100000 => 5]],
        'applications' => ['app_type', ['nginx' => 8, 'mysql' => 5, 'redis' => 4, 'apache' => 3, 'unbound' => 2]],
        'sensors' => ['sensor_class', ['temperature' => 52, 'voltage' => 24, 'fanspeed' => 18, 'power' => 12, 'humidity' => 5, 'current' => 5]],
        'wireless' => ['sensor_class', ['clients' => 25, 'signal' => 31, 'frequency' => 18], 2],
        'alert_rules' => ['severity', ['critical' => 8, 'warning' => 15, 'ok' => 3]],
        'bgppeer_state' => ['bgpPeerState', ['established' => 16, 'idle' => 2, 'active' => 1], 3],
        'bgppeer_status' => ['bgpPeerAdminStatus', ['start' => 17, 'stop' => 2], 3],
        'xdp' => ['protocol', ['lldp' => 110, 'cdp' => 50]],
        'ospf' => ['ospfVersionNumber', [2 => 4], 2],
        'ospfv3' => ['ospfv3VersionNumber', [3 => 2], 4],
        'ospf_links' => ['ospfIfType', ['broadcast' => 12, 'pointToPoint' => 8], 2],
        'ospfv3_links' => ['ospfv3IfType', ['pointToPoint' => 6], 4],
        'bills' => ['bill_type', ['cdr' => 3, 'quota' => 2], 5],
        'sla' => ['rtt_type', ['icmpEcho' => 8, 'udpJitter' => 3], 3],
        'vminfo' => ['vm_type', ['vmware' => 17, 'kvm' => 6], 3],
        'vmware' => ['', ['' => 17], 3],
        'api_tokens' => ['', ['' => 3], 2],
        'ipv6_addresses' => ['', ['' => 36], 2],
        'ipsec' => ['', ['' => 5], 4],
        'vrfs' => ['', ['' => 3], 3],
        'vlans' => ['vlan_type', ['ethernet' => 14], 2],
    ];

    private const VERSIONS = [
        'librenms_release' => ['26.11.0', '26.10.1', '26.9.0'],
        'php_version' => ['8.3.12', '8.4.3', '8.5.1'],
        'python_version' => ['3.12.8', '3.13.2', '3.14.0'],
        'database_version' => ['10.11.6-MariaDB', '11.4.2-MariaDB', '8.4.3-MySQL'],
        'mysql_version' => ['10.11.6-MariaDB', '8.0.40-MySQL'],
        'rrdtool_version' => ['1.8.0', '1.9.0', '1.9.0'],
        'netsnmp_version' => ['5.9.3', '5.9.4', '5.9.4'],
        'dbschema' => ['380', '390', '395'],
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Stats fixtures require a non-production environment.');
        }

        foreach (['hosts', 'run', 'data', 'devinfo'] as $table) {
            if (!Schema::hasTable($table) || DB::table($table)->exists()) {
                throw new RuntimeException('Stats fixtures require a fresh, empty stats schema. See README.md.');
            }
        }

        $count = filter_var(env('STATS_SEED_INSTALLATIONS', 100), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($count === false) {
            throw new RuntimeException('STATS_SEED_INSTALLATIONS must be a positive integer.');
        }

        // Use the database clock so the generated timestamps match the chart windows.
        $latest = Carbon::parse(DB::selectOne('SELECT CURRENT_TIMESTAMP AS timestamp')->timestamp, 'UTC');
        $first = $latest->copy()->subMonthsNoOverflow(3)->addDay();
        $adoptionGroups = array_merge(...array_values(config('stats.adoption_features')));

        DB::transaction(function () use ($count, $first, $latest, $adoptionGroups) {
            $hosts = [];
            $devices = [];
            for ($index = 0; $index < $count; $index++) {
                $uuid = 'seed-install-'.($index + 1);
                $hosts[] = [
                    'uuid' => $uuid,
                    'first_added' => $first->toDateTimeString(),
                    'last_updated' => $latest->toDateTimeString(),
                ];
                $devices[] = [
                    'uuid' => $uuid,
                    'count' => 5 + $index % 50,
                    'os' => 'ios',
                    'sysDescr' => 'Synthetic Cisco switch',
                    'sysObjectID' => '.1.3.6.1.4.1.9.1.1745',
                ];
            }
            $this->insertRows('hosts', $hosts);
            $this->insertRows('devinfo', $devices);
            $hosts = DB::table('hosts')->orderBy('hosts_id')->pluck('uuid', 'hosts_id')->all();

            $dayNumber = 0;
            for ($day = $first->copy(); $day->lte($latest); $day->addDay(), $dayNumber++) {
                $timestamp = $day->toDateTimeString();
                $runs = [];
                foreach ($hosts as $hostId => $uuid) {
                    $runs[] = ['hosts_id' => $hostId, 'datetime' => $timestamp];
                }
                $this->insertRows('run', $runs);
                $runs = DB::table('run')->where('datetime', $timestamp)->pluck('run_id', 'hosts_id');

                $rows = [];
                $index = 0;
                foreach ($hosts as $hostId => $uuid) {
                    foreach ($this->metrics($index++, $dayNumber, $adoptionGroups) as [$group, $name, $value, $total]) {
                        $rows[] = [
                            'uuid' => $uuid,
                            'run_id' => $runs[$hostId],
                            'group' => $group,
                            'name' => $name,
                            'value' => (string) $value,
                            'total' => $total,
                        ];
                    }
                }
                $this->insertRows('data', $rows);
            }

            if (DB::getDriverName() === 'sqlite') {
                DB::statement('ANALYZE');
            }
        });
    }

    private function metrics(int $installation, int $day, array $adoptionGroups): iterable
    {
        $size = 1 + $installation % 8;
        foreach (self::METRICS as $group => $metric) {
            [$name, $values] = $metric;
            if ($installation % ($metric[2] ?? 1) !== 0) {
                if (in_array($group, $adoptionGroups, true)) {
                    yield [$group, Submissions::EMPTY_GROUP, '', 0];
                }
                continue;
            }

            foreach ($values as $value => $base) {
                $variation = crc32("$installation:$day:$group:$value") % max(1, intdiv($base, 4));
                yield [$group, $name, $value, $base * $size + $variation + intdiv($day, 14)];
            }
        }

        yield ['pollers', '', '', $installation % 4 === 0 ? 3 : 1];

        foreach (self::VERSIONS as $group => $versions) {
            yield [$group, $group === 'librenms_release' ? 'release' : 'version', $versions[$installation % count($versions)], 1];
        }
    }

    private function insertRows(string $table, array $rows): void
    {
        foreach (array_chunk($rows, 150) as $batch) {
            DB::table($table)->insert($batch);
        }
    }
}
