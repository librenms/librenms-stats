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

$config['cache'] = false;  // never cache this script's queries
$options = getopt('ho:l:s:i:d:v');

if (empty($options) || isset($options['h'])) {
    echo <<<EOL
\e[1mFilter Options:\e[0m
 -o <os>          Return all entries where OS is \e[1mequal\e[0m to this value
 -d <sysDescr>    Return all entries where sysDescr \e[1mcontains\e[0m this value
 -i <sysObjectID> Return all entries where sysObjectID \e[1mstarts with\e[0m this value
 -l <limit>       Limit the number of results, default 30
\e[1mOutput Options:\e[0m
 -s <field>       Only show this field to improve grouping. Valid fields: os, sysObjectID, sysDescr
 -v               Display debug output.  Can be added multiple times to increase verbosity

EOL;
    exit;
}

$verbose = isset($options['v']) ? count((array)$options['v']) : 0;
$os = isset($options['o']) ? $options['o'] : null;
$limit = isset($options['l']) ? $options['l'] : 30;
$sort = isset($options['s']) ? $options['s'] : '';
$object_id = isset($options['i']) ? $options['i'] : null;
$descr = isset($options['d']) ? $options['d'] : null;

// fetch data
$results = getDeviceInfo($os, $object_id, $descr, $sort, $limit);

// normalize sysObjectID a bit
$results = array_map(function ($entry) {
    $entry['sysObjectID'] = str_replace('enterprises', '.1.3.6.1.4.1', trim($entry['sysObjectID'], '"'));
    return $entry;
}, $results);

// determine column sizes
$os_size = array_reduce($results, function($max, $entry) {
    return max($max, strlen($entry['os']));
}, 2);
$soid_size = array_reduce($results, function($max, $entry) {
    return max($max, strlen($entry['sysObjectID']));
}, 11);

// the line format
$mask = "|%5.5s|%-{$os_size}.{$os_size}s|%-{$soid_size}.{$soid_size}s| %s |\n";

// print header
printf($mask, 'Total', 'OS', 'sysObjectID', 'sysDescr');

// print results
foreach ($results as $result) {
    printf(
        $mask,
        $result['total'],
        $result['os'],
        $result['sysObjectID'],
        isset($result['sysDescr']) ? $result['sysDescr'] : ''
    );
}
