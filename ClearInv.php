<?php
/**
 * @name ClearInv
 * @author alvin0319
 * @main alvin0319\ClearInv
 * @version 1.0.0
 * @api 4.0.0
 */
declare(strict_types=1);
namespace alvin0319;

use pocketmine\plugin\PluginBase;
use pocketmine\command\{Command, CommandSender};
use pocketmine\Player;
//한글깨짐방지

class ClearInv extends PluginBase{
	
	protected function onEnable(){
		$this->getServer()->getCommandMap()->register("clear", new class($this) extends Command{
			public function __construct(ClearInv $plugin){
				//NONE Source
				parent::__construct("인벤초기화", "인벤토리를 초기화합니다.", "/인벤초기화 [닉네임..] [true/false] | 인벤토리를 초기화합니다. true 로 설정할시 갑옷 인벤토리도 초기화됩니다.");
			}
			public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
				if(!$sender instanceof Player){
					return true;
				}
				if(!$sender->isOp()){
					$sender->sendMessage("§d§l* §7인벤초기화 : §r§7권한이 부족합니다.");
					return true;
				}
				if(!isset($args[0])){
					$sender->sendMessage("§d§l* §7인벤초기화 : §r§7" . $this->getUsage());
					return true;
				}
				$name = array_shift($args);
				$bool = array_shift($args);
				if(!isset($name)){
					$sender->sendMessage("§d§l* §7인벤초기화 : §r§7닉네임을 입력해주세요.");
					return true;
				}
				if(!isset($bool)){
					$sender->sendMessage("§d§l* §7인벤초기화 : §r§7갑옷 인벤토리 초기화 여부를 결정해주세요.(true = 초기화, false = 초기화안함)");
					return true;
				}
				if(!($player = \pocketmine\Server::getInstance()->getPlayer(strtolower($name))) instanceof Player){
					$sender->sendMessage("§d§l* §7인벤초기화 : §r§7해당 유저가 온라인이 아닙니다.");
					return true;
				}
				switch($bool){
					case "true":
						$player->getInventory()->clearAll();
						$player->getArmorInventory()->clearAll();
						$sender->sendMessage("§d§l* §7인벤초기화 : §r§7Success");
						break;
					case "false":
						$player->getInventory()->clearAll();
						$sender->sendMessage("§d§l* §7인벤초기화 : §r§7Success");
						break;
					default:
						$sender->sendMessage("§d§l* §7인벤초기화 : §r§7" . $this->getUsage());
				}
				return true;
			}
		});
	}
}