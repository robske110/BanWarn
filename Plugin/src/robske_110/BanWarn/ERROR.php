<?php

namespace robske_110\BanWarn;

use robske_110\BanWarn\Utils;

abstract class error{
	const ERR_LVL_LOW = 0;
	const ERR_LVL_NORMAL = 1;
	const ERR_LVL_HIGH = 2;
	const ERR_LVL_INSANE = 3;
	
	const ERR_PREFIX1 = [
		self::ERR_LVL_INSANE => '9',
		self::ERR_LVL_HIGH => '5',
		self::ERR_LVL_NORMAL => '1',
		self::ERR_LVL_LOW => '0',
	];
	
	const
	
	private static $errs = [
		'ConfigVer' => self::ERR_PREFIX1[self::ERR_LVL_INSANE].self::ERR_PREFIX2[DataManager::class].'01',
		self::ERR_PREFIX1[self::ERR_LVL_NORMAL].self::ERR_PREFIX2[DataManager::class].'02',
	];
    
	/**
	 * @param string $friendlyErrName
	 * 
	*/
	public static getErrorID($friendlyErrName){
		Utils::debug("ERROR ".$friendlyErrName." occured! ERR_ID".self::$errs[$friendlyErrName]);
	}
}
//Theory is when you know something, but it doesn't work. Practice is when something works, but you don't know why. Programmers combine theory and practice: Nothing works and they don't know why!
//Just keep doing though. Just do it. Just keep working. Never give up.