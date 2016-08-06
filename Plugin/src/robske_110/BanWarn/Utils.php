<?php

namespace robske_110\BanWarn;

use pocketmine\Server;

abstract class Utils{
	#private static $server;
	private static $logger;
	const LOG_LVL_INFO = 0;
	const LOG_LVL_WARNING = 1;
	const LOG_LVL_CRITICAL = 2;
	const LOG_LVL_EMERGENCY = 3;
	const LOG_LVL_DEBUG = 4;
	
	const DEBUG_LVL_IMPORTED = 0;
	const DEBUG_LVL_NORMAL = 1;
	const DEBUG_LVL_PRIOR = 2;
	
	public static function init(Server $server, $debugEnabled = false){
		#self::$server = $server;
		self::$logger = $server->getLogger();
		self::$debugEnabled = $debugEnabled;
		//TODO::fopen()
	}
	
	public static function log($msg, $logLvl = self::LOG_LVL_INFO){
		switch($logLvl){
			case self::LOG_LVL_INFO: self::$logger->info($msg) break;
			case self::LOG_LVL_WARNING: self::$logger->warning($msg) break;
			case self::LOG_LVL_CRITICAL: self::$logger->critical($msg) break;
			case self::LOG_LVL_EMERGENCY: self::$logger->emergency($msg) break;
			case self::LOG_LVL_DEBUG: self::$logger->debug($msg) break;
		}
	}
	
	public static function warning($msg){
		self::log($msg, self::LOG_LVL_WARNING);
		self::debug($msg, DEBUG_LVL_IMPORTED);
	}
	public static function critical($msg){
		self::log($msg, self::LOG_LVL_CRITICAL);
		self::debug($msg, DEBUG_LVL_IMPORTED);
	}
	public static function emergency($msg){
		self::log($msg, self::LOG_LVL_EMERGENCY);
		self::debug($msg, DEBUG_LVL_IMPORTED);
	}
	public static function serverSideDebug(){
		self::log($msg, self::LOG_LVL_DEBUG);
	}
		
	public static function debug($msg, $debugLvl = self::DEBUG_LVL_NORMAL){
		self::serverSideDebug();
		if(self::$debugEnabled){
			//TODO::fwrite()
		}
	}
//Theory is when you know something, but it doesn't work. Practice is when something works, but you don't know why. Programmers combine theory and practice: Nothing works and they don't know why!
//Just keep doing though. Just do it. Just keep working. Never give up.