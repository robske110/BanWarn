<?php

namespace robske_110\BanWarn;

use pocketmine\Server;
use pocketmine\utils\Config;

use robske_110\BanWarn\BanWarn;

class Translator{
	private $main;
	private $translationFile;
	private $fallBackFile;
	public $selectedLang;
	
	private static $langs = ['eng','deu'];
	private static $dataFolder = "";
	private static $friendlyLangNames = [
		'deu' => ['ger', 'german', 'deutsch'],
		'eng' => ['english', 'englisch']
	];
	
	public static function getLangFromFriendlyName($friendlyName){
		$friendlyName = strtolower($friendlyName);
		foreach(self::$friendlyLangNames as $lang => $friendlyNames){
			if(in_array($friendlyName,$friendlyNames,true)){
				return $lang;
			}
		}
		return false;
	}
	
	public static function getLangFileName($lang){
		return "messages-".$lang.".yml";
	}  
	
	public static function getLangFilePath($lang){
		return self::$dataFolder.self::getLangFileName($lang);
	}
	
	public function __construct(BanWarn $main, Server $server, $selectedLang){
		$this->main = $main;
		foreach(self::$langs as $lang){
			$this->main->saveResource(self::getLangFileName($lang));
		}
		self::$dataFolder = $this->main->getDataFolder();
		$this->translationFile = new Config(self::getLangFilePath($selectedLang), Config::YAML, []);
		$this->fallBackFile = new Config(self::getLangFilePath(self::$langs[1]), Config::YAML, []);
		$this->selectedLang = $selectedLang;
	}
	
	private function baseTranslate($translatedString, $inMsgVars, $translationString){
		Utils::debug($translatedString);
		if(is_string($translatedString)){
			$cnt = -1;
			foreach($inMsgVars as $inMsgVar){
				$cnt++;
				if(strpos($translatedString, "&&var".$cnt."&&") !== false){
					$translatedString = str_replace("&&var".$cnt."&&", $inMsgVar, $translatedString);
				}else{
					$translatedString = $translatedString." var".$cnt.$inMsgVar;
					Utils::debug("Failed to insert all variables into the translatedString. Data: "."transStr:'".$translationString."' varCnt:".$cnt." inMsgVar:'".$inMsgVar."' transEdString:'".$translatedString."'"); //TODO::ERR
				}
			}
			return $translatedString;
		}
		return false;
	}
	
	public function fallbackTranslate($translationString, $inMsgVars){
		$translatedString = $this->fallBackFile->getNested($translationString);
		$baseTranslateResult = $this->baseTranslate($translatedString, $inMsgVars, $translationString);
		if($baseTranslateResult !== false){
			return $baseTranslateResult;
		}else{
			Utils::warning("Failed to translate the string '".$translationString."' in the fallback lang '".self::$langs[0]."'!"); //TODO::ERR
			return $translationString;
		}
	}
	
	public function translate($translationString, ...$inMsgVars){
		$translatedString = $this->translationFile->getNested($translationString);
		$baseTranslateResult = $this->baseTranslate($translatedString, $inMsgVars, $translationString);
		if($baseTranslateResult !== false){
			return $baseTranslateResult;
		}else{
			Utils::debug("Failed to translate the string '".$translationString."' in the lang '".$this->selectedLang."'! Falling back to lang '".self::$langs[0]."'."); //TODO::ERR
			return $this->fallbackTranslate($translationString, $inMsgVars);		
		}
	}
}
//Theory is when you know something, but it doesn't work. Practice is when something works, but you don't know why. Programmers combine theory and practice: Nothing works and they don't know why!
//Just keep doing though. Just do it. Just keep working. Never give up.