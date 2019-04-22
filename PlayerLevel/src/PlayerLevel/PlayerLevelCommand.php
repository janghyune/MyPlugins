<?php
declare(strict_types=1);
namespace PlayerLevel;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;

class PlayerLevelCommand extends PluginCommand{

    protected $plugin;

    public function __construct(Main $plugin){
        $this->plugin = $plugin;
        parent::__construct("레벨관리", $plugin);
        $this->setDescription("레벨관리 명령어입니다.");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
        $tag = "§d§l* §7레벨 : §r§7";
        if(!$sender->isOp()){
            return true;
        }
        if(!isset($args[0])){
            $sender->sendMessage($tag . "/레벨관리 [헬멧/갑옷/바지/신발] [필요레벨양]");
            return true;
        }
        switch(array_shift($args)){
            case "헬멧":
                $need = array_shift($args);
                if(!isset($need)){
                    $sender->sendMessage($tag . "/레벨관리 헬멧 [필요경험치]");
                    return true;
                }
                if(!is_numeric($need)){
                    $sender->sendMessage($tag . "/레벨관리 헬멧 [필요경험치]");
                    return true;
                }
                $this->plugin->db["helmet-level"] = (int) $need;
                $sender->sendMessage($tag . "설정되었습니다: " . (int) $need);
                break;
            case "갑옷":
                $need = array_shift($args);
                if(!isset($need)){
                    $sender->sendMessage($tag . "/레벨관리 갑옷 [필요경험치]");
                    return true;
                }
                if(!is_numeric($need)){
                    $sender->sendMessage($tag . "/레벨관리 깁옷 [필요경험치]");
                    return true;
                }
                $this->plugin->db["chest-plate-level"] = (int) $need;
                $sender->sendMessage($tag . "설정되었습니다: " . (int) $need);
                break;
            case "바지":
                $need = array_shift($args);
                if(!isset($need)){
                    $sender->sendMessage($tag . "/레벨관리 바지 [필요경험치]");
                    return true;
                }
                if(!is_numeric($need)){
                    $sender->sendMessage($tag . "/레벨관리 바지 [필요경험치]");
                    return true;
                }
                $this->plugin->db["pants-level"] = (int) $need;
                $sender->sendMessage($tag . "설정되었습니다: " . (int) $need);
                break;
            case "신발":
                $need = array_shift($args);
                if(!isset($need)){
                    $sender->sendMessage($tag . "/레벨관리 신발 [필요경험치]");
                    return true;
                }
                if(!is_numeric($need)){
                    $sender->sendMessage($tag . "/레벨관리 신발 [필요경험치]");
                    return true;
                }
                $this->plugin->db["boots-level"] = (int) $need;
                $sender->sendMessage($tag . "설정되었습니다: " . (int) $need);
                break;
            default:
                $sender->sendMessage($tag . "/레벨관리 [헬멧/갑옷/바지/신발] [필요레벨양]");
        }
        return true;
    }
}