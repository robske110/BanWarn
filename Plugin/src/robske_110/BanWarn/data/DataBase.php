<?php

namespace robske_110\BanWarn\data;

use robske_110\BanWarn\Main;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;

class DataManager{
	
	private $warnData;
	private $clientBan;
	private $config;
	private $accountData;

	public function __construct(Main $main, Server $server){
		@mkdir($this->getDataFolder());
		$this->warnData = new Config($main->getDataFolder() . "warnData.yml", Config::YAML, []);
		$this->clientBan = new Config($main->getDataFolder() . "clientBan.list", Config::ENUM, []);
		$this->config = new Config($main->getDataFolder() . "config.yml", Config::YAML, []);
		if(!$this->config->check()){
			Utils::warning(Main::PLUGIN_MAIN_PREFIX."Your config format is somehow broken. Please check it using YAML validators.");
			Utils::log(Main::PLUGIN_MAIN_PREFIX."The plugin will use the default settings. This may cause database corruption, if you have just updated the version of this plugin!");
		}
		if(!$this->warnData->check() || !$this->clientBan->check()){
			Utils::log(Main::PLUGIN_MAIN_PREFIX );
		}
		$this->warnsys->save();
		$this->clientBan->save();
		$this->dataBaseUpgrade($this->config->get("DataBaseVersion", NULL), Main::CURRENT_DATABASE_VERSION);
		$this->configUpgrade($this->config->get("ConfigVersion"), MAIN::CURRENT_CONFIG_VERSION);
	}
    
	public function dataBaseUpgrade($version, $newVersion){
		if($version == NULL){ //User upgrading from 1.x.x to 2.x.x
			if($this->config->get("ConfigVersion") >= 4){
				Utils::critical(Main::PLUGIN_MAIN_PREFIX."Your config version says you have already used 2.0.0 while your DataBase version seems to still be at the 1.2.x state! The plugin can try to upgrade the database regardless of this mismatch though! If you are unsure what to do KILL the Server in the next 15 secounds and backup your BanWarn plugin folder. Then start the server again and ignore this messsage. If this plugin doesn't continue to function normally after you performed these steps, please contact the plugin developer (robske_110) with the following ErrorID: E_9901!"); //TODO::ERR
				sleep(10);
				Utils::critical(Main::PLUGIN_MAIN_PREFIX."Are you ABSOLUTELY sure you have backed up the BanWarn folder? If not, please kill the Server NOW!");
				sleep(5);
			}
			Utils::warning(Main::PLUGIN_MAIN_PREFIX."This looks like the first time you are using a version above 1.2.x!");
			Utils::log(Main::PLUGIN_MAIN_PREFIX."Due to changing how BanWarn internally handles the connection between player names and ClientIDs and some other improvements to the databases your databases have to be upgraded.");
			/* Actual Upgrade Code */
			$oldClientBan = new Config($this->getDataFolder() . "clientBan.yml", Config::YAML, array());
			if($oldClientBan->check()){
				$this->clientBan->setAll([]);
				foreach($oldClientBan->getAll() as $playerName => $clientID){
					$this->clientBan->setAll($this->clientBan->getAll()[] = $clientID);
				}
			}else{
				Utils::warning("Unable to restore BAN information from the old version. You can ignore this warning as BAN information can be rebuilt."); //TODO:ERR
			}
			foreach($this->warnData->getAll()){
				
			}
		}
		
	}
	
	public function configUpgrade($version, $newVersion){
		if($version != $newVersion){
			$this->config->set('max-points-until-ban', $this->config->get('max-points-until-ban', 10));
			$this->config->set('IP-ban', $this->config->exists('IP-Ban') ? $this->config->get('IP-Ban', true) : $this->config->get('IP-ban', true));
			$this->config->set('Client-ban', $this->config->exists('Client-ban') ? $this->config->get('Client-ban', true) : $this->config->get('Client-ban', true));
			$this->config->set('notify-mode', $this->config->exists('notify-mode') ? $this->config->get('notify-mode', true) : $this->config->get('notify-mode', true));
			$this->config->set('lang', $this->config->get('lang', 'eng');
			//TODO::CLEAN UP SQL TO ONE CNFG POINT
			$this->config->set('SQL-enabled', $this->config->get('SQL-enabled', false));
			$this->config->set('SQL-data', $this->config->get('SQL-data', ['connection' => ['server' => '','port' => '', 'username' => '', 'password' => ''], 'database' => 'BanWarn']));
			$this->config->set('ConfigVersion', 4);
			$this->config->set('DataBaseVersion', 0.1);
		}
		$this->config->save();
	}
	
	private function banClient($playerName, $playerID){
		if($this->config->get("Client-Ban")){
			$this->clientBan->set($playerName, $playerID);
			$this->clientBan->save(true);
		}
	}
	private function banIP($ip, $reason, $playerName = "unknown", $issuer = "unknown"){
		if($this->config->get("IP-Ban")){
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
	
	public function getWarnPoints($playerID){
		$tempStuffArray = $this->warnsys->get($playerID);
		if($tempStuffArray != NULL){
			$count = 0;
			$Index = 0;
			foreach($tempStuffArray as $playerData){
				if($Index != 0){
					$count = $count + $playerData[1];
				}
				$Index++;
			}
			return $count;
		}
		return -1;
	}
	private function getWarnPlayerByName($playerName){
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
//Theory is when you know something, but it doesn't work. Practice is when something works, but you don't know why. Programmers combine theory and practice: Nothing works and they don't know why!
//Just keep doing though. Just do it. Just keep working. Never give up.