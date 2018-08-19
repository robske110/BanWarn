<?php
declare(strict_types=1);

namespace robske_110\BanWarn\data;

use robske_110\ConfigImmitators\RAMconfigImmitator;
use robske_110\BanWarn\BanWarn;
use robske_110\BanWarn\Utils;

use pocketmine\Server;
use pocketmine\utils\Config;

class DataManager{
	private $main;
	
	private $isFullyInitialized = false;
	private $warnData;
	private $clientBan;
	private $config;
	private $error = false;

	public function __construct(BanWarn $main, Server $server){
		$this->main = $main;
		/** Begin of a critical part: Initializing the DataBases */
		try{
			@mkdir($main->getDataFolder());
			$this->warnData = new Config($main->getDataFolder() . "warnData.yml", Config::YAML, []);
			$this->clientBan = new Config($main->getDataFolder() . "clientBan.list", Config::ENUM, []);
			$this->config = new Config($main->getDataFolder() . "config.yml", Config::YAML, []);
			if(!$this->config->check()){
				Utils::warning("Your config formatiing is broken. Please check it using YAML validators.");
				Utils::log("The plugin will use the default settings. This may cause database corruption, if you have just updated the version of this plugin!");
				$this->config = new RAMconfigImmitator();
			}
			if(!$this->warnData->check() || !$this->clientBan->check()){
				Utils::critical("Your databases formats are somehow broken. Please check your databases using YAML validators.");
			}
			$this->warnData->save();
			$this->clientBan->save();
			try{
				$this->dataBaseUpgrade($this->config->get("DataBaseVersion", NULL), BanWarn::CURRENT_DATABASE_VERSION);
			}catch(\Exception $e){
				$this->error = true;
				Utils::critical("ERROR while upgrading the databases. Exception: ".$e->getMessage()." BanWarnErrorID: E_9902"); //TODO::ERR
				Utils::warning("Stack Trace: ".$e->getTraceAsString());
			}
			$this->configUpgrade($this->config->get("ConfigVersion"), BanWarn::CURRENT_CONFIG_VERSION);
			if(!$this->error){
				$this->isFullyInitialized = true;
			}
		}catch(\Exception $e){
			$this->error = true;
			Utils::critical("ERROR while initiating the databases. Exception: ".$e->getMessage()." BanWarnErrorID: E_9903"); //TODO::ERR
			Utils::warning("Stack Trace: ".$e->getTraceAsString());
		}
		/** End of a critical part: Initializing the DataBases */
	}
	
    /**
	 * @param long $clientID
	*/
	public function isClientIDbanned($clientID){
		return in_array($clientID, $this->clientBan->getAll(true));
	}
	
	private function importOldBanWarnData($warnData, $initialClientID){
		Utils::debug("Migrating warnData".print_r($warnData));
		$isDamaged = false;
		if(!is_array($warnData)){
			$this->userDataMgr->addWarnPlayer($warndata['RealPlayerName']);
		}
		if(!isset($warndata[0]["RealClientID"])){ //ClientID can be got by the key too...
			$isDamaged = true;
		}
		if(!isset($warndata[0]["RealPlayerName"]) && (!isset($warndata[1]) || !is_array($warndata[1]))){ //Useless entry... -_-
			return self::STATE_UNRECOVERABLE;
		}
		if(!isset($warndata[0]["RealPlayerName"])){ //Useful for getting WarnData => ClientID without playerName
			$isDamaged = true;
		}
		if($isDamaged){
			return self::STATE_DAMAGED;
		}
		return self::STATE_OK;
	}
	
	public function dataBaseUpgrade($version, $newVersion){
		if($version == NULL){ //User upgrading from 1.x.x to 2.x.x
			if($this->config->get("ConfigVersion") >= 4){ //TODO:detect when the user is entering stop exit or abort and then call shutdown.
				Utils::critical("Your config version says you have already used 2.0.0 while your DataBase version seems to still be at 1.x.x states! The plugin can try to upgrade the database regardless of this mismatch though! If you are unsure what to do KILL the Server in the next 20 secounds and backup your BanWarn plugin folder. Then start the server again and ignore this messsage. If this plugin doesn't continue to function normally after you performed these steps, please contact the plugin developer (robske_110) with the following ErrorID: E_9901!"); //TODO::ERR
				sleep(10);
				Utils::critical("Are you ABSOLUTELY sure you have backed up the BanWarn folder? If not, please kill the Server NOW!");
				sleep(10);
			}
			Utils::warning("This looks like the first time you are using a 2.x.x version!");
			Utils::log("Due to changing how BanWarn internally handles the connection between player names and ClientIDs and some other improvements to the databases your databases have to be upgraded.");
			/* Actual Upgrade Code */
			$oldClientBan = new Config($this->main->getDataFolder() . "clientBan.yml", Config::YAML, array());
			if($oldClientBan->check()){
				$this->clientBan->setAll([]);
				$newIndex = count($this->clientBan->getAll());
				foreach($oldClientBan->getAll() as $playerName => $clientID){
					Utils::debug($newIndex."=>".$clientID);
					$this->clientBan->set($clientID);
					$newIndex++;
				}
			}else{
				Utils::warning("Unable to restore BAN information from the old version. You can ignore this warning as BAN information can be rebuilt."); //TODO:ERR
			}
			$this->clientBan->save();
			Utils::debug(print_r($this->clientBan->getAll(true)));
			$oldWarnSys = new Config($this->main->getDataFolder() . "warnsys.yml", Config::YAML, array());
			if(!is_array($oldWarnData = $oldWarnSys->getAll())){
				throw new \Exception("Old warnData could not be restored");
			}
			foreach($this->warnData->getAll() as $initialClientID => $warnData){
				/*
				$this->importOldBanWarnData($warnData, $initialClientID);
				*/
			}
		}
	}
	
	public function configUpgrade($version, $newVersion){
		if($version === NULL){
			Utils::log("This looks like the first time you start BanWarn!");
		}
		if($version != $newVersion){
			$this->config->set('max-points-until-ban', $this->config->get('max-points-until-ban', 10));
			$this->config->set('IP-ban', $this->config->exists('IP-Ban') ? $this->config->get('IP-Ban', true) : $this->config->get('IP-ban', true));
			$this->config->set('Client-ban', $this->config->exists('Client-ban') ? $this->config->get('Client-ban', true) : $this->config->get('Client-ban', true));
			$this->config->set('notify-mode', $this->config->exists('notify-mode') ? $this->config->get('notify-mode', true) : $this->config->get('notify-mode', true));
			$this->config->set('lang', $this->config->get('lang', 'eng'));
			$this->config->set('SQL-enabled', $this->config->get('SQL-enabled', false));
			$this->config->set('SQL-data', $this->config->get('SQL-data', ['connection' => ['server' => '','port' => '', 'username' => '', 'password' => ''], 'database' => 'BanWarn']));
			$this->config->set('ConfigVersion', BanWarn::CURRENT_CONFIG_VERSION);
			$this->config->set('DataBaseVersion', BanWarn::CURRENT_DATABASE_VERSION);
		}
		$this->config->save();
	}

	/**
	 * This function is not meant to be used by extensions!
	*/
	public function banClient($clientID){
		if($this->config->get("Client-ban")){
			$this->clientBan->set($clientID);
			$this->clientBan->save(true);
		}
	}
	
	/**
	 * EXTENSION PLUGINS: DO NOT USE THIS FUNCTION!
	*/
	public function pardonClient($clientID){
		/*
		if($this->config->get("Client-ban")){
			$this->clientBan->set($clientID);
			$this->clientBan->save(true);
		}
		*/
	}
	
	/**
	 * @param $warnPlayerID WarnPlayerID
	 * @return Count of warnpoints.
	 * @todo REWRITE!
	*/
	public function getWarnPoints($warnPlayerID){
		if($tempStuffArray = $this->warnData->get($warnPlayerID) != NULL){
			$count = 0;
			foreach($tempStuffArray as $playerData){
				if($Index != 0){ //The array Index 0 is preserved to data saving.
					$count = $count + $playerData[1];
				}
				$Index++;
			}
			return $count;
		}
		return -1;
	}
	private function addWarnToPlayer($warnPlayerID, $reason, $points){
		
	}
	//TODO:move to user data mgr!
	#a small note: ID is referred to as any ID which identifies a Player. WarnPlayerID/warnPlayerID is an id which tries to represent one human being. This is of course not possible at all so it is just random guessing.
	/**
	 * If both IDs are unknown this will also create a new WarnPlayer.
	 * @param mixed $id1 ID1 (Type specified by $type1)
	 * @return mixed $id2 ID2 (Type specified by $type2)
	 * @param int $type1 Override type of $id1 [Can be: UserDataMgr::TYPE_CLIENTID UserDataMgr::TYPE_IP UserDataMgr::TYPE_PLAYERNAME]
	 * @param int $type2 Override type of $id2 [Can be: UserDataMgr::TYPE_CLIENTID UserDataMgr::TYPE_IP UserDataMgr::TYPE_PLAYERNAME]
	 * @return int $warnPlayerID
	*/
	/*
	private function addWarnPlayer($id1, $id2, $type1 = self::TYPE_DETECT, $type2 = self::TYPE_DETECT){
		if($type1 == self::TYPE_DETECT){
			$type1 = self::detectType($id1);
		}
		if($type2 == self::TYPE_DETECT){
			$type2 = self::detectType($id2);
		}
		if(switch){
			$warnPlayerID = $this->getNextID();
		}else{
			$warnPlayerID = $this->getRealPlayerIDfor($idk1);
		}
		$this->finalAddParamForPlayer($warnPlayerID, $idk1, $idk2);
		return $warnPlayerID
	}
	private function getWarnPlayerIDbyClientID($playerName){
		
	}
	private function getWarnPlayeIDbyName($playerName){
		$playerID = NULL;
		if($this->warnsys->getAll() != NULL){
			$tempStuffArray = $this->warnsys->getAll();
			foreach($tempStuffArray as $warnObject){
				if(isset($warnObject[0])){
					if(isset($warnObject[0]['RealPlayerName'])){
						$realPlayerName = $warnObject[0]['RealPlayerName'];
						if($realPlayerName == $playerName){
							$playerID = $warnObject[0]['RealClientID'];
							break;
						}
					}
				}
			}
		}
		return $playerID;
	}
	*/
}

//New array design of warnData: $banWarnPlayerID => [[UserDataMgr::TYPE_CLIENTID => $playerClientID, UserDataMgr::TYPE_IP => $playerIP, UserDataMgr::TYPE_PLAYERNAME]]

//Theory is when you know something, but it doesn't work. Practice is when something works, but you don't know why. Programmers combine theory and practice: Nothing works and they don't know why!
//Just keep doing though. Just do it. Just keep working. Never give up.