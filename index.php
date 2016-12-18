<?php
// require_once('_lib/import.php');   
// $import = new Import();
// $import->import();
// exit;

require_once('_lib/draftkit.php');
$model = new DraftKitModel();
$data  = array();
//$data['master']     = $model->getMaster();
$data['config']     = $model->getConfig();
$data['views']      = $model->getViews();
$data['categories'] = $model->getCategories();
$data['master']     = $model->getMaster(); 
$data = json_encode($data);


?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MLB Depth Charts V02</title>
    <!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.13/css/jquery.dataTables.min.css">


    <script type="text/javascript" src="js/libs/underscore.js"></script>
    <script type="text/javascript" src="js/libs/jquery-3.1.1.min.js"></script>
    <script type="text/javascript" src="js/libs/backbone.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.10.13/js/jquery.dataTables.min.js"></script>
    
  </head>
  <body>
      <div id="content" class="container"></div>
    <script type="text/javascript" src="js/app.js"></script>
    <script type="text/javascript">
        $(function() { App.init(<?php echo $data; ?>); });
    </script>
  </body>
</html>