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

$output = json_decode($_REQUEST['data'],TRUE);

$uuid = $output['uuid'];

require_once "../config.php";

if (!empty($uuid) && !empty($output['data'])) {

    require '../dibi/dibi.php';
    dibi::connect(array(
                        'database'=>$config['dbname'],
                        'username'=>$config['username'],
                        'password'=>$config['password'],
                        'host'=>$config['host'],
                        'driver'=>'mysqli'));
    $result = dibi::query("SELECT `hosts_id` FROM `hosts` WHERE `uuid`=?", $uuid);
    $host_id = $result->fetchSingle();
    if (empty($host_id)) {
        dibi::query("INSERT INTO `hosts`", array('uuid'=>$uuid,'first_added'=>array('NOW()'),'last_updated'=>array('NOW()')));
        $host_id = dibi::getInsertId();
    } else {
        dibi::query("UPDATE `hosts` SET",array('last_updated'=>array('NOW()')), "WHERE `hosts_id`=?",$host_id);
    }
    $result = dibi::query("SELECT `run_id` FROM `run` WHERE `datetime` >= DATE_SUB(NOW(), INTERVAL 20 HOUR) AND `hosts_id`=?","$host_id");
    $run_id = $result->fetchSingle();
    if (true) {
        dibi::begin();
        dibi::query("INSERT INTO `run`",array('hosts_id'=>$host_id,'datetime'=>array('NOW()')));
        $run_id = dibi::getInsertId();
        foreach ($output['data'] as $group => $data) {
            foreach ($data as $entry) {
                $keys = array_keys($entry);
                $values = array_values($entry);
                $total = $values[0];
                $name = isset($keys[1]) ? $keys[1] : '';
                $value = isset($values[1]) ? $values[1] : '';

                $insert = compact('uuid', 'run_id', 'total', 'group', 'name', 'value');
                dibi::query("INSERT INTO `data`", $insert);
            }
        }

        if (isset($output['info'])) {
            dibi::query("DELETE FROM devinfo WHERE `uuid`=?", $uuid);

            foreach ($output['info'] as $info) {
                $info['uuid'] = $uuid;
                dibi::query('INSERT INTO `devinfo`', $info);
            }
        }
        dibi::commit();
    }
}
