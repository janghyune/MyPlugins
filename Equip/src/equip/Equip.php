<?php
declare(strict_types=1);
namespace equip;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\Armor;
use pocketmine\item\Item;
use pocketmine\nbt\tag\IntTag;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;

class Equip extends PluginBase implements Listener{

    /** @var Config */
    protected $config;

    public $db;

    /** @var string */
    public static $prefix = '§l§d* §f장비§7 : §r§7';

    public function onEnable() {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        @mkdir($this->getDataFolder());
        $this->config = new Config($this->getDataFolder() . 'Config.yml', Config::YAML, [
            'equip' => [],
            'box' => []
        ]);
        $this->db = $this->config->getAll();
        $command = new PluginCommand('장비', $this);
        $command->setDescription('장비 명령어입니다');
        $this->getServer()->getCommandMap()->register('장비', $command);
    }

    public function onDisable() {
        $this->config->setAll($this->db);
        $this->config->save();
    }

    /**
     * @param string $name
     */
    public function deleteEquip(string $name) {
        unset($this->db['equip'] [$name]);
    }

    /**
     * 장비를 추가할때 쓰는 소스
     * @param Player $player
     * @param Item $item
     * @param string $name
     * @param string $mode
     * @param int $damage
     */
    public function addEquip(Player $player, Item $item, string $name, string $mode, int $damage, int $per) {
        $this->db['equip'] [$name] = [];
        $this->db['equip'] [$name] ['id'] = $item->getId();
        $this->db['equip'] [$name] ['damage'] = $item->getDamage();
        $this->db['equip'] [$name] ['per'] = $per;
        if ($mode === '공격') {
            $this->db['equip'] [$name] ['attack'] = $damage;
            $this->db['equip'] [$name] ['defence'] = 0;
        } elseif ($mode === '방어') {
            $this->db['equip'] [$name] ['attack'] = 0;
            $this->db['equip'] [$name] ['defence'] = $damage;
        }
        $player->getInventory()->setItemInHand($this->getEquip($name));
    }

    /**
     * 모든 장비를 얻어오는 소스
     * @return array
     */
    public function getAllEquips() : array {
        $arr = [];
        foreach ($this->db['equip'] as $name => $value) {
            array_push($arr, $name);
        }
        return $arr;
    }

    /**
     * @return array
     */
    public function getAllBoxEquip() : array{
        $arr = [];
        foreach ($this->db['box'] as $name => $value) {
            array_push($arr, $name);
        }
        return $arr;
    }

    /**
     * 장비를 얻는 소스
     * @param string $name
     * @return Item
     */
    public function getEquip(string $name) : Item{
        $db = $this->db['equip'] [$name];
        $item = Item::get((int) $db['id'], (int) $db['damage'], 1);
        $attack = (int) $db['attack'];
        $defence = (int) $db['defence'];
        $item->setCustomName($name);
        $lore = [
                '§l§d* §f장비 스탯',
                '§d* §f공격력 : §d' . $attack,
                '§d* §f방어력 : §d' . $defence
        ];
        $item->setLore($lore);
        $item->setNamedTagEntry(new IntTag('attack', $attack));
        $item->setNamedTagEntry(new IntTag('defence', $defence));
        return $item;
    }

    /**
    public function onDamage(EntityDamageEvent $event) {
        $entity = $event->getEntity();
        if ($entity instanceof Player) {
            foreach ($entity->getArmorInventory()->getContents(false) as $item) {
                //var_dump(is_null($item->getNamedTagEntry('defence')));
                //var_dump($event->getBaseDamage());
                if ($item->getNamedTagEntry('defence') !== null && isset($this->db['equip'] [$item->getCustomName()])) {
                    $defence = $item->getNamedTagEntry('defence');
                    $damage = $event->getFinalDamage() * $defence / 2;
                    //var_dump($damage);
                    $event->setBaseDamage($damage);
                    var_dump($event->getBaseDamage());
                }
            }
        }
    }
    */

    /**
     * @param EntityDamageByEntityEvent $event
     */
    public function onDamageByEntity(EntityDamageByEntityEvent $event) {
        $player = $event->getDamager();
        if ($player instanceof Player) {
            $item = $player->getInventory()->getItemInHand();
            if ($this->isEquip($item)) {
                $attack = $item->getNamedTagEntry('attack')->getValue();
                $damage = $event->getFinalDamage() + (0.5 * $attack);
                $event->setBaseDamage($damage);
            }
        }
    }

    /**
     * @param EntityDamageByChildEntityEvent $event
     */
    public function onDamageByChild(EntityDamageByChildEntityEvent $event) {
        $player = $event->getDamager();
        if ($player instanceof Player) {
            $item = $player->getInventory()->getItemInHand();
            if ($this->isEquip($item)) {
                $attack = $item->getNamedTagEntry('attack')->getValue();
                $damage = $event->getFinalDamage() + (0.5 * $attack);
                $event->setBaseDamage($damage);
            }
        }
    }

    /**
     * @return Item
     */
    public function random() : Item{
        $arr = [];
        foreach ($this->getAllEquips() as $equip) {
            $per = $this->db['equip'] [$equip] ['per'];
            for ($i = 0; $i < $per; $i++) {
                $arr[] = $equip;
            }
        }
        $count = count($arr);
        $rand = mt_rand(0, $count - 1);
        $result = $arr[$rand];
        return $this->getEquip((string) $result);
    }

    /**
     * @param Item $item
     * @return bool
     */
    public function isEquip(Item $item) : bool{
        foreach($this->getAllEquips() as $equip){
            if (($item->getNamedTagEntry('attack') or $item->getNamedTagEntry('defence')) !== null and preg_match("~{$item->getName()}([^|]+)~", $equip, $text)) {
                return true;
            }else{
                return false;
            }
        }
        return false;
    }

    /**
     * @param string $name
     */
    public function addEquipBox(string $name){
        $this->db['box'] [$name] = true;
    }

    /**
     * @param CommandSender $sender
     * @param Command $command
     * @param string $label
     * @param array $args
     * @return bool
     */
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if ($command->getName() === '장비') {
            if (!$sender instanceof Player) {
                return true;
            }
            if (!$sender->isOp()) {
                $sender->sendMessage(Equip::$prefix . '권한이 없습니다.');
                return true;
            }
            if (!isset($args[0])) {
                $args[0] = 'x';
            }
            $mode = array_shift($args);
            switch ($mode) {
                case '추가':
                    $item = $sender->getInventory()->getItemInHand();
                    if ($item->getId() === 0) {
                        $sender->sendMessage(Equip::$prefix . '아이템의 아이디는 공기가 아니여야 합니다');
                        return true;
                    }
                    $name = array_shift($args);
                    $mode1 = array_shift($args);
                    $damage = array_shift($args);
                    $per = array_shift($args);
                    if (!isset($name)) {
                        $sender->sendMessage(Equip::$prefix . '/장비 생성 [이름|string] [공격/방어|string] [수치|int] [확률|int]');
                        return true;
                    }
                    if (isset($this->db['equip'] [$name])) {
                        $sender->sendMessage(Equip::$prefix . '해당 장비는 이미 추가되어 있습니다.');
                        return true;
                    }
                    if (!isset($mode1)) {
                        $sender->sendMessage(Equip::$prefix . '장비가 가질 능력을 골라주세요! 능력은 공격 과 방어가 있습니다.');
                        return true;
                    }
                    switch ($mode1) {
                        case '공격':
                            if (!isset($damage)) {
                                $sender->sendMessage(Equip::$prefix . '장비가 추가로 줄 데미지의 양을 입력해주세요.');
                                return true;
                            }
                            if (!is_numeric($damage)) {
                                $sender->sendMessage(Equip::$prefix . '데미지의 양은 숫자여야 합니다.');
                                return true;
                            }
                            if (!isset($per)) {
                                $sender->sendMessage(Equip::$prefix . '장비가 뽑힐 확률을 써주세요. 숫자가 클수록 확률이 작아집니다.');
                                return true;
                            }
                            if (!is_numeric($per)) {
                                $sender->sendMessage(Equip::$prefix . '확률은 숫자여야 합니다.');
                                return true;
                            }
                            $this->addEquip($sender, $item, $name, $mode1, (int) $damage, (int) $per);
                            $sender->sendMessage(Equip::$prefix . '장비가 추가되었습니다.');
                            break;
                        case '방어':
                            if (!$item instanceof Armor) {
                                $sender->sendMessage(Equip::$prefix . '방어 장비는 오직 방어구만 추가 가능합니다.');
                                return true;
                            }
                            if (!isset($damage)) {
                                $sender->sendMessage(Equip::$prefix . '장비가 추가로 방어할 데미지의 양을 입력해주세요.');
                                return true;
                            }
                            if (!is_numeric($damage)) {
                                $sender->sendMessage(Equip::$prefix . '데미지의 양은 숫자여야 합니다.');
                                return true;
                            }
                            if ($damage <= 2) {
                                $sender->sendMessage(Equip::$prefix . '데미지는 2 이상이어야 합니다');
                                return true;
                            }
                            if (!isset($per)) {
                                $sender->sendMessage(Equip::$prefix . '장비가 뽑힐 확률을 써주세요. 숫자가 클수록 확률이 작아집니다.');
                                return true;
                            }
                            if (!is_numeric($per)) {
                                $sender->sendMessage(Equip::$prefix . '확률은 숫자여야 합니다.');
                                return true;
                            }
                            $this->addEquip($sender, $item, $name, $mode1, (int) $damage, (int) $per);
                            $sender->sendMessage(Equip::$prefix . '장비가 추가되었습니다.');
                            break;
                        default:
                            $sender->sendMessage(Equip::$prefix . '장비 종류는 공격 과 방어 가 있습니다.');
                    }
                    break;
                case '제거':
                    $arr = [];
                    foreach ($this->getAllEquips() as $equip) {
                        array_push($arr, array('text' => '- ' . $equip));
                    }
                    $form = [];
                    $form['type'] = 'form';
                    $form['title'] = '장비 목록';
                    $form['content'] = '장비 이름을 클릭하시면 장비를 제거합니다!';
                    $form['buttons'] = $arr;
                    $packet = new ModalFormRequestPacket();
                    $packet->formId = 99900;
                    $packet->formData = json_encode($form);
                    $sender->sendDataPacket($packet);
                    break;
                case '목록':
                    $arr = [];
                    foreach ($this->getAllEquips() as $equip) {
                        array_push($arr, array('text' => '- ' . $equip));
                    }
                    $form = [];
                    $form['type'] = 'form';
                    $form['title'] = '장비 목록';
                    $form['content'] = '장비 이름을 클릭하시면 장비를 얻습니다';
                    $form['buttons'] = $arr;
                    $packet = new ModalFormRequestPacket();
                    $packet->formId = 9999;
                    $packet->formData = json_encode($form);
                    $sender->sendDataPacket($packet);
                    break;
                case '핫타임아이템주기':
                    $arr = [];
                    foreach ($this->getAllEquips() as $equip) {
                        array_push($arr, array('text' => '- ' . $equip));
                    }
                    $form = [];
                    $form['type'] = 'form';
                    $form['title'] = '장비 목록';
                    $form['content'] = '장비 이름을 클릭하시면 해당 장비를 모든 플레이어가 얻습니다';
                    $form['buttons'] = $arr;
                    $packet = new ModalFormRequestPacket();
                    $packet->formId = 99990;
                    $packet->formData = json_encode($form);
                    $sender->sendDataPacket($packet);
                    break;
                default:
                    $sender->sendMessage(Equip::$prefix . '/장비 추가 [이름] [공격/방어] [데미지] [확률]');
                    $sender->sendMessage(Equip::$prefix . '/장비 제거');
                    $sender->sendMessage(Equip::$prefix . '/장비 목록');
                    $sender->sendMessage(Equip::$prefix . '/장비 핫타임아이템주기');
            }
        }
        return true;
    }

    /**
     * @param DataPacketReceiveEvent $event
     */
    public function onPacketReceive(DataPacketReceiveEvent $event) {
        $packet = $event->getPacket();
        $player = $event->getPlayer();
        if ($packet instanceof ModalFormResponsePacket) {
            $id = $packet->formId;
            $data = json_decode($packet->formData, true);
            if ($id === 9999) {
                if ($data !== null) {
                    $arr = [];
                    foreach ($this->getAllEquips() as $equip) {
                        array_push($arr, $equip);
                    }
                    $player->getInventory()->addItem($this->getEquip((string) $arr[$data]));
                    $player->sendMessage(Equip::$prefix . '장비를 얻었습니다');
                }
            } elseif ($id === 99990) {
                if ($data !== null) {
                    $arr = [];
                    foreach ($this->getAllEquips() as $equip) {
                        array_push($arr, $equip);
                    }
                    foreach ($this->getServer()->getOnlinePlayers() as $onlinePlayer) {
                        $onlinePlayer->getInventory()->addItem($this->getEquip((string) $arr[$data]));
                    }
                    Server::getInstance()->broadcastMessage(Equip::$prefix . '관리자가 ' . (string) $arr[$data] . ' 장비를 지급하였습니다.');
                }
            } elseif ($id === 999900) {
                if ($data !== null){
                    $arr = [];
                    foreach ($this->getAllEquips() as $equip) {
                        array_push($arr, $equip);
                    }
                    $this->deleteEquip((string) $arr[$data]);
                    $player->sendMessage(Equip::$prefix . '장비가 제거되었습니다');
                }
            }
        }
    }

    /**
     * @param PlayerInteractEvent $event
     */
    public function onInteract(PlayerInteractEvent $event){
        $player = $event->getPlayer();
        if ($event->getAction() === $event::RIGHT_CLICK_BLOCK) {
            $item = $player->getInventory()->getItemInHand();
            $inv = $player->getInventory();
            switch ($item->getId()) {
                case 399:
                    $item->setCount(1);
                    switch ($item->getDamage()) {
                        case 15:
                            if (!$player->getInventory()->contains(Item::get(437, 15, 1))) {
                                $player->sendMessage(Equip::$prefix . '장비박스 열쇠가 부족합니다!');
                                return;
                            }
                            $random = $this->random();
                            if (!$inv->canAddItem($random)) {
                                $player->sendMessage(Equip::$prefix . '인벤토리를 비워주세요!');
                                return;
                            }
                            $inv->removeItem($item);
                            $inv->removeItem(Item::get(437, 15, 1));
                            $inv->addItem($random);
                            $player->sendMessage(Equip::$prefix . $random->getCustomName() . ' 이(가) 뽑혔습니다!');
                            break;
                        case 10:
                            $item->setCount(1);
                            $player->getInventory()->removeItem($item);
                            $player->getInventory()->addItem(Item::get(399, 15, 1));
                            $player->sendMessage(Equip::$prefix . '장비 박스 1 개가 교환되었습니다.');
                            break;
                        case 11:
                            $item->setCount(1);
                            $player->getInventory()->removeItem($item);
                            $player->getInventory()->addItem(Item::get(399, 15, 1));
                            $player->sendMessage(Equip::$prefix . '장비 박스 1 개가 교환되었습니다.');
                            break;
                        case 12:
                            $item->setCount(1);
                            $player->getInventory()->removeItem($item);
                            $player->getInventory()->addItem(Item::get(399, 15, 1));
                            $player->sendMessage(Equip::$prefix . '장비 박스 1 개가 교환되었습니다.');
                            break;
                    }
                    break;
                case 437:
                    switch ($item->getDamage()) {
                        case 10:
                            $item->setCount(1);
                            $player->getInventory()->removeItem($item);
                            $player->getInventory()->addItem(Item::get(437, 15, 1));
                            $player->sendMessage(Equip::$prefix . '장비 열쇠 1 개가 교환되었습니다.');
                            break;
                        case 11:
                            $item->setCount(1);
                            $player->getInventory()->removeItem($item);
                            $player->getInventory()->addItem(Item::get(437, 15, 1));
                            $player->sendMessage(Equip::$prefix . '장비 열쇠 1 개가 교환되었습니다.');
                            break;
                        case 12:
                            $item->setCount(1);
                            $player->getInventory()->removeItem($item);
                            $player->getInventory()->addItem(Item::get(437, 15, 1));
                            $player->sendMessage(Equip::$prefix . '장비 열쇠1 개가 교환되었습니다.');
                            break;
                    }
                    break;
            }
        }
    }
}