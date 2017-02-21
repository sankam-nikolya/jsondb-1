<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
| -------------------------------------------------------------------
| JSONDB LIBRARY
| -------------------------------------------------------------------
| This file contains the JsonDB main functions.
|
| Developed by: Matías Navarro Carter
| Version: 1.0.0
| License: MIT
|
*/

class Jsondb {

	/**
	  * Constructor
	  *
	  * @access	public
	  *
	  */
	public function __construct() {
		$this->ci =& get_instance();
		// Load config file
		$this->ci->load->config('jsondb');
		// Get options
		$this->jsondb_dir = $this->ci->config->item('jsondb_dir');
		$this->jsondb_default = $this->ci->config->item('jsondb_default');
		$this->jsondb_active = $this->ci->config->item('jsondb_active');
		
		log_message('debug', "Jsondb Class Initialized");
	}

	/**
	  * Decodes the json and returns it as an object. If $array is set to true, returns an array. 
	  *
	  * @param boolean $array
	  * @return array or object
	  * @access protected 
	  */
	protected function decode($array = false) {
		$file = $this->jsondb_dir.$this->jsondb_active;
		$json = file_get_contents($file,0,null,null);
		$json_output = json_decode($json, $array);
		return $json_output;
	}

	/**
      * Encodes the json from an array and saves it in the json file. 
      *
      * @param array $data
      * @access protected 
      */
	protected function encode($data) {
		$file = $this->jsondb_dir.$this->jsondb_active;
		$jsonData = json_encode($data, JSON_PRETTY_PRINT);
		file_put_contents($file, $jsonData);
		return;
	}

	/**
      * Restores the database file to the default. 
      *
      * @access public 
      */
	public function restore() {
		$file = $this->jsondb_dir.$this->jsondb_active;
		$backup = file_get_contents($this->jsondb_dir.$this->jsondb_default);
		file_put_contents($file, $backup);
		return;
	}

	/**
      * Creates a new table.
      *
      * @param string $name
      * @access public 
      */
	public function create_table($name) {
		$query = $this->decode(true);
		if ($query[$name]) {
			return 0;
		} else {
			$query[$name] = array();
			$query[$name]['_metadata'] = array(
					'id'		=> uniqid(),
					'created' 	=> intval(time()),
					'modified' 	=> intval(time())
				);	
			$this->encode($query);
			return $query[$name]['_metadata']['id'];
		}
	}

	/**
      * Deletes a table.
      *
      * @param string $name
      * @access public 
      */
	public function drop_table($name) {
		$query = $this->decode(true);
		if ($query[$name]) {
			unset($query[$name]);
			$this->encode($query);
			return TRUE;
		} else {
			return 0;
		}
	}

	/**
      * Empties a table.
      *
      * @param string $name
      * @access public 
      */
	public function empty_table($name) {
		$query = $this->decode(true);
		$save_id = $query[$name]['_metadata']['id'];
		$save_created = $query[$name]['_metadata']['created'];
		if ($query[$name]) {
			foreach ($query[$name] as $key => $val) {
				unset($query[$name][$key]);
			}
			$query[$name]['_metadata']['id'] = $save_id;
			$query[$name]['_metadata']['created'] = $save_created;
			$query[$name]['_metadata']['modified'] = intval(time());
			$this->encode($query);
			return TRUE;
		} else {
			return 0;
		}
	}

	/**
      * Gets all the data from the database as an object 
      *
      * @return object
      * @access public 
      */
	public function get_all() {
		$query = $this->decode();
		return $query;
	}

	/**
      * Gets one whole record from the specified $table, whose $field matches the $value sended.
      *
      * @param string $table
      * @param string $field
      * @param string $value
      * @return object
      * @throws False if field or table are not found
      */
	public function get_record($table, $field, $value) {
		$query = $this->decode();
		if (!$query->$table) {
			return 0;
		} else {
			foreach ($query->$table as $item) {
				if($item->$field == $value) {
					 return $item;
				}
		    }
		    return 0;
		}
	}

	/**
      * Gets all data from a $table. Also an specific item (or items) if optional params are present
      * Then returns the data sorted by field if optional params are present
      *
      * @param string $table
      * @param string $field (optional)
      * @param string $value (optional)
      * @return array
      * @throws Error if field or table are not found
      */
	public function get_records($table, $field = false, $value = false, $sortfield = false) {
		$query = $this->decode(true);
		$result = array();
		// Primero veo si tengo que obtener todos los datos o sólo los que ven una condición
		if($field) {
			foreach ($query[$table] as $item) {
				if($item[$field] == $value) {
					$result[] = $item;
				}
	        }  
	    } else {
	    	unset($query[$table]['_metadata']);
	    	unset($query[$table]['_relations']);
	    	$result = $query[$table];
	    }
		// Ordeno el result si hay campo. Si no, simplemente devuelvo.
		if ($sortfield) {
        	foreach ($result as $key => $row) {
			    $thesort[$key]  = $row[$sortfield];
			}    
        	array_multisort($thesort, SORT_ASC, $result);
        	return (object) $result;
        } else {
        	return (object) $result;
        } 
	}

	/**
      * Updates the entry on a $table with $array, whose $field matches the $value sended.
      *
      * @param string $table
      * @param string $field
      * @param string $value
      * @param array $array
      * @throws Error if field or table are not found
      */
	public function update_record($table, $field, $value, $array) {
		$query = $this->decode(true);
		if (!$query[$table]) {
			return 0;
		} else {
			foreach ($query[$table] as $key => $val) {
				if ($val[$field] == $value) {
					$query[$table][$key] = array_replace($query[$table][$key], $array);
					$query[$table][$key]['modified'] = intval(time());
					$query[$table]['_metadata']['modified'] = intval(time());
					$this->encode($query);
					return TRUE;
				}
			}
			return 0;		
		}
	}

	/**
      * Deletes an record from $table whose $field matches the $value sended.
      *
      * @param string $table
      * @param string $field
      * @param string $value
      * @throws Error if field or table are not found
      */
	public function delete_record($table, $field, $value) {
		$query = $this->decode(true);
		if (!$query[$table]) {
			return 0;
		} else {
			foreach($query[$table] as $key => $val) {
			   if($val[$field] == $value){
			      unset($query[$table][$key]);
			      $query[$table]['_metadata']['modified'] = intval(time());
			      $this->encode($query);
			      return TRUE;
			   }
			}
			return 0;
		}
			
		
	}

	/**
      * Creates an record on $table with $data array. Then returns the unique id of that created item.
      *
      * @param string $table
      * @param array $data
      * @return string 'Unique id'
      * @throws Error if field or table are not found
      */
	public function insert_record($table, $data) {
		$query = $this->decode(true);
		if ($query[$table]) {
			$dbdata['id'] = uniqid();
			$dbdata['created'] = intval(time());
			$dbdata['modified'] = intval(time());
			foreach ($data as $key => $val) {
				$dbdata[$key] = $val;
			}
			array_push($query[$table], $dbdata);
			$this->encode($query);
			return $dbdata['id'];
		} else {
			return 0;
		}
	}

}

/* End of file Jsondb.php */
/* Location: ./application/config/Jsondb.php */