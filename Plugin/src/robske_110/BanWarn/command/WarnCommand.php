<?php
declare(strict_types=1);

namespace robske_110\BanWarn\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\plugin\Plugin;

class WarnCommand extends Command implements PluginIdentifiableCommand{

    /** @var BanWarn $main */
    private $main;
	/** @var CommandManager $commandManager */
	private $commandManager;

    /**
     * SethomeCommand constructor.
     * @param SimpleHome $plugin
     */
    public function __construct(CommandManager $commandManager, string $permission){
        parent::__construct("warn", "Warns a player", "/warn <PlayerName> <Reason> [Points]");
        $this->setPermission($permission);
        $this->main = $commandManager->getMain();
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
	 *
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args) {
		return true;
    }

	/**
	 * @return BanWarn
	 */
	public function getPlugin(): Plugin{
		return $this->plugin;
	}
}