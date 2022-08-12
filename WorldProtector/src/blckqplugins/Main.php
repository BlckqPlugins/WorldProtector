<?php

namespace blckqplugins;

use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\Server;

class Main extends PluginBase implements Listener{

    const PREFIX = "§8[§bWorld§fProtector§8] §r";

    public function onEnable(): void{

        $this->saveResource("config.yml", false);

        $config = $this->getConfig();
        if (!is_array($this->getConfig()->get("protected-worlds"))) $config->set("protected-worlds", []);
        if (!$config->exists("damage-enabled")) $config->set("damage-enabled", false);
        if (!$config->exists("interact-enabled")) $config->set("interact-enabled", false);
        $config->save();

        $message = "\n";
        foreach ((array)$this->getConfig()->get("protected-worlds") as $worlds){
            $message .= "- " . $worlds . "\n";
        }

        Server::getInstance()->getLogger()->info("§aCurrently are these worlds protected: {$message}");

        $this->getLogger()->info("WorldProtector enabled");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onDisable(): void{
        $this->getLogger()->info("WorldProtector disabled");
    }

    public function addProtectedWorld(string $worldName){
        $config = $this->getConfig();
        $worlds = (array)$config->get("protected-worlds");

        if (!in_array($worldName, $worlds)){
            $worlds[] = $worldName;
        }

        $config->set("protected-worlds", $worlds);
        $config->save();
    }

    public function removeProtectedWorld(string $worldName){
        $config = $this->getConfig();
        $worlds = (array)$config->get("protected-worlds");

        unset($worlds[array_search($worldName, $worlds)]);

        $config->set("protected-worlds", $worlds);
        $config->save();
    }

    public function onCommand(CommandSender $sender, Command $cmd, String $label, Array $args): bool
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage("§cYou can use this command only as Player.");
            return false;
        }

        if (!$sender->hasPermission("wp.command")){
            $sender->sendMessage("§cYou don't have enough permissions to execute this command");
        }

        if (!isset($args[0])) {
            $sender->sendMessage(self::PREFIX . "§cUse: /wp <add|remove|list> <worldname>");
            return false;
        }

        if ($cmd->getName() === "wp") {
            if ($args[0] == "add") {
                if (isset($args[1])) {
                    if (Server::getInstance()->getWorldManager()->getWorldByName($args[1]) != null) {
                        if (!in_array($args[1], $this->getConfig()->get("protected-worlds"))) {
                            $this->addProtectedWorld($args[1]);
                            $sender->sendMessage(self::PREFIX . "§aWorld §e{$args[1]} §aprotected.");
                        } else {
                            $sender->sendMessage(self::PREFIX . "This world is already protected.");
                        }
                    } else {
                        $sender->sendMessage(self::PREFIX . "This world don't exists.");
                    }
                } else {
                    $sender->sendMessage(self::PREFIX . "§cUse: /wp <add|remove|list> <worldname>");
                }
                return false;
            }
            if ($args[0] == "remove") {
                if (isset($args[1])) {
                    if (Server::getInstance()->getWorldManager()->getWorldByName($args[1]) != null) {
                        if (in_array($args[1], $this->getConfig()->get("protected-worlds"))) {
                            $this->removeProtectedWorld($args[1]);
                            $sender->sendMessage(self::PREFIX . "§cWorld §e{$args[1]} §cunprotected.");
                        } else {
                            $sender->sendMessage(self::PREFIX . "This world isn't protected.");
                        }
                    } else {
                        $sender->sendMessage(self::PREFIX . "This world don't exists.");
                    }
                } else {
                    $sender->sendMessage(self::PREFIX . "§cUse: /wp <add|remove|list> <worldname>");
                }
                return true;
            }
            if ($args[0] == "list") {
                $message = "\n";
                foreach ((array)$this->getConfig()->get("protected-worlds") as $worlds) {
                    $message .= "- " . $worlds . "\n";
                }
                $sender->sendMessage(self::PREFIX . "§aCurrently are these worlds protected: {$message}");
                return true;
            }
        }
        return true;
    }
    
    public function onBreak(BlockBreakevent $event){
        $player = $event->getPlayer();
        if (!$player->hasPermission("wp.bypass")) {
            if (in_array($player->getWorld()->getfolderName(), $this->getConfig()->get("protected-worlds"))) {
                $event->cancel();
                $player->sendMessage(self::PREFIX . "§cYou cannot break blocks in this world");
            }
        }
    }

    public function onPlace(BlockPlaceEvent $event){
        $player = $event->getPlayer();
        if (!$player->hasPermission("wp.bypass")) {
            if (in_array($player->getWorld()->getfolderName(), $this->getConfig()->get("protected-worlds"))) {
                $event->cancel();
                $player->sendMessage(self::PREFIX . "§cYou cannot place blocks in this world");
            }
        }
    }

    public function onInteract(PlayerInteractEvent $event){
        $player = $event->getPlayer();
        if (!$player->hasPermission("wp.bypass")) {
            if ((bool)$this->getConfig()->get("interact-enabled")){
                return;
            }
            if (in_array($player->getWorld()->getfolderName(), $this->getConfig()->get("protected-worlds"))) {
                $event->cancel();
                $player->sendMessage(self::PREFIX . "§cYou cannot place blocks in this world");
            }
        }
    }

    public function onDamage(EntityDamageEvent $event){
        $entity = $event->getEntity();
        if ($entity instanceof Player){
            if (!$entity->hasPermission("wp.bypass")) {
                if ((bool)$this->getConfig()->get("damage-enabled")){
                    return;
                }
                if (in_array($entity->getWorld()->getfolderName(), $this->getConfig()->get("protected-worlds"))) {
                    $event->cancel();
                    $entity->sendMessage(self::PREFIX . "§cDamage is disabled in this world.");
                }
            }
        }
    }
}