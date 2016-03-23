<?php

namespace robske_110\WarnBan;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\math\Vector3;
use pocketmine\tile\Sign;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\Player;
use pocketmine\entity\Entity;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;

class Main extends PluginBase implements Listener
{
    public $warnsys;

    public function onEnable()
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        @mkdir($this->getDataFolder());
        $this->warnsys = new Config($this->getDataFolder() . "warnsys.yml", Config::YAML, array());
        $this->warnsys->save();
    }
    public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		switch($command->getName())
        {
            case "warn":
            if(isset($args[2]))
            {
                if(ctype_digit($args[2]))
                {
                    if($this->getServer()->getPlayer($args[0]) instanceof Player)
                    {
                        $playerName = $this->getServer()->getPlayer($args[0])->getName();
                        $array = $this->warnsys->get($playerName, []); //Returns an empty array if the player has no previous warnings
                        $INDEX = count($array);
                        $INDEX++;
                        $array[$INDEX] = [$args[1], $args[2]];
                        $this->warnsys->set($playerName, $array);
                        $this->warnsys->save();
                        $tempMsgS = TF::GREEN . "Der Spieler '".TF::DARK_GRAY.$playerName.TF::GREEN."' wurde mit dem Grund '".TF::DARK_GRAY.$args[1].TF::GREEN."' mit ".TF::DARK_GRAY.$args[2].TF::GREEN." Punkten gewarnt! Er hat insgesamt ".TF::DARK_GRAY.$this->countWPoints($playerName).TF::GREEN." Punkte.";
                        $tempMsgToP = TF::RED . "DU WURDEST VON '".$sender->getName()."' MIT DEM GRUND '".TF::DARK_GRAY.$args[1].TF::RED."' mit ".TF::DARK_GRAY.$args[2].TF::RED." PUNKTEN GEWARNT! DU HAST ".TF::DARK_GRAY.$this->countWPoints($playerName).TF::RED." PUNKTE! MIT 10 PUNKTEN WIRST DU GEBANNT!";
                        $this->getServer()->getPlayer($args[0])->sendMessage($tempMsgToP);
                        $this->sendMsgToSender($sender, $tempMsgS);
                        if($this->getTypeAsNameOfSender($sender) != "CONSOLE")
                        {
                            $this->getServer()->getLogger()->info($tempMsgS);
                        }
                        if($this->countWPoints($playerName) == 10){
                            $reason = "";
                            $tempStuffArray = $this->warnsys->get($playerName);
                            foreach($tempStuffArray as $playerData)
                            {
                                $INDEX++;
                                $reason = $reason.TF::GREEN."Warnung ".TF::WHITE.$INDEX.": ".TF::GREEN."Grund: ".TF::GOLD.$playerData[0]."\n";
                            }
                            $reason = "Du wurdest gebannt: \n".$reason;
                            $ip = $this->getServer()->getPlayer($args[0])->getAddress();
                    		$this->getServer()->getIPBans()->addBan($ip, $reason, null, $sender->getName());
                    		foreach($this->getServer()->getOnlinePlayers() as $player){
                    			if($player->getAddress() === $ip){
                    				$player->kick($reason, false);
                    			}
                    		}
                    		$sender->getServer()->getNetwork()->blockAddress($ip, -1);
                        }
                    }
                    else
                    {
                        $playerName = $args[0];
                        $array = $this->warnsys->get($playerName, []); //Returns an empty array if the player has no previous warnings
                        $INDEX = count($array);
                        $INDEX++;
                        $array[$INDEX] = [$args[1], $args[2]];
                        $this->warnsys->set($playerName, $array);
                        $this->warnsys->save();
                        $tempMsgS = TF::GREEN . "Der Spieler '".TF::DARK_GRAY.$playerName.TF::GREEN."' wurde mit dem Grund '".TF::DARK_GRAY.$args[1].TF::GREEN."' mit ".TF::DARK_GRAY.$args[2].TF::GREEN." Punkten gewarnt! Er hat insgesamt ".TF::DARK_GRAY.$this->countWPoints($playerName).TF::GREEN." Punkte.";
                        $this->sendMsgToSender($sender, $tempMsgS);
                        if($this->getTypeAsNameOfSender($sender) != "CONSOLE")
                        {
                            $this->getServer()->getLogger()->info($tempMsgS);
                        }
                        if($this->countWPoints($playerName) >= 10){
                            $this->sendMsgToSender($sender, TF::RED."Du musst '".TF::DARK_GRAY.$playerName.TF::RED."' selber bannen, da er nicht online ist!");
                        }
                    }
                }
                else
                {return false;}   
            }
            else
            {return false;}      
        return true;
        break; 
        case "warninfo":
        if(isset($args[0]))
        {
            if($this->getServer()->getPlayer($args[0]) instanceof Player)
            {
                $playerName = $this->getServer()->getPlayer($args[0])->getName();
            }
            else
            {
                $playerName = $args[0];
            }
            if($this->warnsys->exists($playerName))
            {
                $this->sendMsgToSender($sender, TF::GREEN."Warnungen für den Spieler '".TF::DARK_GRAY.$playerName.TF::GREEN."' mit insgesamt ".$this->countWPoints($playerName)." Punkten:");
                $INDEX = 0;
                $tempStuffArray = $this->warnsys->get($playerName);
                foreach($tempStuffArray as $playerData)
                {
                    $INDEX++;
                    $this->sendMsgToSender($sender, TF::GREEN."Warnung ".TF::WHITE.$INDEX.":");
                    $this->sendMsgToSender($sender, TF::GREEN."Grund: ".TF::DARK_GRAY.$playerData[0]);
                    $this->sendMsgToSender($sender, TF::GREEN."Punkte: ".TF::DARK_GRAY.$playerData[1]);
                }
            }
            else
            {
                $this->sendMsgToSender($sender, TF::RED."Es gibt keine Warnungen für den Spieler '".TF::DARK_GRAY.$playerName.TF::RED."'!");
            }
        }
        else
        {return false;}  
        return true;
        }
    }
    private function sendMsgToSender($sender, $message){
        if($sender instanceof Player)
        {
            $sender->getPlayer()->sendMessage($message);
        }
        else
        {
            $this->getServer()->getLogger()->info("[ServerCore-WarnSys] ".$message);
        }
    }
    public function countWPoints($playerName){
        $tempStuffArray = $this->warnsys->get($playerName);
        $count = 0;
        foreach($tempStuffArray as $playerData)
        {
            $count = $count + $playerData[1];
        }
        return $count;
    }
    private function getTypeAsNameOfSender($sender){
        if($sender instanceof Player)
        {
            $NAME = $sender->getPlayer()->getName();
        }
        else
        {
            $NAME = "CONSOLE";
        }
        return $NAME;
    } 
}
//Theory is when you know something, but it doesn't work. Practice is when something works, but you don't know why. Programmers combine theory and practice: Nothing works and they don't know why!
