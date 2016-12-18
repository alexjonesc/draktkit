<?php
require_once('utility.php');
define('VERSION', '01');


class DraftKitModel {
	private $servername  = "localhost";
	private $username    = "root";
	private $password    = "root";
	private $dbname      = "jaymar";
	private $error       = false;
	private $conn;


	public function __construct() {
		 $this->conn = new mysqli($this->servername, $this->username, $this->password, $this->dbname);
	}

	private function _close(){
	   mysqli_close($this->conn);
	}

	private function _runSQL($sql){
		$result =  mysqli_query($this->conn, $sql);
		$row    = mysqli_fetch_array($result, MYSQLI_ASSOC);
		return $row;
	}

	private function _fetcharray($sql){ 
        $result =  mysqli_query($this->conn, $sql);
        // $items=array();
        // while ($row = mysqli_fetch_assoc($result)) {
        //     $items[] = $row;
        // }
        // return $items;
    	return mysqli_fetch_all($result,MYSQLI_ASSOC);
    }

	public function getMaster(){
		$table = VERSION.'_master';
		$sql   = "SELECT * FROM {$table}";
		return $this->_fetcharray($sql);
	}

	public function getConfig(){
		$table = VERSION.'_config';
		$sql   = "SELECT * FROM {$table}";
		return $this->_fetcharray($sql);
	}

	public function getViews(){
		$table = VERSION.'_views';
		$sql   = "SELECT * FROM {$table}";
		return $this->_fetcharray($sql);
	}

	public function getView($id) {
		$table = VERSION.'_views';
		$sql   = "SELECT * FROM {$table} WHERE id={$id}";
		return $this->_fetcharray($sql);
	}

	public function getCategories(){
		$table  = VERSION.'_config';
		$sql    = "SELECT DISTINCT(category) FROM {$table} WHERE category IS NOT NULL AND category !=''";
		$result =  mysqli_query($this->conn, $sql);
		$data   = mysqli_fetch_all($result,MYSQLI_ASSOC); 
		$meta   = array('player_info' => array('label' => 'Player Info', 'ord' => 1),
						'17_ptp'      => array('label' => '2017 PLAYING TIME %', 'ord' => 2),
						'16_gp'       => array('label' => '2016 Games Played', 'ord' => 3),
						'17_proj_sh'  => array('label' => '2017 Projections (Standard Hitting)', 'ord' => 4),
						'17_proj_sp'  => array('label' => '2017 Projections (Standard Pitching)', 'ord' => 5),
						'162_gph'     => array('label' => '162 Game Projections (Hitting)', 'ord' => 6),
						'162_gpp'     => array('label' => '162 Game Projections (Pitching)', 'ord' => 7),
						'17_p'        => array('label' => '2017  Points', 'ord' => 8),
						'rankings'    => array('label' => 'Rankings', 'ord' => 9),
						'pos'         => array('label' => 'Positions', 'ord' => 10),
						'award_cand'  => array('label' => 'Awards Candidates', 'ord' => 11));
		$cats = array();
		foreach ($data as $d) {
			$category = $d['category'];
			if (array_key_exists($category, $meta)) $cats[] = array('category' => $category, 'label' => $meta[$category]['label'], 'ord' => $meta[$category]['ord']);
		}
		return $cats;
	}


}