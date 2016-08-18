<?php

namespace robske_110\ConfigImmitators;

use robske_110\BanWarn\Main;
use pocketmine\Server;

/*
* This class was originally written for the BanWarn project.
* You are free to use or modify this class with this disclaimer left in.
* THIS CLASS HAS BEEN WRITTEN BY @robske_110 (Tim H.). DO NOT CLAIM THE CHANGES AS YOURS!
* THIS CLASS IS BASED ON PocketMine's CONFIG CLASS!
* This class has the same license as the PocketMine project. (GPLv3)
*/

/**
* This class acts pretty much like the PM Config class, just with using SQL.
*/
class SQLconfigImmitator{
	#const TYPE_DETECT = 0; //TODO
	const TYPE_NORMAL = 1;
	const TYPE_SPLIT = 2; //BETA OF TRYING TO SPLIT THE ARRAY IN THE MYSQL FIELDS (key=cell)
	
	/** @var array */
	private $config = [];
	/** @var array */
	private $nestedCache = [];

	/** @var string */
	private $url;
	/** @var mixed */
	private $port;
	/** @var string */
	private $pwd;
	
	/** @var string */
	private $table;
	/** @var int */
	private $type;

	/**
	* @param string     $url      URL to the SQL server
	* @param int        $port     Port of the SQL server
	* @param string     $db       Name of the SQL database, in wich the table should be stored
	* @param string     $table    Name of the MYSQL table in wich the config file should be stored being immitated
	* @param null|array $default  Array with the default values that will be used if the config doesn't exist
	* @param array      $userData Array with [$username,$pwd], if not set or [NULL,NULL] passed no userData is required
	* @param int        $type     A type constant (TYPE_) wich defines in wich way the class should save/read the data.
	*/
	public function __construct($url, $port, $db, $table, $default = [], $userData, $type = self::TYPE_NORMAL){
		if(!is_array($userData)){
			$userData = [NULL, NULL];
		}
		$this->initConnection($url, $port, $db, $table, $userData);
		$this->load($type, $db, $table, $default);
	}

	/**
	* Removes all the changes in memory and loads the data from SQL again
	*/
	public function reload(){
		$this->config = [];
		$this->nestedCache = [];
		$this->correct = false;
		$this->initConnection($this->url, $this->port, $this->pwd);
		$this->load($this->db, );
		#$this->load($this->file, $this->type);
	}
	
	/**
	* @param string $url
	* @param int    $port
	* @param array  $userData
	*/
	public function initConnection($url, $port, $userData){
		$this->url = $url;
		$this->port = $port;
		$this->pwd = $pwd;
		$this->sql = new \mysqli($url, $userData[0], $userData[1], , $port);
		if($this->sql->connect_error){
			throw new \RuntimeException("Failed to connect to MySQL: ".
										"errno:".$mysqli->connect_errno." ".
										"error:".$mysqli->connect_error
			);
		}
		$logger = Server::getInstance()->getLogger();
		$logger->info("MYSQL connection established: ".mysqli_get_host_info($this->sql));
	}

	/**
	* @param       $file
	* @param int   $type
	* @param array $default
	*
	* @return bool
	*/
	public function load($db, $table, $default, $type = self::DETECT){
		$this->type = (int) $type;
		
		if(!is_array($default)){
			$default = [];
		}
		if(!tableIsBuild){ //TODO
			$this->config = $default;
			$this->save();
		}else{
			//save
			if(!is_array($this->config)){
				$this->config = $default;
			}
			if($this->fillDefaults($default, $this->config) > 0){
				$this->save();
			}
		}
		return true;
	}

	/**
	* @param bool $async
	*
	* @return boolean
	*/
	public function save($async = false){
		try{
			if($async){
				//Server::getInstance()->getScheduler()->scheduleAsyncTask(new AsyncSQLconfigWriter($this->config, $this->url, $this->port, [$this->username, $this->pwd])); TODO
			}else{
				switch($this->type){
					case self::TYPE_NORMAL:
					
					case self::TYPE_SPLIT:
					//TODO
					break;
				}
				
			}
		}catch(\Throwable $e){
			$logger = Server::getInstance()->getLogger();
			$logger->critical("Could not save SQLimmitatedConfig " . $this->table . ": " . $e->getMessage());
			if(\pocketmine\DEBUG > 1){
				$logger->logException($e);
			}
		}
		return true;
	}

	/**
	* @param $k
	*
	* @return boolean|mixed
	*/
	public function __get($k){
		return $this->get($k);
	}

	/**
	* @param $k
	* @param $v
	*/
	public function __set($k, $v){
		$this->set($k, $v);
	}

	/**
	* @param $k
	*
	* @return boolean
	*/
	public function __isset($k){
		return $this->exists($k);
	}

	/**
	* @param $k
	*/
	public function __unset($k){
		$this->remove($k);
	}

	/**
	* @param $key
	* @param $value
	*/
	public function setNested($key, $value){
		$vars = explode(".", $key);
		$base = array_shift($vars);

		if(!isset($this->config[$base])){
			$this->config[$base] = [];
		}

		$base =& $this->config[$base];

		while(count($vars) > 0){
			$baseKey = array_shift($vars);
			if(!isset($base[$baseKey])){
				$base[$baseKey] = [];
			}
			$base =& $base[$baseKey];
		}

		$base = $value;
		$this->nestedCache[$key] = $value;
	}

	/**
	* @param       $key
	* @param mixed $default
	*
	* @return mixed
	*/
	public function getNested($key, $default = null){
		if(isset($this->nestedCache[$key])){
			return $this->nestedCache[$key];
		}

		$vars = explode(".", $key);
		$base = array_shift($vars);
		if(isset($this->config[$base])){
			$base = $this->config[$base];
		}else{
			return $default;
		}

		while(count($vars) > 0){
			$baseKey = array_shift($vars);
			if(is_array($base) and isset($base[$baseKey])){
				$base = $base[$baseKey];
			}else{
				return $default;
			}
		}

		return $this->nestedCache[$key] = $base;
	}

	/**
	* @param       $k
	* @param mixed $default
	*
	* @return boolean|mixed
	*/
	public function get($k, $default = false){
		return isset($this->config[$k]) ? $this->config[$k] : $default;
	}

	/**
	* @param string $k key to be set
	* @param mixed  $v value to set key
	*/
	public function set($k, $v = true){
		$this->config[$k] = $v;
		foreach($this->nestedCache as $nestedKey => $nvalue){
			if(substr($nestedKey, 0, strlen($k) + 1) === ($k . ".")){
				unset($this->nestedCache[$nestedKey]);
			}
		}
	}

	/**
	* @param array $v
	*/
	public function setAll($v){
		$this->config = $v;
	}

	/**
	* @param      $k
	* @param bool $lowercase If set, searches Config in single-case / lowercase.
	*
	* @return boolean
	*/
	public function exists($k, $lowercase = false){
		if($lowercase === true){
			$k = strtolower($k); //Convert requested  key to lower
			$array = array_change_key_case($this->config, CASE_LOWER); //Change all keys in array to lower
			return isset($array[$k]); //Find $k in modified array
		}else{
			return isset($this->config[$k]);
		}
	}

	/**
	* @param $k
	*/
	public function remove($k){
		unset($this->config[$k]);
	}

	/**
	* @param bool $keys
	*
	* @return array
	*/
	public function getAll($keys = false){
		return ($keys === true ? array_keys($this->config) : $this->config);
	}

	/**
	* @param array $defaults
	*/
	public function setDefaults(array $defaults){
		$this->fillDefaults($defaults, $this->config);
	}

	/**
	* @param $default
	* @param $data
	*
	* @return integer
	*/
	private function fillDefaults($default, &$data){
		$changed = 0;
		foreach($default as $k => $v){
			if(is_array($v)){
				if(!isset($data[$k]) or !is_array($data[$k])){
					$data[$k] = [];
				}
				$changed += $this->fillDefaults($v, $data[$k]);
			}elseif(!isset($data[$k])){
				$data[$k] = $v;
				++$changed;
			}
		}

		return $changed;
	}
}
//Theory is when you know something, but it doesn't work. Practice is when something works, but you don't know why. Programmers combine theory and practice: Nothing works and they don't know why!
//Just keep doing though. Just do it. Just keep working. Never give up.