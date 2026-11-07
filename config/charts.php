<?php

$exclude_ports = "'0','31','51','1073','4295','1092','11','130','1410','2','65','1010','1030','1049','106','1074','109','110','1100','4294967295','434','1410','2820'";
$sqlitePhpVersion = "substr(`value`, 1, instr(`value` || '-', '-') - 1)";
$sqliteFirstDot = "instr($sqlitePhpVersion, '.')";

$charts = array();
$charts['draw-applications'] = array(
    'type' => 'donut',
    'data' => array('applications'),
);
$charts['draw-sensors'] = array(
    'type' => 'donut',
    'data' => array('sensors'),
);
$charts['draw-snmp_version'] = array(
    'title' => 'SNMP Version',
    'type' => 'donut',
    'data' => array('snmp_version'),
);
$charts['draw-php_version'] = array(
    'title' => 'PHP Version',
    'type' => 'donut',
    'data' => array('php_version'),
    'value_modifier' => "substring_index(substring_index(`value`, '-', 1), '.', 2)",
    'sqlite_value_modifier' => "substr($sqlitePhpVersion, 1, $sqliteFirstDot + instr(substr($sqlitePhpVersion, $sqliteFirstDot + 1) || '.', '.') - 1)",
);
$charts['draw-total_devices'] = array(
    'type' => 'line',
    'data' => array('type'),
    'group' => '`final_value`',
);
$charts['draw-device_type'] = array(
    'type' => 'bar',
    'data' => array('type'),
);
$charts['draw-os'] = array(
    'title' => 'OS',
    'type' => 'bar',
    'data' => array('os'),
);
$charts['draw-percent_generic'] = array(
    'type' => 'line',
    'data' => array('os'),
    'total' => "SUM(CASE WHEN `value` = 'generic' THEN `total` ELSE 0 END) / SUM(`total`) * 100",
    'sqlite_total' => "100.0 * SUM(CASE WHEN `value` = 'generic' THEN `total` ELSE 0 END) / SUM(`total`)",
    'group' => '`final_value`',
);
$charts['draw-total_ports'] = array(
    'type' => 'line',
    'data' => array('port_type'),
    'group' => '`final_value`',
    'sql_limit' => '`value` NOT IN (' . $exclude_ports . ')',
);
$charts['draw-port_type'] = array(
    'type' => 'bar',
    'data' => array('port_type'),
    'sql_limit' => '`value` NOT IN (' . $exclude_ports . ')',
);
$charts['draw-port_speed'] = array(
    'type' => 'bar',
    'data' => array('port_ifspeed'),
    'sql_limit' => '`value` NOT IN (' . $exclude_ports . ') AND (`value` % 100 = 0 OR `value` % 100 = 10)',
);
$charts['draw-dbschema'] = array(
    'title' => 'DB Schema',
    'type' => 'bar',
    'data' => array('dbschema'),
    'total' => 'COUNT(`total`)',
);
$charts['draw-alert_rules'] = array(
    'type' => 'donut',
    'data' => array('alert_rules'),
);

$charts['draw-wireless'] = array(
    'title' => 'Wireless Sensors',
    'type' => 'donut',
    'data' => array('wireless'),
);
$charts['draw-python_version'] = array(
    'title' => 'Python Version',
    'type' => 'donut',
    'data' => array('python_version'),
    'value_modifier' => "substring_index(`value`, '.', 2)",
    'sqlite_value_modifier' => "substr(`value`, 1, instr(`value`, '.') + instr(substr(`value`, instr(`value`, '.') + 1) || '.', '.') - 1)",
);
$charts['draw-mysql_version'] = array(
    'title' => 'MySQL/MariaDB Version',
    'type' => 'donut',
    'data' => array('mysql_version'),
    'value_modifier' => "substring_index(substring_index(`value`, '-', 1), '.', 2)",
    'sqlite_value_modifier' => "substr($sqlitePhpVersion, 1, $sqliteFirstDot + instr(substr($sqlitePhpVersion, $sqliteFirstDot + 1) || '.', '.') - 1)",
);

return $charts;
