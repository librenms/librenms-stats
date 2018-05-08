<?php

$exclude_ports = '"0","31","51","1073","4295","1092","11","130","1410","2","65","1010","1030","1049","106","1074","109","110","1100","4294967295","434","1410","2820"';

$charts = [
    'draw-total_devices' => [
        'type' => 'line',
        'data' => ['type'],
        'group' => 'DATE_FORMAT(`run`.`datetime`,"%Y-%m-%d")',
    ],
    'draw-total_ports' => [
        'type' => 'line',
        'data' => ['port_type'],
        'group' => 'DATE_FORMAT(`run`.`datetime`,"%Y-%m-%d")',
        'sql_limit' => ' AND `value` NOT IN (' . $exclude_ports . ')',
    ],
    'draw-device_type' => [
        'type' => 'bar',
        'data' => ['type'],
    ],
    'draw-port_type' => [
        'type' => 'bar',
        'data' => ['port_type'],
        'sql_limit' => ' AND `value` NOT IN (' . $exclude_ports . ')',
    ],
    'draw-applications' => [
        'type' => 'donut',
        'data' => ['applications'],
    ],
    'draw-sensors' => [
        'type' => 'donut',
        'data' => ['sensors'],
    ],
    'draw-wireless' => [
        'title' => 'Wireless Sensors',
        'type' => 'donut',
        'data' => ['wireless'],
    ],
    'draw-snmp_version' => [
        'title' => 'SNMP Version',
        'type' => 'donut',
        'data' => ['snmp_version'],
    ],
    'draw-php_version' => [
        'title' => 'PHP Version',
        'type' => 'donut',
        'data' => ['php_version'],
        'value_modifier' => "substring_index(substring_index(`value`, '-', 1), '.', 2)",
    ],
    'draw-mysql_version' => [
        'title' => 'MySQL/MariaDB Version',
        'type' => 'donut',
        'data' => ['mysql_version'],
        'value_modifier' => "substring_index(substring_index(`value`, '-', 1), '.', 2)",
    ],
    'draw-os' => [
        'title' => 'OS',
        'type' => 'bar',
        'data' => ['os'],
    ],
    'draw-percent_generic' => [
        'type' => 'line',
        'data' => ['os'],
        'total' => "SUM(CASE WHEN `value` = 'generic' THEN `total` ELSE 0 END) / SUM(`total`) * 100",
        'group' => "DATE_FORMAT(`run`.`datetime`,'%Y-%m-%d')",
    ],
    'draw-port_speed' => [
        'type' => 'bar',
        'data' => ['port_ifspeed'],
        'sql_limit' => ' AND `value` NOT IN (' . $exclude_ports . ') AND (`value` % 100 = 0 OR `value` % 100 = 10)',
    ],
    'draw-dbschema' => [
        'title' => 'DB Schema',
        'type' => 'bar',
        'data' => ['dbschema'],
        'total' => 'COUNT(`total`)',
    ],
    'draw-alert_rules' => [
        'type' => 'donut',
        'data' => ['alert_rules'],
    ],
];