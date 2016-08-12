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
	private $database;

	public function __construct(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
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
						$reason = $reason.TF::GREEN."W ".TF::WHITE.$Index.": ".TF::GREEN."Reason: ".TF::GOLD.$playerData[0]."\n"; //TODO::Translate
					}
					$Index++;
				}
				$reason = "You are banned: \n".$reason;
				$event->getPlayer()->kick($reason, false);
				$isAlreadyBanned = true;
			}
		}
		if($this->countWPoints($playerID) >= $this->config->get("max-points-until-ban") && !$isAlreadyBanned){
			$reason = "";
			$tempStuffArray = $this->warnsys->get($playerID);
			$Index = 0;
			foreach($tempStuffArray as $playerData){
				if($Index != 0){
					$reason = $reason.TF::GREEN."W ".TF::WHITE.$Index.": ".TF::GREEN."Reason: ".TF::GOLD.$playerData[0]."\n"; //TODO::Translate
				}
				if($Index == 1){
					$reason = $reason."\n\n\n";
				}
				$Index++;
			}
			$reason = "You are banned: \n".$reason; //TODO::Translate
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
			$this->sendMsgToSender($sender, TF::RED."Aborted the warnpardon prompt"); //TODO::Translate
		}elseif($msg == "last"){
			$remResult = $this->removeLastWarn($playerName);
			if($remResult["warnsys"] && $remResult["clientBan"] && $remResult["ipBan"]){
				$this->sendMsgToSender($sender, TF::GREEN."The last warn from '".TF::DARK_GRAY.$playerName.TF::GREEN."' has been removed! A server restart may be necassary"); //TODO::Translate TODO::FixServerRestartNeed
			}elseif($remResult["warnsys"] && $remResult["clientBan"]){
				$this->sendMsgToSender($sender, TF::GREEN."The last warn from '".TF::DARK_GRAY.$playerName.TF::GREEN."' has been removed!"); //TODO::Translate
			}else{
				$this->sendMsgToSender($sender, TF::RED."The player '".TF::DARK_GRAY.$playerName.TF::RED."' has no warnings!"); //TODO::Translate
			}
		}elseif($msg == "all"){
			$wipeResult = $this->wipePlayer($playerName);
			if($wipeResult["warnsys"] && $wipeResult["clientBan"] && $wipeResult["ipBan"]){
				$this->sendMsgToSender($sender, TF::GREEN."All warns from '".TF::DARK_GRAY.$playerName.TF::GREEN."' have been removed! A server restart may be necassary"); //TODO::Translate TODO::FixServerRestartNeed
			}elseif($wipeResult["warnsys"] && $wipeResult["clientBan"]){
				$this->sendMsgToSender($sender, TF::GREEN."All warns from '".TF::DARK_GRAY.$playerName.TF::GREEN."' have been removed!"); //TODO::Translate
			}else{
				$this->sendMsgToSender($sender, TF::RED."The player '".TF::DARK_GRAY.$playerName.TF::RED."' has no warnings!"); //TODO::Translate
			}
		}else{
			$this->sendMsgToSender($sender, TF::GREEN."You are currently in the warnpardon prompt (Player: '".TF::DARK_GRAY.$playerName.TF::GREEN."')"); //TODO::Translate
			$this->sendMsgToSender($sender, TF::GREEN."If you want to abort this simply type 'abort'"); //TODO::Translate
			$this->sendMsgToSender($sender, TF::GREEN."Type 'all' to remove all warns."); //TODO::Translate
			$this->sendMsgToSender($sender, TF::GREEN."Type 'last' to remove the last warn."); //TODO::Translate
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
}
//Theory is when you know something, but it doesn't work. Practice is when something works, but you don't know why. Programmers combine theory and practice: Nothing works and they don't know why!