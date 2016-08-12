<?php

namespace robske_110\BanWarn;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\server\ServerCommandEvent;
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;

use robske_110\BanWarn\data\DataManager;

class BanWarn extends PluginBase implements Listener{
	const PLUGIN_MAIN_PREFIX = "[BanWarn] ";
	const CURRENT_DATABASE_VERSION = 0.1;
	const CURRENT_CONFIG_VERSION = 4.05;
	
	public $warnsys;
	public $clientBan;
	public $config;
	public $tempWPUsers;

	public function onEnable(){
		$this->getServer()->getLogger()->critical(self::PLUGIN_MAIN_PREFIX."You are using a version wich is known to not work! Please get the latest stable version from https://github.com/robske110/BanWarn/releases!");
		Utils::init($this, true); //TODO::addConfigValueForThis
		$this->dataManager = new DataManager($this, $this->getServer());
	}
	
	public function onDisable(){
		Utils::close();
	}

	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		switch($command->getName()){
			case "warn":
			if(isset($args[2])){
				if(ctype_digit($args[2])){
					if($this->getServer()->getPlayer($args[0]) instanceof Player){
						$this->addOnlineWarn($args, $sender);
					}else{
						$this->addOfflineWarn($args, $sender);
					}
				}
				else{return false;}   
			}elseif(isset($args[1])){
				$args[2] = 1;
				if($this->getServer()->getPlayer($args[0]) instanceof Player){
					$this->addOnlineWarn($args, $sender);
				}else{
					$this->addOfflineWarn($args, $sender);
				}
			}else{return false;}   
			return true;
			break; 
			case "warninfo":
			if(isset($args[0])){
				if($this->getServer()->getPlayer($args[0]) instanceof Player){
					$playerName = strtolower($this->getServer()->getPlayer($args[0])->getName());
					$playerID = $this->getServer()->getPlayer($args[0])->getClientID();
				}else{
					strtolower($playerName = $args[0]);
					$playerID = $this->getWarnPlayerByName($playerName);
				}
				if($this->warnsys->exists($playerID)){
					$this->sendMsgToSender($sender, TF::GREEN."Warnings for the player '".TF::DARK_GRAY.$playerName.TF::GREEN."', who has ".$this->countWPoints($playerID)." Points:"); //TODO::Translate
					$Index = 0;
					$tempStuffArray = $this->warnsys->get($playerID);
					foreach($tempStuffArray as $playerData){
						if($Index != 0){
							$this->sendMsgToSender($sender, TF::GREEN."Warning ".TF::WHITE.$Index.":"); //TODO::Translate
							$this->sendMsgToSender($sender, TF::GREEN."Reason: ".TF::DARK_GRAY.$playerData[0]); //TODO::Translate
							$this->sendMsgToSender($sender, TF::GREEN."Points: ".TF::DARK_GRAY.$playerData[1]); //TODO::Translate
						}		
						$Index++;
					}
				}else{
					$this->sendMsgToSender($sender, TF::RED."There are no warnings for the player '".TF::DARK_GRAY.$playerName.TF::RED."'!"); //TODO::Translate
				}
			}else{return false;}  
			return true;
			break;
			case "warnpardon":
			if(isset($args[0])){
				if($sender instanceof Player){
					$this->tempWPusers[$sender->getName()] = $args[0];
				}else{
					$this->tempWPUsers["C.O.N.S.O.L.E_moreThan16Characters"] = $args[0]; //So it won't conflict with player names
				}
				$this->sendMsgToSender($sender, TF::GREEN."You are going to remove one warn or wipe all warns from the Player '".TF::DARK_GRAY.$args[0].TF::GREEN."'!");
				$this->sendMsgToSender($sender, TF::GREEN."If you want to abort this simply type 'abort'");
				$this->sendMsgToSender($sender, TF::GREEN."Type 'all' to remove all warns.");
				$this->sendMsgToSender($sender, TF::GREEN."Type 'last' to remove the last warn.");
			}else{return false;}
			return true;
		}
	}
	
	private function addOnlineWarn($args, $sender){
		$playerName = strtolower($this->getServer()->getPlayer($args[0])->getName());
		$playerID = $this->getServer()->getPlayer($args[0])->getClientId();
		$array = $this->warnsys->get($playerID, []); //Returns an empty array if the player has no previous warnings #FunFact this is the only line written by someone else @PEMapModder :)
		$Index = count($array);
		if($Index == 0){
			$array[0] = ["RealPlayerName" => $playerName, "RealClientID" => $playerID];
			$Index++;
		}
		$array[$Index] = [$args[1], $args[2]];
		$this->warnsys->set($playerID, $array);
		$this->warnsys->save(true);
		$tempMsgS = TF::GREEN . "The player '".TF::DARK_GRAY.$playerName.TF::GREEN."' has been warned with the reason '".TF::DARK_GRAY.$args[1].TF::GREEN."' and ".TF::DARK_GRAY.$args[2].TF::GREEN." point(s)! He/she now has a total of ".TF::DARK_GRAY.$this->countWPoints($playerID).TF::GREEN." point(s)."; //TODO::Translate
		$tempMsgToP = TF::RED . "YOU HAVE BEEN WARNED BY '".$sender->getName()."' WITH THE REASON '".TF::DARK_GRAY.$args[1].TF::RED."' and ".TF::DARK_GRAY.$args[2].TF::RED." POINT(S)! YOU NOW HAVE A TOTAL OF ".TF::DARK_GRAY.$this->countWPoints($playerID).TF::RED." POINT(S)! WITH ".TF::DARK_GRAY.$this->config->get("max-points-until-ban").TF::RED." POINTS YOU WILL BE BANNED!"; //TODO::Translate
		$this->getServer()->getPlayer($args[0])->sendMessage($tempMsgToP);
		if($this->config->get("Notify-Mode") == 1){
			$this->sendMsgToSender($sender, $tempMsgS);
			if($this->getTypeAsNameOfSender($sender) != "CONSOLE"){
				$this->getServer()->getLogger()->info($tempMsgS);
			}
		}elseif($this->config->get("Notify-Mode") == 2){
			$this->getServer()->broadcastMessage($tempMsgS);
		}
		if($this->countWPoints($playerID) >= $this->config->get("max-points-until-ban")){
			$reason = "";
			$tempStuffArray = $this->warnsys->get($playerID);
			$Index = 0;
			foreach($tempStuffArray as $playerData){
				if($Index != 0){
					$reason = $reason.TF::GREEN."W ".TF::WHITE.$Index.": ".TF::GREEN."Reason: ".TF::GOLD.$playerData[0]."\n"; //TODO::Translate
				}
				$Index++;
			}
			$reason = "You are banned: \n".$reason; //TODO::Translate
			//IP_Ban
			$ip = $this->getServer()->getPlayer($args[0])->getAddress();
			$this->banIP($ip, $reason, $playerName, $this->getTypeAsNameOfSender($sender));
			//Client-Ban
			$this->banClient($playerName, $playerID);
		}
	}
	private function addOfflineWarn($args, $sender){
		$playerName = strtolower($args[0]);
		$playerID = $this->getWarnPlayerByName($playerName);
		if($playerID != NULL){
			$array = $this->warnsys->get($playerID, []); //Returns an empty array if the player has no previous warnings
			$Index = count($array);
			if($Index == 0){
				$array[0] = ["RealPlayerName" => $playerName, "RealClientID" => $playerID];
				$Index++;
			}
			$array[$Index] = [$args[1], $args[2]];
			$this->warnsys->set($playerID, $array);
			$this->warnsys->save(true);
			$tempMsgS = TF::GREEN . "The player '".TF::DARK_GRAY.$playerName.TF::GREEN."' has been warned with the reason '".TF::DARK_GRAY.$args[1].TF::GREEN."' and ".TF::DARK_GRAY.$args[2].TF::GREEN." Point(s)! He now has a total of ".TF::DARK_GRAY.$this->countWPoints($playerID).TF::GREEN." Points."; //TODO::Translate
			if($this->config->get("Notify-Mode") == 1){
				$this->sendMsgToSender($sender, $tempMsgS);
				if($this->getTypeAsNameOfSender($sender) != "CONSOLE"){
					$this->getServer()->getLogger()->info($tempMsgS);
				}
			}elseif($this->config->get("Notify-Mode") == 2){
				$this->getServer()->broadcastMessage($tempMsgS);
			}
			if($this->countWPoints($playerID) >= $this->config->get("max-points-until-ban")){
				$this->sendMsgToSender($sender, TF::RED."The player '".TF::DARK_GRAY.$playerName.TF::RED."' will be banned on his next login!"); //TODO::Translate
			}
		}else{
			$this->sendMsgToSender($sender, TF::RED."Unfortunaly '".TF::DARK_GRAY.$playerName.TF::RED."' could not be warned, as he/she is not online and has no prevoius warnings!"); //TODO::Translate //TODO::FixThis (By using player.dat maybe (WaitingForPM)? HEY, POCKETMINE:WHY ISN'T THERE AN EASY SOLOUTION FOR THIS!)
		}
	}
	
	private function wipePlayer($playerName){
		$remSuceededLvls = ["warnsys" => false, "clientBan" => false, "ipBan" => false];
		$playerID = $this->getWarnPlayerByName($playerName);
		if($this->warnsys->exists($playerID)){
			$remSuceededLvls["warnsys"] = true;
			$this->warnsys->remove($playerID);
			$this->warnsys->save(true);
		}
		if($this->clientBan->exists($playerName)){
			$remSuceededLvls["clientBan"] = true;
			$this->clientBan->remove($playerName);
			$this->clientBan->save(true);
		}
		foreach($this->getServer()->getIPBans()->getEntries() as $ipBanObject){
			if($ipBanObject->getReason() == "BanWarnPluginBan BannedPlayer:".$playerName){
				$ip = $ipBanObject->getName();
				$this->getServer()->getIPBans()->remove($ip);
				$remSuceededLvls["ipBan"] = true;
			}
		}
		return $remSuceededLvls;
	}
	private function removeLastWarn($playerName){
		$remSuceededLvls = ["warnsys" => false, "clientBan" => false, "ipBan" => false];
		$playerID = $this->getWarnPlayerByName($playerName);
		if($this->warnsys->exists($playerID)){
			$warnData = $this->warnsys->get($playerID);
			$index = count($array);
			$index--;
			$array[$index] = NULL;
			$this->warnsys->set($playerID, $array);
			$this->warnsys->save(true);
		}
		if($this->countWPoints($playerID) < $this->config->get("max-points-until-ban")){
			if($this->clientBan->exists($playerName)){
				$remSuceededLvls["clientBan"] = true;
				$this->clientBan->remove($playerName);
				$this->clientBan->save(true);
			}
			foreach($this->getServer()->getIPBans()->getEntries() as $ipBanObject){
				if($ipBanObject->getReason() == "BanWarnPluginBan BannedPlayer:".$playerName){
					$ip = $ipBanObject->getName();
					$this->getServer()->getIPBans()->remove($ip);
					$remSuceededLvls["ipBan"] = true;
				}
			}
		}
		return $remSuceededLvls;
	}
	private function sendMsgToSender($sender, $message){
		if($sender instanceof Player){
			$sender->getPlayer()->sendMessage($message);
		}else{
			$this->getServer()->getLogger()->info(PLUGIN_MAIN_PREFIX.$message);
		}
	}
	public function countWPoints($playerID){
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
	private function getTypeAsNameOfSender($sender){
		if($sender instanceof Player){
			$NAME = $sender->getPlayer()->getName();
		}else{
			$NAME = "CONSOLE";
		}
		return $NAME;
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