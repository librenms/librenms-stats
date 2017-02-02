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

$options = getopt('o:l:v');
$verbose = isset($options['v']) ? count((array)$options['v']) : 0;
$os = isset($options['o']) ? $options['o'] : null;
$limit = isset($options['l']) ? $options['l'] : 10;


$results = getDeviceInfo($os, $limit);

$mask = "|%5.5s |%-10.10s |%-15.15s | %s |\n";
printf($mask, 'Total', 'OS', 'sysObjectID', 'sysDescr');
foreach ($results as $result) {
    printf($mask, $result['total'], $result['os'], $result['sysObjectID'], $result['sysDescr']);
}
