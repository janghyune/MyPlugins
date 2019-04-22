<?php
declare(strict_types=1);
namespace delivery;

use delivery\commands\MainCommand;
use delivery\event\GetDeliveryEvent;
use delivery\event\SendDeliveryEvent;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Delivery extends PluginBase{

    /** @var Config */
    protected $config;

    public $db;

    private static $instance = null;

    public function onLoad(){
        if(self::$instance === null) self::$instance = $this;
    }
    public function onEnable(){
        if(!is_dir($this->getDataFolder())){
            @mkdir($this->getDataFolder());
        }
        $this->config = new Config($this->getDataFolder() . 'Deliverys.yml', Config::YAML, [
            'player' => [],
        ]);
        $this->db = $this->config->getAll();
        date_default_timezone_set('Asia/Seoul');
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getServer()->getCommandMap()->register('delivery', new MainCommand($this));
    }

    public function onDisable(){
        $this->config->setAll($this->db);
        $this->config->save();
    }

    /**
     * @param Player $player
     * @param string $target
     * @throws \ReflectionException
     */
    public function addDelivery(Player $player, $target){
        $item = $player->getInventory()->getItemInHand();
        $event = new SendDeliveryEvent($player, $target);
        $event->call();
        if($event->isCancelled()){
            return;
        }
        $this->db['player'] [strtolower($target)] = [];
        $this->db['player'] [strtolower($target)] [strtolower($player->getName())] = [];
        $this->db['player'] [strtolower($target)] [strtolower($player->getName())] ['item'] = $item->jsonSerialize();
        $this->db['player'] [strtolower($target)] [strtolower($player->getName())] ['date'] = date('Y년 m월 d일 H시 i분 s초');
        $player->getInventory()->removeItem($item);
    }

    /**
     * @param Player $player
     * @param string $who
     * @throws \ReflectionException
     */
    public function getDelivery(Player $player, $who){
        $item = Item::jsonDeserialize($this->db['player'] [strtolower($player->getName())] [strtolower($who)] ['item']);
        $event = new GetDeliveryEvent($player);
        $event->call();
        if($event->isCancelled()){
            return;
        }
        $player->getInventory()->addItem($item);
        unset($this->db['player'] [strtolower($player->getName())] [strtolower($who)]);
    }

    /**
     * @param Player $player
     * @return array
     */
    public function getDeliveryList(Player $player) : array{
        $arr = [];
        foreach($this->db['player'] [strtolower($player->getName())] as $name => $value){
            array_push($arr, $name);
        }
        return $arr;
    }

    /**
     * @return Delivery
     */
    public static function getInstance() : Delivery{
        return self::$instance;
    }
}