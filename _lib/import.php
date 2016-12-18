<?php
require_once('utility.php');
define('VERSION', '01');
define('IMPORT_PATH', $_SERVER['DOCUMENT_ROOT'].'/MLB_DC/v02/uploads/draftkit/');   
define('CONFIG_FILE', IMPORT_PATH.'CONFIGURATION-Table 1.csv');
define('STATS_PROJECTIONS_FILE', IMPORT_PATH.'STATS PROJECTIONS-Table 1.csv');  
define('OVERALL_RANKINGS_FILE', IMPORT_PATH.'OVERALL RANKINGS-Table 1.csv');  
define('GAME_PROJECTIONS_162_FILE', IMPORT_PATH.'162 GAME PROJECTIONS-Table 1.csv'); 
define('PLAYING_TYPE_PROJECTIONS_FILE', IMPORT_PATH.'PLAYING TIME PROJECTIONS-Table 1.csv'); 
define('POSITIONS_FILE', IMPORT_PATH.'POSITIONS (2016)-Table 1.csv');
define('MASTER_FILE', IMPORT_PATH.'MASTER-Table 1.csv');
    
/* --------------------------------------------
 *  Importer
 * -------------------------------------------- */
class Import {
	private $servername  = "localhost";
	private $username    = "root";
	private $password    = "root";
	private $dbname      = "jaymar";
	private $columns     = array();
	private $error       = false;
	private $conn;
	private $file;

	public function __construct() {
	    $this->connect();
		}

		public function connect() {
	    $this->conn = new mysqli($this->servername, $this->username, $this->password, $this->dbname);
	    
	    // Check connection
	    if ($this->conn->connect_error) {
	        //die("Connection failed: " . $this->conn->connect_error);
	    } else {
	       // belch('connected');
	    }
	}

	public function close() {
	   mysqli_close($this->conn);
	}

	public function end() {
		$this->close();
	}

	public function import() {
		//$this->importConfig();
		//$this->importViews(true);
		//$this->importMaster();
		$this->close();
	}	

	public function importMaster($create=false) {
		$table  = VERSION.'_master';

		// create the table
		$file = MASTER_FILE;
	   	$cols = array();
	   	$column_names = array();
	   	if (($handle = fopen($file,"r")) !== FALSE) {
	   		$row = 1;
		while (($data = fgetcsv($handle, 0, ",")) !== FALSE) { 
	   			if ($row == 2) {
	   				foreach($data as $i => $col) {
						$key    = $this->_getNameFromNumber($i);
						$cols[] = "_{$key} VARCHAR(64)";
						$column_names[] = "_{$key}";
					}
	   			}
	   			$row++;
	   		}
	   }

		$cols   = implode(',', $cols);
		$this->conn->query("DROP TABLE IF EXISTS {$table}");
		$sql = "CREATE TABLE {$table} (id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY, {$cols})";
		if ($this->conn->query($sql) === TRUE) {
		    belch("Table {$table} created successfully");
		} else {
		    belch("Error creating table: " . $this->conn->error);
		    return;
		}
	
		$file = MASTER_FILE;
		$vals = array();
        if (($handle = fopen($file,"r")) !== FALSE) {
		    $row = 1;
		    while (($data = fgetcsv($handle, 0, ",")) !== FALSE) { 
		    	$d   = array(); 
		    	if ($row > 2) {
		    		foreach($data as $val) {
		    			$v = addslashes($val);
		    			$d[] = "'$v'";
		    		}
		    		$v      = implode(',', $d);
  		            $vals[] = "({$v})";
		    	}
		    	$row++;
		    }
		}


		$this->conn->query("TRUNCATE TABLE {$table}");
        $vals         = implode(',', $vals);
        $column_names = implode(',', $column_names);
        $sql          = "INSERT INTO {$table} ({$column_names}) VALUES {$vals};";
        if ($this->conn->query($sql) === TRUE) {
            belch("Table  {$table} updated successfully");
        } else {
            belch("Error creating table {$table}: " . $this->conn->error);
        }
	}

	public function importViews($create=false) {
		$table  = VERSION.'_views';

		// create the table
		if ($create) {
			$cols   = array();
			$cols[] = 'view_key VARCHAR(64)';
			$cols[] = 'view_subkey VARCHAR(64)';
			$cols[] = 'label VARCHAR(64)';
			$cols[] = 'col_keys VARCHAR(255)';
			$cols   = implode(',', $cols);

			$this->conn->query("DROP TABLE IF EXISTS {$table}");
			$sql = "CREATE TABLE {$table} (id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY, {$cols})";
			if ($this->conn->query($sql) === TRUE) {
	            belch("Table {$table} created successfully");
	        } else {
	            belch("Error creating table: " . $this->conn->error);
	            return;
	        }
    	}	
        


        // stats projections
        $file     = STATS_PROJECTIONS_FILE;
        $views    = array();
        if (($handle = fopen($file,"r")) !== FALSE) {
		    $row = 1;
		    while (($data = fgetcsv($handle, 0, ",")) !== FALSE) { 
		    	if ($row == 1) {
		    		$views[0]['view_key'] = $data[0];
		    		$views[0]['label']    = '';
		    		$views[1]['view_key'] = $data[0];
		    		$views[1]['label']    = '';

		    	}
		    	// hitters
		    	if ($row == 2) $views[0]['view_subkey'] = strtolower($data[0]);
		    	if ($row == 4) $views[0]['col_keys']    = implode(',', array_filter((array)$data, array($this, 'removeEmptyCol')));

		    	// pitchers
		    	if ($row == 6) $views[1]['view_subkey'] = strtolower($data[0]);
		    	if ($row == 8) $views[1]['col_keys']    = implode(',', array_filter((array)$data, array($this, 'removeEmptyCol')));
		    	if ($row == 9) {
		    		
		    		break;
		    	}
		    	$row++;
		    }
		}


		// overall rankings
		$file = OVERALL_RANKINGS_FILE;
        if (($handle = fopen($file,"r")) !== FALSE) {
		    $row = 1;
		    while (($data = fgetcsv($handle, 0, ",")) !== FALSE) { 
		    	if ($row == 1) {
		    		$views[2]['view_key']    = $data[0];
		    		$views[2]['label']       = '';
		    		$views[2]['view_subkey'] = '';
		    	}
		    	if ($row == 3) $views[2]['col_keys'] = implode(',', array_filter((array)$data, array($this, 'removeEmptyCol')));
		    	if ($row == 4) break;
		    	$row++;
		    }
		}

		

		// 162 game projections
        $file = GAME_PROJECTIONS_162_FILE;
        if (($handle = fopen($file,"r")) !== FALSE) {
		    $row = 1;
		    while (($data = fgetcsv($handle, 0, ",")) !== FALSE) { 
		    	if ($row == 1) {
		    		$view_key = $data[0];
		    		$views[3]['view_key'] = $data[0];
		    		$views[3]['label']    = '';
		    		$views[4]['view_key'] = $data[0];
		    		$views[4]['label']    = '';

		    	}
		    	// hitters
		    	if ($row == 2) $views[3]['view_subkey'] = strtolower($data[0]);
		    	if ($row == 4) $views[3]['col_keys']    = implode(',', array_filter((array)$data, array($this, 'removeEmptyCol')));

		    	// pitchers
		    	if ($row == 6) $views[4]['view_subkey'] = strtolower($data[0]);
		    	if ($row == 8) $views[4]['col_keys']    = implode(',', array_filter((array)$data, array($this, 'removeEmptyCol')));
		    	if ($row == 9) break;
		    	$row++;
		    }
		}


		// playing time projections
        $file = PLAYING_TYPE_PROJECTIONS_FILE;
        if (($handle = fopen($file,"r")) !== FALSE) {
		    $row = 1;
		    while (($data = fgetcsv($handle, 0, ",")) !== FALSE) { 
		    	if ($row == 1) {
		    		$view_key = $data[0];
		    		$views[5]['view_key'] = $data[0];
		    		$views[5]['label']    = '';
		    		$views[6]['view_key'] = $data[0];
		    		$views[6]['label']    = '';

		    	}
		    	// hitters
		    	if ($row == 2) $views[5]['view_subkey'] = strtolower($data[0]);
		    	if ($row == 4) $views[5]['col_keys']    = implode(',', array_filter((array)$data, array($this, 'removeEmptyCol')));

		    	// pitchers
		    	if ($row == 6) $views[6]['view_subkey'] = strtolower($data[0]);
		    	if ($row == 8) $views[6]['col_keys']    = implode(',', array_filter((array)$data, array($this, 'removeEmptyCol')));
		    	if ($row == 9) break;
		    	$row++;
		    }
		}

		// postions
		$file = POSITIONS_FILE;
        if (($handle = fopen($file,"r")) !== FALSE) {
		    $row = 1;
		    while (($data = fgetcsv($handle, 0, ",")) !== FALSE) { 
		    	if ($row == 1) {
		    		$views[7]['view_key']    = $data[0];
		    		$views[7]['label']       = '';
		    		$views[7]['view_subkey'] = '';
		    	}
		    	if ($row == 3) $views[7]['col_keys'] = implode(',', array_filter((array)$data, array($this, 'removeEmptyCol')));
		    	if ($row == 4) break;
		    	$row++;
		    }
		}

		// load the data
		foreach($views as $i => $view) {
 			$d = array(); 
        	foreach($view as $j => $val) {
        		$v = addslashes($val);
        		$d[] = "'$v'";
        	}
        	$v = implode(',', $d);;
            $vals[] = "({$v})"; 
        }
        $vals = implode(',', $vals);
		$column_names = 'view_key, label, view_subkey, col_keys';
		$this->conn->query("TRUNCATE TABLE {$table}");
	 	$sql  = "INSERT INTO {$table} ({$column_names}) VALUES {$vals};";
		if ($this->conn->query($sql) === TRUE) {
            belch("Table {$table} created successfully");
        } else {
            belch("Error creating table: " . $this->conn->error);
            return;
        }
	}

	public function removeEmptyCol($var) {
		return !(empty($var));
	}
	public function importConfig($create=false) {
		$table = VERSION.'_config';

        // create the table
        $create = true;
      	if ($create) {
	      	$db_cols   = array();
	      	$db_cols[] = 'col_key VARCHAR(2) UNIQUE';
	      	$db_cols[] = 'label VARCHAR(64)';
	      	$db_cols[] = 'category VARCHAR(64)';
	      	$db_cols[] = 'colors BLOB';
	      	$db_cols   = implode(',', $db_cols);
	       	$sql       = "CREATE TABLE {$table} (id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY, {$db_cols})";

	       	$this->conn->query("DROP TABLE IF EXISTS {$table}");
	       	if ($this->conn->query($sql) === TRUE) {
	            belch("Table {$table} created successfully");
	        } else {
	            belch("Error creating table: " . $this->conn->error);
	            return;
	        }
    	}

        // load the data
        $file    = CONFIG_FILE;
		$columns = array();
		if (($handle = fopen($file,"r")) !== FALSE) {
		    $row = 1;
		    while (($data = fgetcsv($handle, 0, ",")) !== FALSE) { 
		    	if ($row == 2) {
		    		foreach($data as $i => $col) {
		    			$key = $this->_getNameFromNumber($i);
		    			$columns[$i] = array('key' => $key, 'label' => $col);
		    		}
		    	}
		    	if ($row == 3) {
		    		foreach($data as $i => $col) {
		    			$columns[$i]['category'] = $col;
		    		}
		    	}

		    	if ($row == 4) {
		    		foreach($data as $i => $col) {
		    			$k = array('background', 'font-color');
		    			$v = explode(',', (string)$col);
		    			$a = array_combine($k, array_pad($v, 2, ''));
		    			$columns[$i]['colors'] = json_encode($a);
		    		}
		    	}
		    	if ($row == 4) break;
		      $row++; 
		    }        
        }

        $this->conn->query("TRUNCATE TABLE {$table}");
        $column_names = 'col_key, label, category, colors';
        $vals         = array();
        foreach($columns as $i => $col) {
 			$d = array(); 
        	foreach($col as $j => $val) {
        		$v = addslashes($val);
        		///if ($j == 'colors') belch(json_decode(stripslashes($v))); //<-- for decoding json with back slashes
        		$d[] = "'$v'";
        	}
        	$v = implode(',', $d);;
            $vals[] = "({$v})"; 
        }
        $vals = implode(',', $vals);
        $sql  = "INSERT INTO {$table} ({$column_names}) VALUES {$vals};";
        if ($this->conn->query($sql) === TRUE) {
            belch("Table  {$table} updated successfully");
        } else {
            belch("Error creating table {$table}: " . $this->conn->error);
        }
	}

	private function _getNameFromNumber($num, $index=0) {
        $index = abs($index*1); //make sure index is a positive integer
        $numeric = ($num - $index) % 26; 
        $letter = chr(65 + $numeric);
        $num2 = intval(($num -$index) / 26);
        if ($num2 > 0) {
            return $this->_getNameFromNumber($num2 - 1 + $index) . $letter;
        } else {
            return $letter;
        }
    }

    private function _sanatize_str($string='') {
       $string = str_replace(' ', '_', $string); // Replaces all spaces with hyphens.
       $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
       return preg_replace('/-+/', '-', $string); // Replaces multiple hyphens with single one.
   }   
}