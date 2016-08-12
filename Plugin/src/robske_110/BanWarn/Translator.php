<?php

namespace robske_110\BanWarn;

use robske_110\BanWarn\BanWarn;
use pocketmine\utils\Config;

class Translator{
	private $main;
	private $translationFile;
	private $fallBackFile;
	
	private static $langs = ['eng','deu'];
	private static $friendlyLangNames = [
		'eng' => ['ger', 'german', 'deutsch'],
		'deu' => ['english', 'englisch']
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
		return $this->main->getDataFolder().self::getLangFileName($lang);
	}
	
	public function __construct(BanWarn $main, Server $server, $selectedLang){
		$this->main = $main;
		foreach(self::$langs as $lang){
			$this->plugin->saveResource(self::getLangFileName($lang));
		}
		$this->translationFile = new Config(self::getLangFilePath($selectedLang), Config::YAML, []);
		$this->fallbackFile = new Config(self::getLangFilePath(self::$langs[1]))
		$this->selectedLang = $selectedLang;
	}
	
	private function baseTranslate($translatedString, $inMsgVars){
		if($translatedString !== NULL){
			$cnt = -1;
			foreach($inMsgVars as $inMsgVar){
				$cnt++;
				if(strpos($translatedString, "&&var".$cnt."&&") !== false){
					$translatedString = str_replace("&&var".$cnt."&&", $inMsgVar, $translatedString);
				}else{
					$translatedString = $translatedString." var".$cnt.$inMsgVar;
					Utils::debug("Failed to insert all variables into the translatedString. Data:"."transStr:".$translationString."varCnt:".$cnt."inMsgVar:".$inMsgVar."transEdString:".$translatedString); //TODO::ERR
				}
			}
			return $translatedString;
		}
		return false;
	}
	
	private function fallbackTranslate($translationString, $inMsgVars){
		$translatedString = $this->fallBackFile->getNested($translationString);
		$baseTranslateResult = $this->baseTranslate();
		if($baseTranslateResult !== false){
			return $baseTranslateResult;
		}else{
			Utils::warning("Failed to translate the string ".$translationString." in the default lang".self::$langs[0]."!"); //TODO::ERR
			return "Missing translation!"; //TODO::ERR
		}
	}
	
	public function translate($translationString, ...$inMsgVars){
		$translatedString = $this->translationFile->getNested($translationString);
		$baseTranslateResult = $this->baseTranslate();
		if($baseTranslateResult !== false){
			return $baseTranslateResult;
		}else{
			Utils::debug("Failed to translate the string ".$translationString." in the lang ".$selectedLang."! Falling back to lang".self::$langs[0]); //TODO::ERR
			return $this->fallbackTranslate($translationString, $inMsgVars);		
		}
	}
//Theory is when you know something, but it doesn't work. Practice is when something works, but you don't know why. Programmers combine theory and practice: Nothing works and they don't know why!
//Just keep doing though. Just do it. Just keep working. Never give up.