<?php

namespace robske_110\BanWarn\data;

use robske_110\ConfigImmitators\RAMconfigImmitator;
use robske_110\BanWarn\BanWarn;
use robske_110\BanWarn\Utils;

use pocketmine\Server;
use pocketmine\utils\Config;

class DataManager{
	private $main;
	
	private $warnData;
	private $clientBan;
	private $config;
	private $accountData;

	public function __construct(BanWarn $main, Server $server){
		$this->main = $main;
		@mkdir($main->getDataFolder());
		$this->warnData = new Config($main->getDataFolder() . "warnData.yml", Config::YAML, []);
		$this->clientBan = new Config($main->getDataFolder() . "clientBan.list", Config::ENUM, []);
		$this->config = new Config($main->getDataFolder() . "config.yml", Config::YAML, []);
		if(!$this->config->check()){
			Utils::warning("Your config format is somehow broken. Please check it using YAML validators.");
			Utils::log("The plugin will use the default settings. This may cause database corruption, if you have just updated the version of this plugin!");
			$this->config = new RAMconfigImmitator();
		}
		if(!$this->warnData->check() || !$this->clientBan->check()){
			Utils::critical("Your databases formats are somehow broken. Please check your databases using YAML validators.");
		}
		$this->warnData->save();
		$this->clientBan->save();
		$this->dataBaseUpgrade($this->config->get("DataBaseVersion", NULL), BanWarn::CURRENT_DATABASE_VERSION);
		$this->configUpgrade($this->config->get("ConfigVersion"), BanWarn::CURRENT_CONFIG_VERSION);
	}
    
	public function isClientIDbanned($clientID){
		return in_array($clientID, $this->clientBan->getAll(true);
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
			foreach($this->warnData->getAll() as $warnData){
				Utils::debug("Migrating warnData".$warnData);
				
			}
		}
		
	}
	
	public function configUpgrade($version, $newVersion){
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

	private function banClient($clientID){
		if($this->config->get("Client-ban")){
			$this->clientBan->set($clientID);
			$this->clientBan->save(true);
		}
	}
	
	//TODO:move this to somewhere else
	private function banIP($ip, $reason, $playerName = "unknown", $issuer = "unknown"){
		if($this->config->get("IP-ban")){
			foreach($this->getServer()->getOnlinePlayers() as $player){
				if($player->getAddress() === $ip){
					$player->kick($reason, false);
				}
			}
			$this->getServer()->getNetwork()->blockAddress($ip, -1);
			$this->getServer()->getIPBans()->addBan($ip, "BanWarnPluginBan BannedPlayer:".$playerName, null, $issuer);
		}else{
			$player->kick($reason, false);
		}
	}
	
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
	//TODO:move to user data mgr
	private function get
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
}

//New array design of warnData: $banWarnPlayerID => []

//Theory is when you know something, but it doesn't work. Practice is when something works, but you don't know why. Programmers combine theory and practice: Nothing works and they don't know why!
//Just keep doing though. Just do it. Just keep working. Never give up.