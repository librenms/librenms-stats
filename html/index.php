<?php

require '../dibi/dibi.php';
require '../functions.php';
require '../definitions.php';
require "../config.php";
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>LibreNMS - User-submitted Stats</title>

    <!-- Bootstrap Core CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="css/agency.css" rel="stylesheet">
    <link href="css/app.css" rel="stylesheet">

    <!-- Custom Fonts -->
    <link href="font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">
    <link href="//fonts.googleapis.com/css?family=Montserrat:400,700" rel="stylesheet" type="text/css">
    <link href='//fonts.googleapis.com/css?family=Kaushan+Script' rel='stylesheet' type='text/css'>
    <link href='//fonts.googleapis.com/css?family=Droid+Serif:400,700,400italic,700italic' rel='stylesheet' type='text/css'>
    <link href='//fonts.googleapis.com/css?family=Roboto+Slab:400,100,300,700' rel='stylesheet' type='text/css'>

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
                <a class="navbar-brand page-scroll" href="#page-top">LibreNMS user stats</a>
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

$submitters = cache_get_or_fetch('submitter_count', function () {
    db_connect();
    $result = dibi::query("SELECT COUNT(DISTINCT(`uuid`)) AS `total` FROM `data` LEFT JOIN `run` ON `data`.`run_id`=`run`.`run_id` WHERE `run`.`datetime` >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
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
    list(, $title) = explode("-", $chart_id);

    echo('<div class="col-sm-6">
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <h3 class="panel-title">' . $title . '</h3>
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
    <script src="js/jquery.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="js/bootstrap.min.js"></script>

    <!-- Plugin JavaScript -->
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.3/jquery.easing.min.js"></script>
    <script src="js/classie.js"></script>
    <script src="js/cbpAnimatedHeader.js"></script>

    <!-- Contact Form JavaScript -->
    <script src="js/jqBootstrapValidation.js"></script>
    <script src="js/contact_me.js"></script>

    <!-- Custom Theme JavaScript -->
    <script src="js/agency.js"></script>

    <!-- App specific JavaScript -->
    <script src="js/app.js"></script>

    <script src="//cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.min.js"></script>

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
