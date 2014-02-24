<?php
error_reporting (E_ALL | E_STRICT);
ini_set('display_errors' , 1);
 
include('./GnuPlot.php');

use Gregwar\GnuPlot\GnuPlot;

$picturefile = 'plot.png';
$datafile = 'KlimaLoggPro.csv';

// begin the session
session_start();

if (!isset($_SESSION['CREATED'])) {
    $_SESSION['CREATED'] = time();
} else if (time() - $_SESSION['CREATED'] > 1800) {
    // session started more than 30 minutes ago
    session_regenerate_id(true);    // change session ID for the current session an invalidate old session ID
    $_SESSION['CREATED'] = time();  // update creation time
}

// Get start and end date from data file
if (!isset($_SESSION['maxDate']))
{
	$lines = file($datafile);

	// First line [0] contains headers
	$dates = explode(";",$lines[1],2);
	$_SESSION['minDate'] = new DateTime( $dates[0] );

	// Last line is always empty
	$dates = explode(";",$lines[count($lines)-1],2);
	$_SESSION['maxDate'] = new DateTime( $dates[0] );
	
}
$begin = $minDate = $_SESSION['minDate'];
$end = $maxDate = $_SESSION['maxDate'];


if (!empty($_POST))
{
    // Array of post values for each different form on your page.
    $postNameArr = array('refresh');        

    // Find all of the post identifiers within $_POST
    $postIdentifierArr = array();
        
    foreach ($postNameArr as $postName)
    {
        if (array_key_exists($postName, $_POST))
        {
             $postIdentifierArr[] = $postName;
        }
    }

    // Only one form should be submitted at a time so we should have one
    // post identifier.  The die statements here are pretty harsh you may consider
    // a warning rather than this. 
    if (count($postIdentifierArr) != 1)
    {
        count($postIdentifierArr) < 1 or
            die("\$_POST contained more than one post identifier: " .
               implode(" ", $postIdentifierArr));

        // We have not died yet so we must have less than one.
        die("\$_POST did not contain a known post identifier.");
    }
         
    switch ($postIdentifierArr[0])
    {
    case 'refresh':
       	$startdate = $_POST["startdate"] . ' 00:00:00';
       	$enddate = $_POST["enddate"] . ' 23:55:00';
       	// Format dates
		$begin = new DateTime($startdate);
		//$startdate = $begin->format('Y-m-d H:i:s');
		$end = new DateTime($enddate);
		//$enddate = $end->format('Y-m-d H:i:s');
       	break;
           
    }
}
else // $_POST is empty.
{

}
?>
<!DOCTYPE HTML>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
	    <meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Klimadaten</title>
		
		<script src="js/jquery-1.10.2.js"></script>
		<script src="js/jquery-ui-1.10.4.custom.js"></script>
	
		<!-- Bootstrap -->
    	<link href="css/bootstrap.min.css" rel="stylesheet">

    	<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    	<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    	<!--[if lt IE 9]>
    	  <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    	  <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    	<![endif]-->
    
		<link href="css/ui-lightness/jquery-ui-1.10.4.custom.css" rel="stylesheet">
		<link type="text/css" href="css/jquery.datepick.css" rel="stylesheet">
		
		<script type="text/javascript">
		$(function(){
			
			$("#startdate").datepicker({
				constrainInput: true,
				minDate: "<? echo $minDate->format('Y-m-d'); ?>",
				maxDate: "<? echo $maxDate->format('Y-m-d'); ?>",
				dateFormat: "yy-mm-dd",
				onClose: function( selectedDate ) {
			        $( "#enddate" ).datepicker( "option", "minDate", selectedDate );
		    	}
			});
		
			$("#enddate").datepicker({
				constrainInput: true,
				minDate: "<? echo $minDate->format('Y-m-d'); ?>",
				maxDate: "<? echo $maxDate->format('Y-m-d'); ?>",
				dateFormat: "yy-mm-dd",
				onClose: function( selectedDate ) {
		    	    $( "#startdate" ).datepicker( "option", "maxDate", selectedDate );
      			}
			});
			$( "#startdate" ).datepicker( "setDate", "<? echo $begin->format('Y-m-d'); ?>" );
			$( "#enddate" ).datepicker( "setDate", "<? echo $end->format('Y-m-d'); ?>" );
		});
		</script>
	</head>
<body role="document" style>

<?php
$plot = new GnuPlot();

$plot
	->setFontfile('/opt/share/fonts/FreeUniversal-Regular.ttf', 9)
    ->setGraphTitle('Arbeitszimmer')
    ->setXTimeFormat("%d.%m\\n%H:%M")
    ->setTimeFormat('%Y-%m-%d %H:%M:%S')
    ->setXLabel('Datum')
    ->setXRange($begin->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'))
    ->setYLabel('Temperatur in \260C')
    ->setY2Label('Feuchte in % rH')
    ->setWidth(780)
    ->setHeight(600)
    ->setCSVSeparator(';')
    ->setCSVFile($datafile)
    ->writePngFromCSV($picturefile);
    

?>
<div class="container-fluid" role="main">
	
	<!-- Datepickers -->
	<div class="row">
  		<div class="col-md-10"><img class="img-responsive" src="<?echo $picturefile?>"></div>
  		<form role="form" class="form-horizontal" action="klimalog.php" method="post">
  			<div class="col-md-2">
				<div class="panel panel-primary">
					<div class="panel-heading">
		  				Darstellbereich
					</div>
					<div class="panel-body">
						<div class="form-group">
						<input type="date" class="form-control" name="startdate" id="startdate" placeholder="<? echo $begin->format('Y-m-d'); ?>">
						</div>
						<div class="form-group">
						<input type="date" class="form-control" name="enddate" id="enddate" placeholder="<? echo $end->format('Y-m-d'); ?>">
						</div>
						<div class="form-group">
						<button name="refresh" type="submit" class="form-control btn btn-default">Aktualisieren</button>
						</div>
					</div>
				</div>
			</div>
		</form>
	</div>

</div>

	<!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="js/bootstrap.min.js"></script>
	</body>
</html>
