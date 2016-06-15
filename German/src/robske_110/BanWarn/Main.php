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

class Main extends PluginBase implements Listener{
	public $warnsys;
	public $clientBan;
	public $config;
	public $tempWPUsers;

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		@mkdir($this->getDataFolder());
		$this->warnsys = new Config($this->getDataFolder() . "warnsys.yml", Config::YAML, array());
		$this->warnsys->save();
		$this->clientBan = new Config($this->getDataFolder() . "clientBan.yml", Config::YAML, array());
		$this->clientBan->save();
		$this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, array());
		if($this->config->get("ConfigVersion") != 3){
			$this->config->set('max-points-until-ban', 10);
			$this->config->set('IP-Ban', true);
			$this->config->set('Client-Ban', true);
			$this->config->set('Notify-Mode', 1);
			$this->config->set('ConfigVersion', 3);
		}
		$this->config->save();
	}
    
	public function onJoin(PlayerLoginEvent $event){
		$isAlreadyBanned = false;
		$playerID = $event->getPlayer()->getClientId();
		$playerName = $event->getPlayer();
		foreach($this->clientBan->getAll() as $rawPlayerID){
			if($playerID == $rawPlayerID){
				$reason = "";
				$Index = 0;
				foreach($this->warnsys->get($playerID) as $playerData){
					if($Index != 0){
						$reason = $reason.TF::GREEN."W ".TF::WHITE.$Index.": ".TF::GREEN."Grund: ".TF::GOLD.$playerData[0]."\n"; //TODO::Translate
					}
					$Index++;
				}
				$reason = "Du wurdest gebannt: \n".$reason;
				$event->getPlayer()->kick($reason, false);
				$isAlreadyBanned = true;
			}
		}
		if($this->countWPoints($playerID) >= $this->config->get("max-warns-until-ban") && !$isAlreadyBanned){
			$reason = "";
			$tempStuffArray = $this->warnsys->get($playerID);
			$Index = 0;
			foreach($tempStuffArray as $playerData){
				if($Index != 0){
					$reason = $reason.TF::GREEN."W ".TF::WHITE.$Index.": ".TF::GREEN."Grund: ".TF::GOLD.$playerData[0]."\n"; //TODO::Translate
				}
				if($Index == 1){
					$reason = $reason."\n\n\n";
				}
				$Index++;
			}
			$reason = "Du wurdest gebannt: \n".$reason; //TODO::Translate
			//IP_Ban
			$ip = $event->getPlayer()->getAddress();
			$this->banIP($ip, $reason, $playerName);
			//Client-Ban
			$this->banClient($playerName, $playerID);
		}
	}
    
	private function parseWPpromptMsg($msg, $playerName, $sender){
		$doEnd = true;
		if($msg == "abort"){
			$this->sendMsgToSender($sender, TF::RED."Das Warnpardon Kommando wurde abgebrochen"); //TODO::Translate
		}elseif($msg == "last"){
			$remResult = $this->removeLastWarn($playerName);
			if($remResult["warnsys"] && $remResult["clientBan"] && $remResult["ipBan"]){
				$this->sendMsgToSender($sender, TF::GREEN."Die letzte Warnung von '".TF::DARK_GRAY.$playerName.TF::GREEN."' wurde entfernt! Ein Serverneustart könnte notwendig sein"); //TODO::Translate TODO::FixServerRestartNeed
			}elseif($remResult["warnsys"] && $remResult["clientBan"]){
				$this->sendMsgToSender($sender, TF::GREEN."Die letzte Warnung von '".TF::DARK_GRAY.$playerName.TF::GREEN."' wurde entfernt!"); //TODO::Translate
			}else{
				$this->sendMsgToSender($sender, TF::RED."Der Spieler '".TF::DARK_GRAY.$playerName.TF::RED."' hat keine Warnungen!"); //TODO::Translate
			}
		}elseif($msg == "all"){
			$wipeResult = $this->wipePlayer($playerName);
			if($wipeResult["warnsys"] && $wipeResult["clientBan"] && $wipeResult["ipBan"]){
				$this->sendMsgToSender($sender, TF::GREEN."Alle Warnungen von '".TF::DARK_GRAY.$playerName.TF::GREEN."' wurden entfernt! Ein Serverneustart könnte notwendig sein"); //TODO::Translate TODO::FixServerRestartNeed
			}elseif($wipeResult["warnsys"] && $wipeResult["clientBan"]){
				$this->sendMsgToSender($sender, TF::GREEN."Alle Warnungen von '".TF::DARK_GRAY.$playerName.TF::GREEN."' wurden entfernt!"); //TODO::Translate
			}else{
				$this->sendMsgToSender($sender, TF::RED."Der Spieler '".TF::DARK_GRAY.$playerName.TF::RED."' hat keine Warnungen!"); //TODO::Translate
			}
		}else{
			$this->sendMsgToSender($sender, TF::GREEN."Du bist momentan im Warnpardon Kommando (Spieler: '".TF::DARK_GRAY.$playerName.TF::GREEN."')"); //TODO::Translate
			$this->sendMsgToSender($sender, TF::GREEN."Falls du das Warnpardon Kommando verlassen möchtest, gebe 'abort' ein"); //TODO::Translate
			$this->sendMsgToSender($sender, TF::GREEN."Gebe 'all' ein, um alle Warnungen zu entfernen."); //TODO::Translate
			$this->sendMsgToSender($sender, TF::GREEN."Gebe 'last' ein, um die letzte Warnung zu entfernen."); //TODO::Translate
			$doEnd = false;
		}
		return $doEnd;
	}
	
	public function onChat(PlayerChatEvent $event){
		if(isset($this->tempWPUsers[$event->getPlayer()->getName()])){
			$msg = strtolower($event->getMessage());
			$sender = $event->getSender();
			$playerName = strtolower($this->tempWPUsers[$event->getPlayer()->getName()]);
			$event->setCancelled(true);
			if($this->parseWPpromptMsg($msg, $playerName, $sender)){
				unset($this->tempWPUsers[$event->getPlayer()->getName()]);
			}
		}
	}
	public function onConsoleChat(ServerCommandEvent $event){
		if(isset($this->tempWPUsers["C.O.N.S.O.L.E_moreThan16Characters"])){
			$msg = strtolower($event->getCommand());
			$sender = $event->getSender();
			$event->setCancelled(true);
			$playerName = strtolower($this->tempWPUsers["C.O.N.S.O.L.E_moreThan16Characters"]);
			if($this->parseWPpromptMsg($msg, $playerName, $sender)){
				unset($this->tempWPUsers["C.O.N.S.O.L.E_moreThan16Characters"]);
			}
		}
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
					$this->sendMsgToSender($sender, TF::GREEN."Warnungen für den Spieler '".TF::DARK_GRAY.$playerName.TF::GREEN."' mit insgesamt ".$this->countWPoints($playerID)." Punkten:"); //TODO::Translate
					$Index = 0;
					$tempStuffArray = $this->warnsys->get($playerID);
					foreach($tempStuffArray as $playerData){
						if($Index != 0){
							$this->sendMsgToSender($sender, TF::GREEN."Warnung ".TF::WHITE.$Index.":"); //TODO::Translate
							$this->sendMsgToSender($sender, TF::GREEN."Grund: ".TF::DARK_GRAY.$playerData[0]); //TODO::Translate
							$this->sendMsgToSender($sender, TF::GREEN."Punkte: ".TF::DARK_GRAY.$playerData[1]); //TODO::Translate
						}
						$Index++;
					}
				}else{
					$this->sendMsgToSender($sender, TF::RED."Es gibt keine Warnungen für den Spieler '".TF::DARK_GRAY.$playerName.TF::RED."'!"); //TODO::Translate
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
				$this->sendMsgToSender($sender, TF::GREEN."Du bist kurz davor eine Warnung oder alle Warnungen des Spielers '".TF::DARK_GRAY.$args[0].TF::GREEN."' zu entfernen!");
				$this->sendMsgToSender($sender, TF::GREEN."Falls du dies abbrechen möchtest, gebe 'abort' ein");
				$this->sendMsgToSender($sender, TF::GREEN."Gebe 'all' ein, um alle Warnungen zu entfernen.");
				$this->sendMsgToSender($sender, TF::GREEN."Gebe 'last' ein, um die letzte Warnung zu entfernen.");
			}else{return false;}
			return true;
		}
	}
    
	private function addOnlineWarn($args, $sender){
		$playerName = strtolower($this->getServer()->getPlayer($args[0])->getName());
		$playerID = $this->getServer()->getPlayer($args[0])->getClientId();
		$array = $this->warnsys->get($playerID, []); //Returns an empty array if the player has no previous warnings
		$Index = count($array);
		if($Index == 0){
			$array[0] = ["RealPlayerName" => $playerName, "RealClientID" => $playerID];
			$Index++;
		}
		$array[$Index] = [$args[1], $args[2]];
		$this->warnsys->set($playerID, $array);
		$this->warnsys->save();
		$tempMsgS = TF::GREEN . "Der Spieler '".TF::DARK_GRAY.$playerName.TF::GREEN."' wurde mit dem Grund '".TF::DARK_GRAY.$args[1].TF::GREEN."' mit ".TF::DARK_GRAY.$args[2].TF::GREEN." Punkten gewarnt! Er hat insgesamt ".TF::DARK_GRAY.$this->countWPoints($playerID).TF::GREEN." Punkte."; //TODO::Translate
		$tempMsgToP = TF::RED . "DU WURDEST VON '".$sender->getName()."' MIT DEM GRUND '".TF::DARK_GRAY.$args[1].TF::RED."' mit ".TF::DARK_GRAY.$args[2].TF::RED." PUNKTEN GEWARNT! DU HAST ".TF::DARK_GRAY.$this->countWPoints($playerID).TF::RED." PUNKTE! MIT ".TF::DARK_GRAY.$this->config->get("max-warns-until-ban").TF::RED." PUNKTEN WIRST DU GEBANNT!"; //TODO::Translate
		$this->getServer()->getPlayer($args[0])->sendMessage($tempMsgToP);
		if($this->config->get("Notify-Mode") == 1){
			$this->sendMsgToSender($sender, $tempMsgS);
			if($this->getTypeAsNameOfSender($sender) != "CONSOLE"){
				$this->getServer()->getLogger()->info($tempMsgS);
			}
		}elseif($this->config->get("Notify-Mode") == 2){
			$this->getServer()->broadcastMessage($tempMsgS);
		}
		if($this->countWPoints($playerID) >= $this->config->get("max-warns-until-ban")){
			$reason = "";
			$tempStuffArray = $this->warnsys->get($playerID);
			$Index = 0;
			foreach($tempStuffArray as $playerData){
				if($Index != 0){
					$reason = $reason.TF::GREEN."W ".TF::WHITE.$Index.": ".TF::GREEN."Grund: ".TF::GOLD.$playerData[0]."\n"; //TODO::Translate
				}
				$Index++;
			}
			$reason = "Du wurdest gebannt: \n".$reason; //TODO::Translate
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
			$this->warnsys->save();
			$tempMsgS = TF::GREEN . "Der Spieler '".TF::DARK_GRAY.$playerName.TF::GREEN."' wurde mit dem Grund '".TF::DARK_GRAY.$args[1].TF::GREEN."' mit ".TF::DARK_GRAY.$args[2].TF::GREEN." Punkten gewarnt! Er hat insgesamt ".TF::DARK_GRAY.$this->countWPoints($playerID).TF::GREEN." Punkte."; //TODO::Translate
			if($this->config->get("Notify-Mode") == 1){
				$this->sendMsgToSender($sender, $tempMsgS);
				if($this->getTypeAsNameOfSender($sender) != "CONSOLE"){
					$this->getServer()->getLogger()->info($tempMsgS);
				}
			}elseif($this->config->get("Notify-Mode") == 2){
				$this->getServer()->broadcastMessage($tempMsgS);
			}
			if($this->countWPoints($playerID) >= $this->config->get("max-points-until-ban")){
				$this->sendMsgToSender($sender, TF::RED."Der Spieler '".TF::DARK_GRAY.$playerName.TF::RED."' wird bei seinem nächsten Login gebannt!"); //TODO::Translate
			}
		}else{
			$this->sendMsgToSender($sender, TF::RED."Leider konnte '".TF::DARK_GRAY.$playerName.TF::RED."' nicht gewarnt werden, da er nicht Online ist und keine bisherigen Warns hat!"); //TODO::Translate //TODO::FixThis (By using player.dat maybe (WaitingForPM)? HEY, POCKETMINE:WHY ISN'T THERE AN EASY SOLOUTION FOR THIS!)
		}
	}
	
	private function wipePlayer($playerName){
		$remSuceededLvls = ["warnsys" => false, "clientBan" => false, "ipBan" => false];
		$playerID = $this->getWarnPlayerByName($playerName);
		if($this->warnsys->exists($playerID)){
			$remSuceededLvls["warnsys"] = true;
			$this->warnsys->remove($playerID);
			$this->warnsys->save();
		}
		if($this->clientBan->exists($playerName)){
			$remSuceededLvls["clientBan"] = true;
			$this->clientBan->remove($playerName);
			$this->clientBan->save();
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
			$this->warnsys->save();
		}
		if($this->countWPoints($playerID) < $this->config->get("max-points-until-ban")){
			if($this->clientBan->exists($playerName)){
				$remSuceededLvls["clientBan"] = true;
				$this->clientBan->remove($playerName);
				$this->clientBan->save();
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
	
	private function banClient($playerName, $playerID){
		if($this->config->get("Client-Ban")){
			$this->clientBan->set($playerName, $playerID);
			$this->clientBan->save();
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
	private function sendMsgToSender($sender, $message){
		if($sender instanceof Player){
			$sender->getPlayer()->sendMessage($message);
		}else{
			$this->getServer()->getLogger()->info("[WarnBan] ".$message);
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