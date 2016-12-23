<?php
// require_once('_lib/import.php');   
// $import = new Import();
// $import->import();
// exit;

require_once('_lib/draftkit.php');
$model = new DraftKitModel();
$data  = array();
$data['config']        = $model->getConfig();
$data['views']         = $model->getViews();
$data['categories']    = $model->getCategories();
$data['teams_leagues'] = $model->getTeamsAndLeagues();
$data['positions']     = $model->getPositions();
$data['master']        = $model->getMaster(); 
$data_json = json_encode($data);


?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MLB Depth Charts V02</title>
    <link rel="stylesheet" href="css/reset.css">
    <!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
<!-- <link rel="stylesheet" href="https://cdn.datatables.net/1.10.13/css/jquery.dataTables.min.css"> -->
<link rel="stylesheet" href="css/styles.css">


    <script type="text/javascript" src="js/libs/underscore.js"></script>
    <script type="text/javascript" src="js/libs/jquery-3.1.1.min.js"></script>
    <script type="text/javascript" src="js/libs/backbone.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.10.13/js/jquery.dataTables.min.js"></script>
    
  </head>
  <body>
 <!--    <div id="debug">
      <label>View ID: <input type="text" id="view_id">
    </div> -->
    <div id="content" class="container-fluid">
      <div id="filters">

    <!--       <div>
            <label>Hitter/Pitchers:</label>
            <select id="playerType">
              <option value="all">All Players</option>
              <option value="pitchers">Pitchers</option>
              <option value="hitters">Hitters</option>
            </select>
          </div> -->

          <div id="positions" class="block">
            <label>Positions</label>
            <?php
            foreach ($data['positions'] as $col) {
               echo "<span class='pos' data-col_key='{$col['col_key']}'>{$col['label']}</span>";
            }
            ?>
          </div>

          <div class="block">
            <label>Categories:</label>
            <select id="rankCategories">
              <option value="5x5" data-view_id="2">5X5</option>
              <option value="4x4" data-view_id="3">4X4</option>
              <option value="6x6" data-view_id="4">6X6</option>
              <option value="points" data-view_id="5">Points</option>
            </select>
          </div>
          <!--
          <div class="block">
          <label>Teams:</label>
            <select id="teams">
              <option value="all team">All Teams</option>
              <?php
              // foreach ($data['teams_leagues'] as $team) {
              //   echo "<option>{$team}</option>";
              // }
              ?>
            </select>
          </div> -->

        <div id="viewsTabs" class="block">
            <ul id="views">
              <li data-id="1" data-view_key="default">Default</li>
              <li data-id="2" data-view_key="overall_rankings">Overall Rankings</li>
              <li data-id="3" data-view_key="stat_projections">Stats Projections</li>
    <!--           <li data-id="162 Game Projections">Overall Rankings</li>
              <li data-id="overall_rankings">Overall Rankings</li>
              <li data-id="overall_rankings">Overall Rankings</li> -->
            </ul>
        </div>

      </div><!-- #end #filters -->
      <div id="tableContainer">
      </div>
    </div><!-- #end #conted -->
    <script type="text/javascript" src="js/app.js"></script>
    <script type="text/javascript">
        $(function() { App.init(<?php echo $data_json; ?>); });
    </script>

    <style type="text/css">
      #view li {
        cursor: pointer;
      }
      #view li.selected {
        color: red;
      }
    </style>
  </body>
</html>