<?php
declare(strict_types=1);

namespace robske_110\BanWarn\command;

use pocketmine\permission\Permission;

use robske_110\BanWarn\BanWarn;

class CommandManager{
	const COMMANDS = [
		"warn" => [
			WarnCommand::class,
			["warn", "Allows to run the /warn command", "op"]
		],
		/*"warninfo" => [
			WarnInfoCommand::class,
			["warninfo", "Allows to run the /warninfo command", "op"]
		],
		"warnpardon" => [
			WarnPardonCommand::class,
			["warnpardon", "Allows to run the /warninfo command", "op"]
		]*/
	];
	
	/** @var BanWarn */
	private $main;
	
	public function __construct(BanWarn $main){
		$this->main = $main;
		$this->loadPermissions();
		$this->registerCommands();
	}
	
	private function loadPermissions(){
		$permissions = [];
		foreach(self::COMMANDS as $command){
			$perm = $command[1];
			$permissions["BanWarn.command".$perm[0]] = ["description" => $perm[1], "default" => $perm[2]];
		}
		Permission::loadPermissions($permissions);
	}
	
	private function registerCommands(){
		foreach(self::COMMANDS as $command){
			$command = new $command[0]($this, $command[1][1]);
			$this->main->getServer()->getCommandMap()->register("BanWarn", $command);
		}
	}
	
	public function getMain(): BanWarn{
		return $this->main;
	}
}