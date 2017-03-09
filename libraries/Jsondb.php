<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
| -------------------------------------------------------------------
| JSONDB LIBRARY
| -------------------------------------------------------------------
| This file contains the JsonDB main functions.
|
| Developed by: Matías Navarro Carter
| Version: 1.1.0
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
		$this->jsondb_log = $this->ci->config->item('jsondb_log');
		
		log_message('debug', "Jsondb Class Initialized");
	}

	/**
	  * Decodes the json and returns it as an object. If $array is set to true, returns an array. 
	  *
	  * @param boolean $array
	  * @return array or object
	  * @access protected 
	  */
	protected function decode($array = false, $db = false) {
		if ($db) {
            $file = $this->jsondb_dir.$db.'.json';
        } else {
            $file = $this->jsondb_dir.$this->jsondb_active.'.json';
        }
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
	protected function encode($data, $db = false) {
        if ($db) {
            $file = $this->jsondb_dir.$db.'.json';
        } else {
            $file = $this->jsondb_dir.$this->jsondb_active.'.json';
        }
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
		$file = $this->jsondb_dir.$this->jsondb_active.'.json';
		$backup = file_get_contents($this->jsondb_dir.$this->jsondb_default.'.json');
		file_put_contents($file, $backup);
		$this->log('(restore) SUCCESS: Database restored to default');
		return;
	}

	/**
      * Writes a string in the log file. 
      *
      * @param string $message
      * @access public 
      */
	public function log($message) {
		if ($this->jsondb_log) {
			$file = $this->jsondb_dir.'log.txt';
			$format = 'd-m-Y h:m:s';
			file_put_contents($file, date($format, time()).' - '.$message."\n", FILE_APPEND);
		}
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
			$this->log('(create_table) ERROR: Cloud not create. Table '.$name.' already exists!');
			return 0;
		} else {
			$query[$name] = array();
			$query[$name]['_metadata'] = array(
					'id'		=> uniqid(),
					'created' 	=> intval(time()),
					'modified' 	=> intval(time())
				);	
			$this->encode($query);
			$this->log('(create_table) SUCCESS: Table '.$name.' (id: '.$query[$name]['_metadata']['id'].') has been created');
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
			$this->log('(drop_table) SUCCESS: Table '.$name.' has been deleted');
			return TRUE;
		} else {
			$this->log('(drop_table) ERROR: Could not drop. Table '.$name.' does not exist!');
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
			$this->log('(empty_table) SUCCESS: Table '.$name.' has been emptied');
			$this->encode($query);
			return TRUE;
		} else {
			$this->log('(empty_table) ERROR: Could not empty. Table '.$name.' does not exist!');
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
		$this->log('(get_all) SUCCESS: The whole database has been retrieved');
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
			$this->log('(get_record) ERROR: Could not retrieve. Table '.$table.' does not exist!');
			return 0;
		} else {
			foreach ($query->$table as $item) {
				if($item->$field == $value) {
					$this->log('(get_record) SUCCESS: Record (id: '.$item->id.') retrieved from '.$table);
					 return $item;
				}
		    }
		    $this->log('(get_record) ERROR: Coould not retrieve. No record from '.$table.' table matches ('.$field.': '.$value.')');
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
		if (!$query[$table]) {
			$this->log('(get_records) ERROR: Could not retrieve. Table '.$table.' does not exist!');
			return 0;
		} else {
			$result = array();
			$i = 0;
			if($field && $value) {
				foreach ($query[$table] as $item) {
					if($item[$field] == $value) {
						$result[] = $item;
                        $i++;
					}
		        }
		        if ($i == 0) {
		        	$this->log('(get_records) ERROR: Could not retrieve. No record(s) from '.$table.' table matches ('.$field.': '.$value.')');
					return 0;
		        }
		    } else {
		    	unset($query[$table]['_metadata']);
		    	unset($query[$table]['_relations']);
		    	$result = $query[$table];
		    }
			if ($sortfield) {
				$i = 0;
	        	foreach ($result as $key => $row) {
				    $thesort[$key]  = $row[$sortfield];
				    $i++;
				}    
	        	array_multisort($thesort, SORT_ASC, $result);
	        	$this->log('(get_records) SUCCESS: '.$i.' record(s) retrieved from '.$table.' table, sorted by '.$sortfield);
	        	return (object) $result;
	        } else {
                $i = 0;
	        	foreach ($result as $key => $row) {
                    $i++;
				}    
	        	$this->log('(get_records) SUCCESS: '.$i.' record(s) retrieved from '.$table.' table');
	        	return (object) $result;
	        } 
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
			$this->log('(update_record) ERROR: Could not update. Table '.$table.' does not exist!');
			return 0;
		} else {
			$i = 0;
			foreach ($query[$table] as $key => $val) {
				if ($val[$field] == $value) {
					$query[$table][$key] = array_replace($query[$table][$key], $array);
					$i++;
					$query[$table][$key]['modified'] = intval(time());
					$query[$table]['_metadata']['modified'] = intval(time());
				}
			}
			if ($i == 0) {
				$this->log('(update_record) ERROR: Could not update. No record(s) from '.$table.' table match ('.$field.': '.$value.')');
				return 0;			
			}
			$this->encode($query);
			$this->log('(update_record) SUCCESS: '.$i.' record(s) updated in '.$table.' table');
			return TRUE;
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
			$this->log('(delete_record) ERROR: Could not delete. Table '.$table.' does not exist!');
			return 0;
		} else {
			$i = 0;
			foreach($query[$table] as $key => $val) {
			   if($val[$field] == $value){
			      unset($query[$table][$key]);
			      $query[$table]['_metadata']['modified'] = intval(time());
			      $i++;
			   }
			}
			if ($i == 0) {
				$this->log('(delete_record) ERROR: Could not delete. No record(s) from '.$table.' table match ('.$field.': '.$value.')');
				return 0;			
			}
			$this->encode($query);
			$this->log('(delete_record) SUCCESS: '.$i.' record(s) deleted in '.$table.' table');
  			return TRUE;
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
            $query[$table]['_metadata']['modified'] = intval(time());
			$this->encode($query);
			$this->log('(insert_record) SUCCESS: Record inserted in '.$table.' table (id: '.$dbdata['id'].')');
			return $dbdata['id'];
		} else {
			$this->log('(insert_record) ERROR: Could not insert. Table '.$table.' does not exist!');
			return 0;
		}
	}

    /**
      * Joins multiple related records from different tables into a single multidimensional object. TODO
      *
      * @param string $table
      * @param array $data
      * @return string 'Unique id'
      * @throws Error if field or table are not found
      */
    public function get_related($table, $field, $join_table, $join_field) {
        
    }

    ######################################################################################################
    #                                                                                                    #
    # API SPECIFIC FUNCTIONS                                                                             #
    #                                                                                                    #        
    ######################################################################################################

    /**
      * Checks a token in the _schema database
      *
      * @param string $token
      * @return true on success
      */
    public function api_token_check($token) {
        $query = $this->decode(true, '_schema');
        if (!$query['tokens']) {
            $this->log('(api_token_check) ERROR: Could not check token. Table tokens does not exist!');
            $response = array(
                    'code' => 404,
                    'msg' => 'Table tokens does not exist.'
                );
            $json = json_encode($response, JSON_PRETTY_PRINT);
            http_response_code(404);
            print_r($json);
            die;
        } else {
            foreach ($query['tokens'] as $key => $val) {
                if ($val['string'] == $token) {
                    $query['tokens'][$key]['current']++;
                    $query['tokens'][$key]['lastused'] = intval(time()); // TODO Function that verifies if the API has been used.
                    $this->log('(api_token_check) SUCCESS: Token '.$token.' has been used from ip'.$_SERVER['REMOTE_ADDR'].'.');
                    $this->encode($query, '_schema');
                    return true;
                }
            }
            $this->log('(api_token_check) ERROR: Token '.$token.' is not valid!');
            $response = array(
                    'code' => 401,
                    'msg' => 'Invalid token.'
                );
            $json = json_encode($response, JSON_PRETTY_PRINT);
            http_response_code(401);
            print_r($json);
            die;
        }
    }

    /**
      * Creates a token in the _schema database
      *
      * @param string $header
      * @return boolean
      */
    public function api_create_token(){
        $query = $this->decode('_schema');

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
    public function api_get_record($table, $field, $value) {
        $query = $this->decode();
        if (!$query->$table) {
            $this->log('(api_get_record) ERROR: Could not retrieve. Table '.$table.' does not exist!');
            $response = array(
                    'code' => 404,
                    'msg' => 'Could not retrieve. Table '.$table.' does not exist!.'
                );
            $json = json_encode($response, JSON_PRETTY_PRINT);
            http_response_code(404);
            print_r($json);
            die;
        } else {
            foreach ($query->$table as $item) {
                if($item->$field == $value) {
                    $this->log('(api_get_record) SUCCESS: Record (id: '.$item->id.') retrieved from '.$table);
                    $response = array(
                        'code' => 200,
                        'msg' => 'Record (id: '.$item->id.') retrieved from '.$table,
                        'data' => $item
                    );
                    $json = json_encode($response, JSON_PRETTY_PRINT);
                    http_response_code(200);
                    print_r($json);
                    die;
                }
            }
            $this->log('(api_get_record) ERROR: Could not retrieve. No record from '.$table.' table matches ('.$field.': '.$value.')');
            $response = array(
                    'code' => 404,
                    'msg' => 'Could not retrieve. No record from '.$table.' table matches ('.$field.': '.$value.')'
                );
            $json = json_encode($response, JSON_PRETTY_PRINT);
            http_response_code(404);
            print_r($json);
            die;
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
    public function api_get_records($table, $field = false, $value = false, $sortfield = false) {
        $query = $this->decode(true);
        if (!$query[$table]) {
            $this->log('(api_get_records) ERROR: Could not retrieve. Table '.$table.' does not exist!');
            $response = array(
                    'code' => 404,
                    'msg' => 'Could not retrieve. Table '.$table.' does not exist!.'
                );
            $json = json_encode($response, JSON_PRETTY_PRINT);
            http_response_code(404);
            print_r($json);
            die;
        } else {
            $result = array();
            $i = 0;
            if($field && $value) {
                foreach ($query[$table] as $item) {
                    if($item[$field] == $value) {
                        $result[] = $item;
                        $i++;
                    }
                }
                if ($i == 0) {
                    $this->log('(api_get_records) ERROR: Could not retrieve. No record(s) from '.$table.' table matches ('.$field.': '.$value.')');
                    $response = array(
                        'code' => 404,
                        'msg' => 'Could not retrieve. No record(s) from '.$table.' table matches ('.$field.': '.$value.')'
                    );
                    $json = json_encode($response, JSON_PRETTY_PRINT);
                    http_response_code(404);
                    print_r($json);
                    die;
                }
            } else {
                unset($query[$table]['_metadata']);
                unset($query[$table]['_relations']);
                $result = $query[$table];
            }
            if ($sortfield) {
                $i = 0;
                foreach ($result as $key => $row) {
                    $thesort[$key]  = $row[$sortfield];
                    $i++;
                }    
                array_multisort($thesort, SORT_ASC, $result);
                $this->log('(api_get_records) SUCCESS: '.$i.' record(s) retrieved from '.$table.' table, sorted by '.$sortfield);
                $response = array(
                    'code' => 200,
                    'msg' => $i.' record(s) retrieved from '.$table.' table, sorted by '.$sortfield,
                    'data' => $result
                );
                $json = json_encode($response, JSON_PRETTY_PRINT);
                http_response_code(200);
                print_r($json);
                die;
            } else {
                $i = 0;
                foreach ($result as $key => $row) {
                    $i++;
                }    
                $this->log('(api_get_records) SUCCESS: '.$i.' record(s) retrieved from '.$table.' table');
                $response = array(
                    'code' => 200,
                    'msg' => $i.' record(s) retrieved from '.$table.' table',
                    'data' => $result
                );
                $json = json_encode($response, JSON_PRETTY_PRINT);
                http_response_code(200);
                print_r($json);
                die;
            } 
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
    public function api_update_record($table, $field, $value, $array) {
        $query = $this->decode(true);
        if (!$query[$table]) {
            $this->log('(api_update_record) ERROR: Could not update. Table '.$table.' does not exist!');
            $response = array(
                    'code' => 404,
                    'msg' => 'Could not update. Table '.$table.' does not exist!.'
                );
            $json = json_encode($response, JSON_PRETTY_PRINT);
            http_response_code(404);
            print_r($json);
            die;
        } else {
            $i = 0;
            foreach ($query[$table] as $key => $val) {
                if ($val[$field] == $value) {
                    $query[$table][$key] = array_replace($query[$table][$key], $array);
                    $i++;
                    $query[$table][$key]['modified'] = intval(time());
                    $query[$table]['_metadata']['modified'] = intval(time());
                    $data = $query[$table][$key];
                }
            }
            if ($i == 0) {
                $this->log('(api_update_record) ERROR: Could not update. No record(s) from '.$table.' table match ('.$field.': '.$value.')');
                $response = array(
                    'code' => 404,
                    'msg' => 'Could not update. No record(s) from '.$table.' table matches ('.$field.': '.$value.')'
                );
                $json = json_encode($response, JSON_PRETTY_PRINT);
                http_response_code(404);
                print_r($json);
                die;           
            }
            $this->encode($query);
            $this->log('(api_update_record) SUCCESS: '.$i.' record(s) updated in '.$table.' table');
            $response = array(
                'code' => 200,
                'msg' => $i.' record(s) updated in '.$table.' table',
                'data' => $data
            );
            $json = json_encode($response, JSON_PRETTY_PRINT);
            http_response_code(200);
            print_r($json);
            die;
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
    public function api_delete_record($table, $field, $value) {
        $query = $this->decode(true);
        if (!$query[$table]) {
            $this->log('(api_delete_record) ERROR: Could not delete. Table '.$table.' does not exist!');
            $response = array(
                    'code' => 404,
                    'msg' => 'Could not delete. Table '.$table.' does not exist!.'
                );
            $json = json_encode($response, JSON_PRETTY_PRINT);
            http_response_code(404);
            print_r($json);
            die;
        } else {
            $i = 0;
            foreach($query[$table] as $key => $val) {
               if($val[$field] == $value){
                  unset($query[$table][$key]);
                  $query[$table]['_metadata']['modified'] = intval(time());
                  $i++;
               }
            }
            if ($i == 0) {
                $this->log('(api_delete_record) ERROR: Could not delete. No record(s) from '.$table.' table match ('.$field.': '.$value.')');
                $response = array(
                    'code' => 404,
                    'msg' => 'Could not delete. No record(s) from '.$table.' table matches ('.$field.': '.$value.')'
                );
                $json = json_encode($response, JSON_PRETTY_PRINT);
                http_response_code(404);
                print_r($json);
                die;
            }
            $this->encode($query);
            $this->log('(api_delete_record) SUCCESS: '.$i.' record(s) deleted in '.$table.' table');
            $response = array(
                'code' => 200,
                'msg' => $i.' record(s) deleted in '.$table.' table'
            );
            $json = json_encode($response, JSON_PRETTY_PRINT);
            http_response_code(200);
            print_r($json);
            die;
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
    public function api_insert_record($table, $data) {
        $query = $this->decode(true);
        if ($query[$table]) {
            $dbdata['id'] = uniqid();
            $dbdata['created'] = intval(time());
            $dbdata['modified'] = intval(time());
            foreach ($data as $key => $val) {
                $dbdata[$key] = $val;
            }
            array_push($query[$table], $dbdata);
            $query[$table]['_metadata']['modified'] = intval(time());
            $this->encode($query);
            $this->log('(api_insert_record) SUCCESS: Record inserted in '.$table.' table (id: '.$dbdata['id'].')');
            $response = array(
                'code' => 201,
                'msg' => 'Record inserted in '.$table.' table (id: '.$dbdata['id'].')',
                'data' =>  $dbdata
            );
            $json = json_encode($response, JSON_PRETTY_PRINT);
            http_response_code(201);
            print_r($json);
            die;
        } else {
            $this->log('(api_insert_record) ERROR: Could not insert. Table '.$table.' does not exist!');
            $response = array(
                    'code' => 404,
                    'msg' => 'Could not insert. Table '.$table.' does not exist!.'
                );
            $json = json_encode($response, JSON_PRETTY_PRINT);
            http_response_code(404);
            print_r($json);
            die;
        }
    }

}

/* End of file Jsondb.php */
/* Location: ./application/libraries/Jsondb.php */