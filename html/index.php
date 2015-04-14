<?php

require_once "../config.php";
require '../dibi/dibi.php';
dibi::connect(array(
                    'database'=>$config['dbname'],
                    'username'=>$config['username'],
                    'password'=>$config['password'],
                    'host'=>$config['host'],
                    'driver'=>'mysqli'));
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Agency - Start Bootstrap Theme</title>

    <!-- Bootstrap Core CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="css/agency.css" rel="stylesheet">

    <!-- Custom Fonts -->
    <link href="font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">
    <link href="http://fonts.googleapis.com/css?family=Montserrat:400,700" rel="stylesheet" type="text/css">
    <link href='http://fonts.googleapis.com/css?family=Kaushan+Script' rel='stylesheet' type='text/css'>
    <link href='http://fonts.googleapis.com/css?family=Droid+Serif:400,700,400italic,700italic' rel='stylesheet' type='text/css'>
    <link href='http://fonts.googleapis.com/css?family=Roboto+Slab:400,100,300,700' rel='stylesheet' type='text/css'>

    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.css">

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->

</head>

<body id="page-top" class="index">

    <!-- Navigation -->
    <nav class="navbar navbar-inverse navbar-fixed-top">
        <div class="container">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header page-scroll">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand page-scroll" href="#page-top">LibreNMS user-submitted stats</a>
            </div>

            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav navbar-right">
                    <li><a href="http://docs.librenms.org/General/Callback-Stats-and-Privacy/">Privacy Policy</a></li>
                    <li class="hidden">
                        <a href="#page-top"></a>
                    </li>
                </ul>
            </div>
            <!-- /.navbar-collapse -->
        </div>
        <!-- /.container-fluid -->
    </nav>
<br /><br /><br />
<?php

$morris[] = array('id'=>'draw-applications','type'=>'bar','data'=>array('applications'));
$morris[] = array('id'=>'draw-snmp-version','type'=>'bar','data'=>array('snmp_version'));
$morris[] = array('id'=>'draw-os','type'=>'bar','data'=>array('os'));
$morris[] = array('id'=>'draw-type','type'=>'bar','data'=>array('type'));
$morris[] = array('id'=>'draw-total_devices','type'=>'bar','data'=>array('type'),'group'=>'`name`','xkey'=>'Devices');
$morris[] = array('id'=>'draw-port_type','type'=>'bar','data'=>array('port_type'));
$morris[] = array('id'=>'draw-total_ports','type'=>'bar','data'=>array('port_type'),'group'=>'`name`','xkey'=>'Ports');
$morris[] = array('id'=>'draw-port_ifspeed','type'=>'bar','data'=>array('port_ifspeed'));
$morris[] = array('id'=>'draw-alert_rules','type'=>'bar','data'=>array('alert_rules'));
$morris[] = array('id'=>'draw-dbschema','type'=>'bar','data'=>array('dbschema'),'total'=>'count');

?>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="row">
                <div class="col-sm-6">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title">Submitters</h3>
                        </div>
                        <div class="panel-body">
                            <div id="draw-submitters"></div>
                        </div>
                    </div>
                </div>
<?php

foreach ($morris as $chart) {
    $id = $chart['id'];
    list(,$title) = explode("-",$chart['id']);

          echo('<div class="col-sm-6">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title">'.$title.'</h3>
                        </div>
                        <div class="panel-body">
                            <div id="'.$id.'"></div>
                        </div>
                    </div>
                </div>');
}

?>

            </div>
        </div>
    </header>

    <!-- jQuery -->
    <script src="js/jquery.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="js/bootstrap.min.js"></script>

    <!-- Plugin JavaScript -->
    <script src="http://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.3/jquery.easing.min.js"></script>
    <script src="js/classie.js"></script>
    <script src="js/cbpAnimatedHeader.js"></script>

    <!-- Contact Form JavaScript -->
    <script src="js/jqBootstrapValidation.js"></script>
    <script src="js/contact_me.js"></script>

    <!-- Custom Theme JavaScript -->
    <script src="js/agency.js"></script>
    
    <script src="//cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.min.js"></script>

<?php

function grab_data($type,$div,$groups,$total='sum',$group='`group`,`value`',$xkey='y') {
    $groups = "'".implode("','",$groups)."'";
    if ($div == 'draw-submitters') {
        $result = dibi::query("SELECT COUNT(DISTINCT(`hosts_id`)) AS `total`, 'Submitters' AS `value`,'Submitters' AS `name`,'Submitters' AS `group` FROM `run` WHERE `run`.`datetime` >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    } else {
        $result = dibi::query("SELECT $total(`total`) AS `total`, `group`,`name`,`value` FROM `data` LEFT JOIN `run` ON `data`.`run_id`=`run`.`run_id` WHERE `run`.`datetime` >= DATE_SUB(NOW(), INTERVAL 24 HOUR) AND `group` IN ($groups) GROUP BY $group");
    }
    $all = $result->fetchAll();
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
        if ($type == 'bar') {
            $response[] = array('y'=>$y,'a'=>$a);
        } elseif ($type == 'donut') {
            $response[] = array('label'=>$y,'value'=>$a);
        }
    }

    if ($type == 'bar') {
        $output = array('element'=>$div,
                        'data'=>$response,
                        'xkey'=>'y',
                        'hideHover'=>false,
                        'barRatio'=>0.4,
                        'xLabelAngle'=>90,
                        'ykeys'=>array('a'),
                        'labels'=>array('Total'));
    } elseif ($type == 'donut') {
        $output = array('element'=>$div,
                        'colors'=>array('#f54757','#f96ff1','#9c7ffa','#7fe7fa','#7ffa9c','#dffa7f','#fa8b7f'),
                        'data'=>$response);
    }
    return json_encode($output);
}
//$general = grab_data('bar','draw-general',array('alert_rules','alert_templates','api_tokens','bills','cef','inventory','ipsec','pollers','pseudowires','vmware','vrfs'));
?>
<script>
<?php

$data = grab_data('bar','draw-submitters','','','','y');
echo("
Morris.Bar(
  $data
);");

foreach ($morris as $chart) {
    if (!isset($chart['total'])) {
        $chart['total'] = 'SUM';
    }
    if (!isset($chart['group'])) {
        $chart['group'] = '`group`,`value`';
    }
    if (!isset($chart['xkey'])) {
        $chart['xkey'] = 'y';
    }
    $data = grab_data($chart['type'],$chart['id'],$chart['data'],$chart['total'],$chart['group'],$chart['xkey']);
    echo("
Morris.".ucfirst($chart['type'])."(
  $data
);");

}

?>
    </script>

</body>

</html>
