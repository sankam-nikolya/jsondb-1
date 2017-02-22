<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
| -------------------------------------------------------------------
| JSONDB CONFIG
| -------------------------------------------------------------------
| This file will contain some jsondb' settings.
|
| $config['jsondb_dir']			The directory where json files are located
| $config['jsondb_default']		The name of the default db file (for restore)
| $config['jsondb_active']		The name of the active db file
| $config['jsondb_log']			Enables or disables the log function.
|
*/

$config['jsondb_dir'] = 'app/data/';
$config['jsondb_default'] = 'default.json';
$config['jsondb_active'] = 'data.json';
$config['jsondb_log'] = true;



/* End of file jsondb.php */
/* Location: ./application/config/jsondb.php */