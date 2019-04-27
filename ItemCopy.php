<?php
/**
 * @name ItemCopy
 * @author alvin0319
 * @main alvin0319\ItemCopy
 * @version 1.0.0
 * @api 4.0.0
 */
declare(strict_types=1);
namespace alvin0319;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;

class ItemCopy extends PluginBase{

    public function onEnable(){
        $this->getServer()->getCommandMap()->register('itemcopy', new ItemCopyCommand($this));
    }
}
class ItemCopyCommand extends Command{

    protected $plugin;

    public function __construct(ItemCopy $plugin){
        $this->plugin = $plugin;
        parent::__construct('itemcopy', '아이템을 복사합니다', '/itemcopy');
    }
    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender instanceof Player){
            return true;
        }
        if(!$sender->isOp()) return true;

        $item = $sender->getInventory()->getItemInHand();
        $sender->getInventory()->addItem($item);
        $sender->sendMessage('아이템 복사에 성공했습니다');
        return true;
    }
}