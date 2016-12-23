<?php
require_once('../_lib/utility.php');
require_once('../_lib/draftkit.php');

$model = new DraftKitModel();
$data  = array();

switch ($_REQUEST['action']) {
	case 'get_master' :
		$data['master'] = $model->getMaster(array($_REQUEST['player_type'] => true));
		break; 
}


header('Content-Type: application/json');
echo json_encode($data);
