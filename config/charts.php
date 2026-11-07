<?php

$exclude_ports = "'0','31','51','1073','4295','1092','11','130','1410','2','65','1010','1030','1049','106','1074','109','110','1100','4294967295','434','1410','2820'";
$sqlitePhpVersion = "substr(`value`, 1, instr(`value` || '-', '-') - 1)";
$sqliteFirstDot = "instr($sqlitePhpVersion, '.')";

$charts = array();
$charts['draw-total_devices'] = array(
    'category' => 'Trends',
    'type' => 'line',
    'unit' => 'Devices',
    'per_install' => true,
    'data' => array('type'),
    'group' => '`final_value`',
);
$charts['draw-total_ports'] = array(
    'category' => 'Trends',
    'type' => 'line',
    'unit' => 'Ports',
    'per_install' => true,
    'data' => array('port_type'),
    'group' => '`final_value`',
    'sql_limit' => '`value` NOT IN (' . $exclude_ports . ')',
);
$charts['draw-percent_generic'] = array(
    'category' => 'Trends',
    'title' => 'Generic devices (%)',
    'percent' => true,
    'type' => 'line',
    'data' => array('os'),
    'total' => "SUM(CASE WHEN `value` = 'generic' THEN `total` ELSE 0 END) / SUM(`total`) * 100",
    'sqlite_total' => "100.0 * SUM(CASE WHEN `value` = 'generic' THEN `total` ELSE 0 END) / SUM(`total`)",
    'group' => '`final_value`',
);
$charts['draw-device_type'] = array(
    'category' => 'Devices & ports',
    'type' => 'bar',
    'data' => array('type'),
);
$charts['draw-os'] = array(
    'category' => 'Devices & ports',
    'title' => 'Device OS',
    'type' => 'bar',
    'data' => array('os'),
);
$charts['draw-snmp_version'] = array(
    'category' => 'Devices & ports',
    'title' => 'SNMP Version',
    'type' => 'donut',
    'data' => array('snmp_version'),
);
$charts['draw-port_type'] = array(
    'category' => 'Devices & ports',
    'type' => 'bar',
    'data' => array('port_type'),
    'sql_limit' => '`value` NOT IN (' . $exclude_ports . ')',
);
$charts['draw-port_speed'] = array(
    'category' => 'Devices & ports',
    'title' => 'Port speed (Mbps)',
    'type' => 'bar',
    'data' => array('port_ifspeed'),
);
$charts['draw-applications'] = array(
    'category' => 'Monitoring',
    'type' => 'donut',
    'data' => array('applications'),
);
$charts['draw-sensors'] = array(
    'category' => 'Monitoring',
    'type' => 'donut',
    'data' => array('sensors'),
);
$charts['draw-alert_rules'] = array(
    'category' => 'Monitoring',
    'type' => 'donut',
    'data' => array('alert_rules'),
);
$charts['draw-wireless'] = array(
    'category' => 'Monitoring',
    'title' => 'Wireless sensors',
    'type' => 'donut',
    'data' => array('wireless'),
);
$charts['draw-feature_adoption'] = array(
    'category' => 'Feature adoption',
    'title' => 'Installations using each feature',
    'type' => 'bar',
    'percent' => true,
    'adoption' => true,
);
$charts['draw-bgppeer_state'] = array(
    'category' => 'Routing & topology',
    'title' => 'BGP peer states',
    'type' => 'donut',
    'data' => array('bgppeer_state'),
);
$charts['draw-bgppeer_status'] = array(
    'category' => 'Routing & topology',
    'title' => 'BGP administrative status',
    'type' => 'donut',
    'data' => array('bgppeer_status'),
);
$charts['draw-xdp'] = array(
    'category' => 'Routing & topology',
    'title' => 'Discovery protocols',
    'type' => 'donut',
    'data' => array('xdp'),
);
$charts['draw-ospf'] = array(
    'category' => 'Routing & topology',
    'title' => 'OSPF versions',
    'type' => 'donut',
    'data' => array('ospf', 'ospfv3'),
);
$charts['draw-ospf_links'] = array(
    'category' => 'Routing & topology',
    'title' => 'OSPFv2 interface types',
    'type' => 'bar',
    'data' => array('ospf_links'),
);
$charts['draw-ospfv3_links'] = array(
    'category' => 'Routing & topology',
    'title' => 'OSPFv3 interface types',
    'type' => 'bar',
    'data' => array('ospfv3_links'),
);
$charts['draw-php_version'] = array(
    'category' => 'Versions & releases',
    'snapshot' => true,
    'unit' => 'Installations',
    'title' => 'PHP Version',
    'type' => 'donut',
    'data' => array('php_version'),
    'value_modifier' => "substring_index(substring_index(`value`, '-', 1), '.', 2)",
    'sqlite_value_modifier' => "substr($sqlitePhpVersion, 1, $sqliteFirstDot + instr(substr($sqlitePhpVersion, $sqliteFirstDot + 1) || '.', '.') - 1)",
);
$charts['draw-dbschema'] = array(
    'category' => 'Versions & releases',
    'snapshot' => true,
    'unit' => 'Installations',
    'title' => 'DB Schema',
    'type' => 'bar',
    'data' => array('dbschema'),
);

foreach (array(
    'librenms_release' => 'LibreNMS releases',
    'python_version' => 'Python versions',
    'database_version' => 'Database versions',
    'rrdtool_version' => 'RRDTool versions',
    'netsnmp_version' => 'Net-SNMP versions',
) as $group => $title) {
    $charts['draw-'.$group] = array(
        'category' => 'Versions & releases',
        'title' => $title,
        'type' => 'bar',
        'snapshot' => true,
        'unit' => 'Installations',
        'data' => array($group),
    );
}

$charts['draw-python_version'] += array(
    'value_modifier' => "substring_index(`value`, '.', 2)",
    'sqlite_value_modifier' => "substr(`value`, 1, instr(`value`, '.') + instr(substr(`value`, instr(`value`, '.') + 1) || '.', '.') - 1)",
);
$charts['draw-mysql_version'] = array(
    'category' => 'Versions & releases',
    'snapshot' => true,
    'unit' => 'Installations',
    'title' => 'MySQL/MariaDB Version',
    'type' => 'donut',
    'data' => array('mysql_version'),
    'value_modifier' => "substring_index(substring_index(`value`, '-', 1), '.', 2)",
    'sqlite_value_modifier' => "substr($sqlitePhpVersion, 1, $sqliteFirstDot + instr(substr($sqlitePhpVersion, $sqliteFirstDot + 1) || '.', '.') - 1)",
);

return $charts;
