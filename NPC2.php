<?php
/**
 * @name NPC2
 * @author alvin0319
 * @main alvin0319\NPC2
 * @version 1.0.0
 * @api 4.0.0
 * @description NPC 플러그인
 */
namespace alvin0319;

use pocketmine\entity\EntityFactory;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
//한글깨짐방지

class NPC2 extends PluginBase implements Listener{
	private $prefix = '§d§l* §f엔피시 §7: §r';
	public function onEnable() : void{
		EntityFactory::register(CmdNPC::class, true);
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		//if(!dir_exists($this->getDataFolder()){
			@mkdir($this->getDataFolder());
			$this->config = new Config($this->getDataFolder() . 'Config.yml', Config::YAML);
			$this->db = $this->config->getAll();
			$cmd = new PluginCommand('엔피시', $this);
			$cmd->setDescription('엔피시 명령어 입니다');
			$this->getServer()->getCommandMap()->register('엔피시', $cmd);
		//}
	}
	public function createNPC($name){
	}
	public function onDamage(EntityDamageEvent $event){
		if($event instanceof EntityDamageByEntityEvent){
			$player = $event->getDamager();
			$npc = $event->getEntity();
			if($player instanceof Player and $npc instanceof CmdNPC){
				$nameTag = $npc->getNameTag();
				if(isset($this->db[$nameTag])){
					$event->setCancelled(true);
					if(isset($this->db[$nameTag] ['cmd'])){
						Server::getInstance()->dispatchCommand($player, $this->db[$nameTag] ['cmd']);
					}
					if(isset($this->db[$nameTag] ['msg'])){
						$player->addTitle($this->db[$nameTag] ['msg']);
					}
				}
			}
		}
	}
	public function onDisable() : void{
		$this->config->setAll($this->db);
		$this->config->save();
	}
	/**
	 * When we need create NPC, use this method
	 * @param Player $player
	 * @param string $name
	 * @return void
	 */
	public function spawnNPC(string $name, Player $player){
		$this->db[$name] = [];
		$inv = $player->getInventory();
		$arinv = $player->getArmorInventory();
		$nbt = new CompoundTag("", [
            new ListTag("Pos", [
                new DoubleTag("", $player->x),
                new DoubleTag("", $player->y),
                new DoubleTag("", $player->z) ]),
            new ListTag("Motion", [
                new DoubleTag("", 0),
                new DoubleTag("", 0),
                new DoubleTag("", 0) ]),
            new ListTag("Rotation",[
                new FloatTag(0, $player->getYaw()),
                new FloatTag(0, $player->getPitch())]),
            new CompoundTag("Skin", [
                "Data" => new StringTag("Data", $player->getSkin()->getSkinData()),
                "Name" => new StringTag("Name", $player->getSkin()->getSkinId()),
            ]),
        ]);
        $entity = EntityFactory::create(CmdNPC::class, $player->getLevel(), $nbt);
        $entity->setNameTag($name);
        $entity->setMaxHealth(100);
        $entity->setHealth(100);
        $einv = $entity->getInventory();
        $earinv = $entity->getArmorInventory();
        $einv->setItemInHand($inv->getItemInHand());
        $earinv->setHelmet($arinv->getHelmet());
        $earinv->setChestplate($arinv->getChestplate());
        $earinv->setLeggings($arinv->getLeggings());
        $earinv->setBoots($arinv->getBoots());
        $entity->setNameTagVisible(true);
        $entity->setNameTagAlwaysVisible(true);
        $entity->spawnToAll();
	}
	public function onDataPacket(DataPacketReceiveEvent $event){
		$pk = $event->getPacket();
		$player = $event->getPlayer();
		if($pk instanceof ModalFormResponsePacket){
			$id = $pk->formId;
			$data = json_decode($pk->formData, true);
			if($id === 1029345){
				if(!isset($data[0])){
					$player->sendMessage($this->prefix . '모든 칸을 정확히 입략해주세요');
					return;
				}
				if(isset($this->db[$data[0]])){
					$player->sendMessage($this->prefix . '해당 엔피시는 이미 존재합니다');
					return;
				}
				$this->spawnNPC($data[0], $player);
				$player->sendMessage($this->prefix . '생성했습니다');
			}elseif($id === 1029347){
				if(!isset($data[0])){
					$player->sendMessage($this->prefix . '모든 칸을 정확히 입략해주세요');
					return;
				}
				if(!isset($this->db[$data[0]])){
					$player->sendMessage($this->prefix . '해당 엔피시는 존재하지 않습니다');
					return;
				}
				$this->removeNPC($data[0]);
			}elseif($id === 1029346){
				if(!isset($data[0])){
					$player->sendMessage($this->prefix . '모든 칸을 정확히 입력해주세요');
					return;
				}
				if(!isset($data[1])){
					$player->sendMessage($this->prefix . '모든 칸을 정확히 입력해주세요');
					return;
				}
				if(!isset($data[2])){
					$player->sendMessage($this->prefix . '모든 칸을 정확히 입력해주세요');
					return;
				}
				if(!isset($this->db[$data[0]])){
					$player->sendMessage($this->prefix . '해당 엔피시는 존재하지 않습니다');
					return;
				}
				//$this->editNPC($data[0], $data[1], $data[2]);
				if($data[1] === '메시지'){
					$this->db[$data[0]] ['msg'] = $data[2];
				}elseif($data[1] === '명령어'){
					$this->db[$data[0]] ['cmd'] = $data[2];
				}
				$player->sendMessage($this->prefix . '수정되었습니다');
			}
		}
	}
	/**
	 * When we need remove Npc, use this method
	 * @param string $name
	 * @return void
	 */
	public function removeNPC(string $name){
		unset($this->db[$name]);
		foreach($this->getServer()->getLevelManager()->getLevels() as $lv){
			foreach($lv->getEntities() as $target){
				if($target instanceof CmdNPC and !isset($this->db[$target->getNameTag()])){
					$target->kill();
				}
			}
		}
	}
	public function sendUI(Player $player, $id, $data){
		$pk = new ModalFormRequestPacket();
		$pk->formId = $id;
		$pk->formData = $data;
		$player->dataPacket($pk);
	}/**
	public static function editNPC(string $name, $data, string $msg){
		if($data === '메시지'){
			$this->db[$name] ['msg'] = $msg;
		}elseif($data === '명령어'){
			$this->db[$name] ['cmd'] = $msg;
		}
	}
	*/
	public function MainData(){
		$encode = [
		'type' => 'custom_form',
		'title' => 'NPC',
		'content' => [
		[
		'type' => 'input',
		'text' => '엔피시의 이름을 넣어주세요'
		]
		]
		];
		return json_encode($encode);
	}
	public function SubData(){
		$encode = [
		'type' => 'custom_form',
		'title' => 'NPC',
		'content' => [
		[
		'type' => 'input',
		'text' => '엔피시의 이름을 넣어주세요'
		],
		[
		'type' => 'input',
		'text' => '기능을 넣어주세요. 기능은 "메시지", "명령어" 가 있습니다'
		],
		[
		'type' => 'input',
		'text' => '메시지를 넣어주세요'
		]
		]
		];
		return json_encode($encode);
	}
	public function SubData1(){
		$encode = [
		'type' => 'custom_form',
		'title' => 'NPC',
		'content' => [
		[
		'type' => 'input',
		'text' => '엔피시의 이름을 넣어주세요'
		]
		]
		];
		return json_encode($encode);
	}
	public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args) : bool{
		if($cmd->getName() === '엔피시'){
			if(!$sender->isOp()){
				return true;
			}
			if(!isset($args[0])){
				$sender->sendMessage($this->prefix . '/엔피시 [생성 | 제거 | 수정]');
				return true;
			}
			if($args[0] === '생성'){
				$this->sendUI($sender, 1029345, $this->MainData());
			}
			if($args[0] === '수정'){
				$this->sendUI($sender, 1029346, $this->SubData());
			}
			if($args[0] === '제거'){
				$this->sendUI($sender, 1029347, $this->SubData1());
			}
		}
		return true;
	}
}
class CmdNPC extends Human{
}