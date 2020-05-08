<?php

require '../dibi/dibi.php';
require '../functions.php';
require '../definitions.php';
require "../config.php";

$charts = getChartDefintions();
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Stats - LibreNMS User Submitted Stats</title>
    <link rel="icon" type="image/png" href="/img/favicon.png">

    <!-- Bootstrap Core CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css" />

    <!-- App Specific CSS -->
    <link href="css/app.css" rel="stylesheet">

    <!-- Custom Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />
    <link href="//fonts.googleapis.com/css?family=Montserrat:400,700" rel="stylesheet" type="text/css">
    <link href='//fonts.googleapis.com/css?family=Roboto:400,100,300,700' rel='stylesheet' type='text/css'>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.css" />

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->

</head>

<body id="page-top" class="index">

    <!-- Navigation -->
    <nav class="navbar navbar-inverse navbar-fixed-top">
        <div class="container">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header page-scroll">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
<!--                <a class="navbar-brand" href="https://librenms.org"> User Stats</a>-->
                <a class="navbar-brand" href="https://librenms.org">
                    <img alt="LibreNMS" src="/img/librenms.svg">
                    User Stats
                </a>
            </div>
            <div id="navbar" class="navbar-collapse collapse">
                <ul class="nav navbar-nav navbar-right">
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="true">Jump to Graph<span class="caret"></span></a>
                        <ul class="dropdown-menu">
<?php
foreach ($charts as $chart_id => $chart) {
    echo '<li> <a href="#' . $chart['anchor'] . '">' . $chart['title'] . '</a></li>';
}
?>
                        </ul>
                    </li>
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
<?php

$submitters = cache_get_or_fetch('submitter_count', function () {
    db_connect();
    $result = dibi::query("SELECT COUNT(DISTINCT(`hosts_id`)) AS `total` FROM `run` WHERE `datetime` >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    return $result->fetchAll()[0]['total'];
});

?>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="row">
                <div class="col-sm-12">
                    <h1><span class="label label-success"><?php echo $submitters; ?></span></h1> <h3>LibreNMS installs have submitted statistics.</h3>
                 </div>
            </div>
            <div class="row">
<?php

foreach ($charts as $chart_id => $chart) {
    echo('<div class="col-sm-6">
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <h3 id="' . $chart['anchor'] . '" class="panel-title">' . $chart['title'] . '</h3>
                        </div>
                        <div class="panel-body">
                            <i id="' . $chart_id . '-spinner" class="fa fa-spinner fa-spin fa-5x fa-fw"></i>
                            <div id="' . $chart_id . '"></div>
                        </div>
                    </div>
                </div>');
}

?>

            </div>
        </div>
    </header>

    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/js/bootstrap.min.js"></script>

    <!-- App specific JavaScript -->
    <script src="js/app.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/raphael/2.2.7/raphael.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.min.js"></script>

<script>
<?php

echo "\n$( document ).ready(function() {\n";

foreach ($charts as $chart_id => $chart) {
    $data = json_encode(get_chart_def($chart_id));
    $chart_var = str_replace('-','', $chart_id);
    echo("
var $chart_var = Morris.".ucfirst($chart['type'])."(
  $data
);
fetch_chart_data($chart_var, '$chart_id');
");
}
echo "});\n";

?>
</script>

</body>

</html>
