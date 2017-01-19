<?php
/**
 * cache.php
 *
 * Simple caching system.  Utilizes the opcache in HHVM and PHP 7.
 * Works fine on older php, just not as fast.
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

function cache_set($key, $val)
{
    $val = var_export($val, true);
    // HHVM fails at __set_state, so just use object cast for now
    $val = str_replace('stdClass::__set_state', '(object)', $val);
    file_put_contents("/tmp/$key", '<?php $val = ' . $val . ';');
}

function cache_get($key)
{
    @include "/tmp/$key";
    return isset($val) ? $val : false;
}

function cache_get_or_fetch($key, $func)
{
    global $config;
    $file = "/tmp/$key";
    $cache_time = isset($config['cache_time']) ? $config['cache_time'] : 3600;

    if (is_file($file) && time() - filemtime($file) < $cache_time) {
        return cache_get($key);
    } else {
        $val = call_user_func($func);
        cache_set($key, $val);
        return $val;
    }
}

function db_connect() {
    if(!dibi::isConnected()) {
        global $config;
        dibi::connect(array(
            'database'=>$config['dbname'],
            'username'=>$config['username'],
            'password'=>$config['password'],
            'host'=>$config['host'],
            'driver'=>'mysqli'));
    }
}

function get_chart_def($chart_id, $data = array()) {
    global $charts;
    $chart = $charts[$chart_id];
    $type = $chart['type'];

    $def = array();
    if ($type == 'bar') {
        $def = array('element'=>$chart_id,
                     'data'=>$data,
                     'xkey'=>'y',
                     'hideHover'=>false,
                     'barRatio'=>0.4,
                     'xLabelAngle'=>90,
                     'ykeys'=>array('a'),
                     'labels'=>array('Total'));
    } elseif ($type == 'donut') {
        $def = array('element'=>$chart_id,
                     'data'=>empty($data) ? array(array('labal'=>'','data'=>'')) : $data);
    } elseif ($type == 'line') {
        $def = array('element'=>$chart_id,
                     'data'=>$data,
                     'xkey'=>'y',
                     'xLabelAngle'=>90,
                     'ykeys'=>array('a'),
                     'labels'=>array('Total'));
    }
    return $def;
}

function get_chart_data($chart_id) {
    global $charts;
    $chart = $charts[$chart_id];

    $type = $chart['type'];
    $groups = "'".implode("','",$chart['data'])."'";
    $total = isset($chart['total']) ? $chart['total'] : 'SUM';
    $group = isset($chart['group']) ? $chart['group'] : '`group`,`value`';
    $xkey = isset($chart['xkey']) ? $chart['xkey'] : 'y';
    $extra_sql = isset($chart['sql_limit']) ? $chart['sql_limit'] : '';

    if ($chart_id == 'draw-total_devices' || $chart_id == 'draw-total_ports') {
        $sql = "SELECT DISTINCT(`uuid`), $total(`total`) AS `total`, `group`,`name`,DATE_FORMAT(`run`.`datetime`,'%Y-%m-%d') AS `value` FROM `data` LEFT JOIN `run` ON `data`.`run_id`=`run`.`run_id` WHERE `group` IN ($groups) AND `run`.`datetime` >= DATE_SUB(NOW(), INTERVAL 3 MONTH) $extra_sql GROUP BY $group";
    } else {
        $sql = "SELECT DISTINCT(`uuid`), $total(`total`) AS `total`, `group`,`name`,`value` FROM `data` LEFT JOIN `run` ON `data`.`run_id`=`run`.`run_id` WHERE `run`.`datetime` >= DATE_SUB(NOW(), INTERVAL 24 HOUR) AND `group` IN ($groups) $extra_sql GROUP BY $group";
    }

    $output = cache_get_or_fetch($chart_id, function() use ($sql, $type, $chart_id, $xkey) {
        db_connect();
        $result = dibi::query($sql);
        $all = $result->fetchAll();

        $response = array();
        foreach ($all as $data) {
            if (empty($data['name'])) {
                $y = $data['group'];
            } else {
                $y = $data['value'];
            }
            if ($xkey != 'y') {
                $y = $xkey;
            }
            $a = $data['total'];
            if ($type == 'bar' || $type == 'line') {
                $response[] = array('y'=>$y,'a'=>$a);
            } elseif ($type == 'donut') {
                $response[] = array('label'=>$y,'value'=>$a);
            }
        }

        return $response;
    });

    return $output;
}
