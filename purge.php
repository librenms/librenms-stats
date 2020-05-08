#!/usr/bin/php
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

require_once "config.php";
require 'dibi/dibi.php';
dibi::connect(array(
                    'database'=>$config['dbname'],
                    'username'=>$config['username'],
                    'password'=>$config['password'],
                    'host'=>$config['host'],
                    'driver'=>'mysqli'));
$result = dibi::query("SELECT `run_id` FROM `run` WHERE `datetime` <= NOW() - INTERVAL 6 MONTH");

foreach ($result as $run) {
    echo "Deleting " . $run->run_id . PHP_EOL;
    dibi::query("DELETE FROM `data` WHERE `run_id` = ?", $run->run_id);
    dibi::query("DELETE FROM `run` WHERE `run_id` = ?", $run->run_id);
}
