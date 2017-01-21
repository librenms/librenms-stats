<?php
/**
 * cache-graphs.php
 *
 * Render data for graphs and cache it.
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
 * @copyright  2017 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

require 'dibi/dibi.php';
require "definitions.php";
require "functions.php";
require "config.php";

$options = getopt('c:fv');
$verbose = isset($options['v']) ? 1 : 0;
if ($verbose && is_array($options['v'])) {
    $verbose = count($options['v']);
}
if (isset($options['f'])) {
    $config['cache_time'] = 1;
}
if (isset($options['c'])) {
    $update = (array)$options['c'];
} else {
    $update = array_keys($charts);
}

foreach ($update as $chart_id) {
    if ($verbose) {
        echo "Executing $chart_id...\n";
    }
    $start = microtime(true);
    get_chart_data($chart_id);
    if ($verbose) {
        echo "$chart_id took " . number_format(microtime(true) - $start, 4) . ' seconds' . PHP_EOL;
    }
}
