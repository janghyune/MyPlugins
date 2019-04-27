<?php
/**
 * @name ShoutBox
 * @author alvin0319
 * @main alvin0319\ShoutBox
 * @version 1,9,9
 * @api 4.0.0
 */
declare(strict_types=1);
namespace alvin0319;

use onebone\economyapi\EconomyAPI;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;

class ShoutBox extends PluginBase{

    public function onEnable(){
        $this->getServer()->getCommandMap()->register('shoutbox', new class($this) extends Command{

            protected $plugin;

            public function __construct(ShoutBox $plugin){
                $this->plugin = $plugin;
                parent::__construct('확성기', '/확성기 <할말...>', '/확성기 <할말...>');
            }

            public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
                if(!$sender instanceof Player){
                    return true;
                }
                $prefix = '§d§l* §7확성기 §r§a';
                if(!isset($args[0])){
                    $sender->sendMessage($prefix . $this->getUsage());
                    return true;
                }
                if(EconomyAPI::getInstance()->myMoney($sender) < 10000){
                    $sender->sendMessage($prefix . '돈이 부족합니다. 사용에 필요한 돈: 10000');
                    return true;
                }
                EconomyAPI::getInstance()->reduceMoney($sender, 10000);
                $a = implode(' ', $args);
                Server::getInstance()->broadcastMessage($prefix . $sender->getName() . ' 님: ' . $a);
                return true;
            }
        });
    }
}