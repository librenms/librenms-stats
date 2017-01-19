<?php

$exclude_ports = '"0","31","51","1073","4295","1092","11","130","1410","2","65","1010","1030","1049","106","1074","109","110","1100","4294967295","434","1410","2820"';

$charts = array();
$charts['draw-applications'] = array('type'=>'donut','data'=>array('applications'));
$charts['draw-snmp-version'] = array('type'=>'donut','data'=>array('snmp_version'));
$charts['draw-os'] = array('type'=>'bar','data'=>array('os'));
$charts['draw-alert_rules'] = array('type'=>'donut','data'=>array('alert_rules'));
$charts['draw-type'] = array('type'=>'bar','data'=>array('type'));
$charts['draw-total_devices'] = array('type'=>'line','data'=>array('type'),'group'=>'DATE_FORMAT(`run`.`datetime`,"%Y-%m-%d")');
$charts['draw-port_type'] = array('type'=>'bar','data'=>array('port_type'),'sql_limit'=>' AND `value` NOT IN ('.$exclude_ports.')');
$charts['draw-total_ports'] = array('type'=>'line','data'=>array('port_type'),'group'=>'DATE_FORMAT(`run`.`datetime`,"%Y-%m-%d")','sql_limit'=>' AND `value` NOT IN ('.$exclude_ports.')');
$charts['draw-port_ifspeed'] = array('type'=>'bar','data'=>array('port_ifspeed'),'sql_limit'=>' AND `value` NOT IN ('.$exclude_ports.') AND (`value` % 100 = 0 OR `value` % 100 = 10)');
$charts['draw-dbschema'] = array('type'=>'bar','data'=>array('dbschema'),'total'=>'count');
