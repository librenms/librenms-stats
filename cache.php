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

define("CACHE_TIME", 3600);

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
    $file = "/tmp/$key";
    if (is_file($file) && time() - filemtime($file) < CACHE_TIME) {
        return cache_get($key);
    } else {
        $val = call_user_func($func);
        cache_set($key, $val);
        return $val;
    }
}