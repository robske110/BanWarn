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
* This class acts pretty much like the PM Config class, just with no data saving.
*/
class RAMconfigImmitator{
	/** @var array */
	private $config = [];

	private $nestedCache = [];

	/** @var boolean */
	private $correct = false;
	/** @var integer */
	private $type = Config::DETECT;

	/**
	 * @param array  $default  Array with the default values
	 * @param null   &$correct Sets correct to true if everything has been loaded correctly
	 */
	public function __construct($default = [], &$correct = null){
		$this->load($type, $default);
		$correct = $this->correct;
	}

	/**
	 * Does nothing
	 */
	public function reload(){
		return;
	}

	/**
	 * @param $str
	 *
	 * @return mixed
	 */
	public static function fixYAMLIndexes($str){
		return preg_replace("#^([ ]*)([a-zA-Z_]{1}[ ]*)\\:$#m", "$1\"$2\":", $str);
	}

	/**
	 * @param int   $type
	 * @param array $default
	 *
	 * @return bool
	 */
	public function load($type = Config::DETECT, $default = []){
		$this->correct = true;
		$this->type = (int) $type;
		if(!is_array($default)){
			$default = [];
		}
		$this->config = $default;
		$this->save();
		return true;
	}

	/**
	 * @return boolean
	 */
	public function check(){
		return $this->correct === true;
	}

	/**
	 * @param bool $async
	 *
	 * @return boolean
	 */
	public function save($async = false){
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
		return ($this->correct and isset($this->config[$k])) ? $this->config[$k] : $default;
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