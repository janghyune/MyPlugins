<?php

/**
 * @name SizeShop
 * @author alvin0319
 * @main alvin0319\SizeShop
 * @version 1.0.0
 * @api 4.0.0
 */
namespace alvin0319;

use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\level\Position;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\player\{
PlayerMoveEvent, PlayerJoinEvent
};
use pocketmine\network\mcpe\protocol\{
ModalFormRequestPacket, ModalFormResponsePacket
};
use pocketmine\utils\TextFormat;
use pocketmine\command\{
Command, PluginCommand, CommandSender
};
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\block\Block;
//한글깨짐방지
class SizeShop extends PluginBase implements Listener{
	public $prefix = '§d§l< §fSize§d > §r';
	public function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		@mkdir($this->getDataFolder());
		$this->config = new Config($this->getDataFolder() . 'Size.yml', Config::YAML);
		$this->db = $this->config->getAll();
		$this->setting = new Config($this->getDataFolder() . 'Config.yml', Config::YAML, [
		'L' => 2,
		'S' => 0.5,
		'Lm' => 500000,
		'Sm' => 500000
		]);
		$this->set = $this->setting->getAll();
		$cmd = new PluginCommand('크기', $this);
		$cmd->setDescription('크기상점');
		$this->getServer()->getCommandMap()->register('크기', $cmd);
	}
	public function onJoin(PlayerJoinEvent $event) {
		$player = $event->getPlayer();
		$name = $player->getName();
		if (! isset($this->db[$name] ['크기'])) {
			$this->db[$name] ['크기'] = 1;
			$player->sendMessage($this->prefix . '기본 크기가 1 로 설정되었습니다');
			$this->save();
		} else {
			$player->setScale($this->db[$name] ['크기']);
		}
	}
	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		if ($command->getName() === '크기') {
		    if(!$sender instanceof Player) return true;
			$x = (int) round($sender->x - 0.5);
			$y = (int) round($sender->y - 1);
			$z = (int) round($sender->z - 0.5);
			$id = $sender->getLevel()->getBlock(new Position((float) $x, (float) $y, (float) $z))->getId();
			$data = $sender->getLevel()->getBlock(new Position((float) $x, (float) $y, (float) $z))->getDamage();
			if ($id === Block::GRASS and $data === 3) {
				$sender->sendMessage($this->prefix . '이구역에서는 크기상점을 이용할수가 없습니다');
				return true;
			}
			$this->sendUI($sender, 1029, $this->MainData($sender));
		}
		return true;
	}
	public function sendUI(Player $player, $code, $data) {
		$pk = new ModalFormRequestPacket();
		$pk->formId = $code;
		$pk->formData = $data;
		$player->dataPacket($pk);
	}
	public function MainData(Player $player) {
		$size = $this->db[$player->getName()] ['크기'];
		$encode = [
		'type' => 'form',
		'title' => '크기상점',
		'content' => '내 크기: ' . $size,
		'buttons' => [
		[
		'text' => '§0- §f나가기',
		],
		[
		'text' => '§e- §f크기 커지기' . TextFormat::EOL . '커지는 비용: ' . $this->set['Lm'] . '원',
		],
		[
		'text' => '§e- §f크기 작아지기' . TextFormat::EOL . '작아지는 비용: ' . $this->set['Sm'] . '원',
		],
		[
		'text' => '§c- §f크기 1로 변하기',
		],
		[
		'text' => '§a- §f원래대로 돌아오기',
		]
		]
		];
		return json_encode($encode);
	}
	public function onDataPacket(DataPacketReceiveEvent $event) {
		$player = $event->getPlayer();
		$name = $player->getName();
		$pk = $event->getPacket();
		$eco = $this->getServer()->getPluginManager()->getPlugin('EconomyAPI');
		if ($pk instanceof ModalFormResponsePacket) {
			$id = $pk->formId;
			$data = json_decode($pk->formData, true);
			if ($id === 1029) {
				if ($data === 0) {
					$player->sendMessage($this->prefix . '크기상점에서 나왔습니다');
				} else if ($data === 1) {
					if ($eco->myMoney($player) < $this->set['Lm']) {
						$player->sendMessage($this->prefix . '돈이 부족합니다');
						return;
					}
					if ($this->db[$name] ['크기'] > $this->set['L']) {
						$player->sendMessage($this->prefix . '더이상 커지면 성장판 다 닳을껄요?');
						return;
					}
					$this->Up($player);
					$player->sendMessage($this->prefix . '크기가 커졌습니다');
				} else if ($data === 2) {
					if ($eco->myMoney($player) < $this->set['Sm']) {
						$player->sendMessage($this->prefix . '돈이 부족합니다');
						return;
					}
					if ($this->db[$name] ['크기'] < $this->set['S']) {
						$player->sendMessage($this->prefix . '더이상 작아지면 사라질껄요?');
						return;
					}
					$this->Down($player);
					$player->sendMessage($this->prefix . '크기가 작아졌습니다');
				} else if ($data === 3) {
					$player->setScale(1);
					$player->sendMessage($this->prefix . '크기가 1로 변했습니다. 재부팅이나 서버 재접속시 크기가 원래대로 돌아갑니다');
				} else if ($data === 4) {
					$player->setScale($this->db[$name] ['크기']);
					$player->sendMessage($this->prefix . '크기가 원래대로 돌아갔습니다');
				}
			}
		}
	}
	public function Up(Player $player) {
		$name = $player->getName();
		$eco = $this->getServer()->getPluginManager()->getPlugin('EconomyAPI');
		$eco->reduceMoney($player, $this->set['Lm']);
		$this->db[$name] ['크기'] += 0.1;
		$this->save();
		$player->setScale($this->db[$name] ['크기']);
	}
	public function Down(Player $player) {
		$name = $player->getName();
		$eco = $this->getServer()->getPluginManager()->getPlugin('EconomyAPI');
		$eco->reduceMoney($player, $this->set['Sm']);
		$this->db[$name] ['크기'] -= 0.1;
		$this->save();
		$player->setScale($this->db[$name] ['크기']);
	}
	public function save() {
		$this->config->setAll($this->db);
		$this->setting->setAll($this->set);
		$this->config->save();
		$this->setting->save();
	}
	public function onMove(PlayerMoveEvent $event) {
		$player = $event->getPlayer();
		$x = (int) round($player->x - 0.5);
		$y = (int) round($player->y - 1);
		$z = (int) round($player->z - 0.5);
		$id = $player->getLevel()->getBlock(new Position((float) $x, (float) $y, (float) $z, $player->getLevel()))->getId();
		$data = $player->getLevel()->getBlock(new Position((float) $x, (float) $y, (float) $z, $player->getLevel()))->getDamage();
		if ($id === Block::REDSTONE_BLOCK and $data === 1) {
			$player->setScale(1);
			$player->sendTip('§c레드스톤 블럭을 밟아 크기가 1로 돌아갔습니다');
		}
	}
	public function onLevelChange(EntityLevelChangeEvent $event){
	    $player = $event->getEntity();
	    if($player instanceof Player){
	        if($event->getTarget()->getFolderName() === 'pvp'){
	            if($player->isOp()){
	                return;
                }
	            $player->setScale(1);
	            $player->sendMessage($this->prefix . 'PVP 에 입장하여 크기가 1로 돌아갔습니다');
            }
        }
    }
}