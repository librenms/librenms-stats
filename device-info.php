#!/usr/bin/php
<?php
/**
 * device-info.php
 *
 * Get stats on device
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    LibreNMS
 * @link       http://librenms.org
 * @copyright  2016 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

require 'dibi/dibi.php';
require 'functions.php';
require 'definitions.php';
require 'config.php';

$config['cache_time'] = 2;
$options = getopt('o:l:s:i:v');
$verbose = isset($options['v']) ? count((array)$options['v']) : 0;
$os = isset($options['o']) ? $options['o'] : null;
$limit = isset($options['l']) ? $options['l'] : 10;
$sort = isset($options['s']) ? $options['s'] : '';
$object_id = isset($options['i']) ? $options['i'] : null;


$results = getDeviceInfo($os, $object_id, $sort, $limit);
$soi_size = array_reduce($results, function($max, $entry) {
    return max($max, strlen($entry['sysObjectID']));
}, 11);

$mask = "|%5.5s|%-10.10s|%-{$soi_size}.{$soi_size}s| %s |\n";

printf($mask, 'Total', 'OS', 'sysObjectID', 'sysDescr');
foreach ($results as $result) {
    printf(
        $mask,
        $result['total'],
        $result['os'],
        $result['sysObjectID'],
        isset($result['sysDescr']) ? $result['sysDescr'] : ''
    );
}
