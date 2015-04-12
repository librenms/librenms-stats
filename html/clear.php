<?php

/*
 * LibreNMS
 *
 * Copyright (c) 2014 Neil Lathwood <https://github.com/laf/ http://www.lathwood.co.uk/fa>
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.  Please see LICENSE.txt at the top level of
 * the source code distribution for details.
 */

require '../dibi/dibi.php';
require '../config.php';
dibi::connect(array(
                    'database'=>$config['dbname'],
                    'username'=>$config['username'],
                    'password'=>$config['password'],
                    'host'=>$config['host'],
                    'driver'=>'mysqli'));
$uuid = $_REQUEST['uuid'];

if (!empty($uuid)) {
    $result = dibi::query("SELECT `hosts_id` FROM `hosts` WHERE `uuid`=?", $uuid);
    $host_id = $result->fetchSingle();
    dibi::query("DELETE FROM `run` WHERE `hosts_id` = ?","$host_id");
    dibi::query("DELETE FROM `data` WHERE `uuid` = ?","$uuid");
    dibi::query("DELETE FROM `hosts` WHERE `hosts_id` = ?","$host_id");
}
