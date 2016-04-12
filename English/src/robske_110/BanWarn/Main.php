<?php

namespace robske_110\BanWarn;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;

class Main extends PluginBase implements Listener{
    public $warnsys;
    public $clientBan;
    public $config;

    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        @mkdir($this->getDataFolder());
        $this->warnsys = new Config($this->getDataFolder() . "warnsys.yml", Config::YAML, array());
        $this->warnsys->save();
        $this->clientBan = new Config($this->getDataFolder() . "clientBan.yml", Config::YAML, array());
        $this->clientBan->save();
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, array());
        if($this->config->get("ConfigVersion") != 2){
            $this->config->set('max-points-until-ban', 10);
            $this->config->set('IP-Ban', true);
            $this->config->set('Client-Ban', true);
            $this->config->set('ConfigVersion', 2);
        }
        $this->config->save();
    }
    
    public function onPreJoin(PlayerPreLoginEvent $event){
        $isAlreadyBanned = false;
        $playerID = $event->getPlayer()->getClientId();
        foreach($this->clientBan->getAll() as $rawPlayerID){
            if($playerID == $rawPlayerID){
                $reason = "";
                $Index = 0;
                foreach($this->warnsys->get($playerID) as $playerData)
                {
                    if($Index != 0){
                        $reason = $reason.TF::GREEN."W ".TF::WHITE.$Index.": ".TF::GREEN."Reason: ".TF::GOLD.$playerData[0]."\n"; //TODO::Translate
                    }
                    if($Index == 1){
                        $reason = $reason."\n\n\n";
                    }
                    $Index++;
                }
                $reason = "You are banned: \n".$reason;
                $event->getPlayer()->kick($reason, false);
                $isAlreadyBanned = true;
            }
        }
        if($this->countWPoints($playerID) >= $this->config->get("max-warns-until-ban") && !$isAlreadyBanned){
            $reason = "";
            $tempStuffArray = $this->warnsys->get($playerID);
            $Index = 0;
            foreach($tempStuffArray as $playerData)
            {
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
            $ip = $this->getServer()->getPlayer($args[0])->getAddress();
            $this->banIP($ip, $reason);
            //Client-Ban
            $this->banClient($playerName, $playerID);
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
                else
                {return false;}   
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
                $playerName = $this->getServer()->getPlayer($args[0])->getName();
                $playerID = $this->getServer()->getPlayer($args[0])->getClientID();
            }else{
                $playerName = $args[0];
                $playerID = $this->getWarnPlayerByName($playerName);
            }
            if($this->warnsys->exists($playerID)){
                $this->sendMsgToSender($sender, TF::GREEN."Warnings for the player '".TF::DARK_GRAY.$playerName.TF::GREEN."', who has ".$this->countWPoints($playerID)." Points:"); //TODO::Translate
                $Index = 0;
                $tempStuffArray = $this->warnsys->get($playerID);
                foreach($tempStuffArray as $playerData)
                {
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
        }
        else
        {return false;}  
        return true;
        }
    }
    private function addOnlineWarn($args, $sender){
        $playerName = $this->getServer()->getPlayer($args[0])->getName();
        $playerID = $this->getServer()->getPlayer($args[0])->getClientId();
        $array = $this->warnsys->get($playerID, []); //Returns an empty array if the player has no previous warnings #FunFact this is the only line written by someone else @PEMapModder :)
        $Index = count($array);
        if($Index == 0){
          $array[0] = ["RealPlayerName" => $playerName, "RealClientID" => $playerID];
          $Index++;
        }
        $array[$Index] = [$args[1], $args[2]];
        $this->warnsys->set($playerID, $array);
        $this->warnsys->save();
        $tempMsgS = TF::GREEN . "The player '".TF::DARK_GRAY.$playerName.TF::GREEN."' has been warned with the reason '".TF::DARK_GRAY.$args[1].TF::GREEN."' and ".TF::DARK_GRAY.$args[2].TF::GREEN." Point(s)! He now has a total of ".TF::DARK_GRAY.$this->countWPoints($playerID).TF::GREEN." Points."; //TODO::Translate
        $tempMsgToP = TF::RED . "YOU HAVE BEEN WARNED BY '".$sender->getName()."' WITH THE REASON '".TF::DARK_GRAY.$args[1].TF::RED."' and ".TF::DARK_GRAY.$args[2].TF::RED." POINTS! YOU NOW HAVE A TOTAL OF".TF::DARK_GRAY.$this->countWPoints($playerID).TF::RED." POINTS! WITH ".TF::DARK_GRAY.$this->config->get("max-warns-until-ban").TF::RED." POINTS YOU'LL BE BANNED!"; //TODO::Translate
        $this->getServer()->getPlayer($args[0])->sendMessage($tempMsgToP);
        $this->sendMsgToSender($sender, $tempMsgS);
        if($this->getTypeAsNameOfSender($sender) != "CONSOLE"){
            $this->getServer()->getLogger()->info($tempMsgS);
        }
        if($this->countWPoints($playerID) >= $this->config->get("max-warns-until-ban")){
            $reason = "";
            $tempStuffArray = $this->warnsys->get($playerID);
            $Index = 0;
            foreach($tempStuffArray as $playerData)
            {
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
            $ip = $this->getServer()->getPlayer($args[0])->getAddress();
            $this->banIP($ip, $reason);
            //Client-Ban
            $this->banClient($playerName, $playerID);
        }
    }
    private function addOfflineWarn($args, $sender){
        $playerName = $args[0];
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
          $tempMsgS = TF::GREEN . "The player '".TF::DARK_GRAY.$playerName.TF::GREEN."' has been warned with the reason '".TF::DARK_GRAY.$args[1].TF::GREEN."' and ".TF::DARK_GRAY.$args[2].TF::GREEN." Point(s)! He now has a total of ".TF::DARK_GRAY.$this->countWPoints($playerID).TF::GREEN." Points."; //TODO::Translate
          $this->sendMsgToSender($sender, $tempMsgS);
          if($this->getTypeAsNameOfSender($sender) != "CONSOLE"){
              $this->getServer()->getLogger()->info($tempMsgS);
          }
          if($this->countWPoints($playerID) >= $this->config->get("max-warns-until-ban")){
              $this->sendMsgToSender($sender, TF::RED."The player '".TF::DARK_GRAY.$playerName.TF::RED."' will be banned on his next login!"); //TODO::Translate
          }
        }else{
          $this->sendMsgToSender($sender, TF::RED."Unfortunaly '".TF::DARK_GRAY.$playerName.TF::RED."' could not be warned, as he is not online and has no prevoius warnings!"); //TODO::Translate //TODO::FixThis (By using player.dat maybe (WaitingForPM)? HEY, POCKETMINE:WHY ISN'T THERE AN EASY SOLOUTION FOR THIS!)
        }
    }
    private function clientBan($playerName, $clientID){
        if($this->config->get("Client-Ban") == true){
            $this->clientBan->set($playerName, $playerID);
            $this->clientBan->save();
        }
    }
    private function ipBan($ip, $reason){
        if($this->config->get("IP-Ban") == true){
    		foreach($this->getServer()->getOnlinePlayers() as $player){
    	        if($player->getAddress() === $ip){
    		        $player->kick($reason, false);
    			}
    		 }
    		 $sender->getServer()->getNetwork()->blockAddress($ip, -1);
    		 $this->getServer()->getIPBans()->addBan($ip, "BanWarnPluginBan", null, $sender->getName());
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
        $count = 0;
        $Index = 0;
        foreach($tempStuffArray as $playerData)
        {
          if($Index != 0){
            $count = $count + $playerData[1];
          }
          $Index++;
        }
        return $count;
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
              }
            }
          }
        }
      }
      return $playerID;
    }
}
//Theory is when you know something, but it doesn't work. Practice is when something works, but you don't know why. Programmers combine theory and practice: Nothing works and they don't know why!