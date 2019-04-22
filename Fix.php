<?php
/**
 * @name Fix
 * @author alvin0319
 * @main alvin0319\Fix
 * @version 1.0.0
 * @api 4.0.0
 */
declare(strict_types=1);
namespace alvin0319;

use alvin0319\trident\item\Trident;
use onebone\economyapi\EconomyAPI;
use pocketmine\block\Block;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Armor;
use pocketmine\item\Axe;
use pocketmine\item\Bow;
use pocketmine\item\Durable;
use pocketmine\item\Pickaxe;
use pocketmine\item\Sword;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Fix extends PluginBase implements Listener{

    /** @var Config */
    private $config;

    /** @var Config */
    private $db;

    public function onEnable(){
        @mkdir($this->getDataFolder());
        $this->config = new Config($this->getDataFolder() . 'Config.yml', Config::YAML, [
            'money' => '5000',
            'rand' => 60,
            'prefix' => '§d* §f수리 §r§f'
        ]);
        $this->db = $this->config->getAll();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onInteract(PlayerInteractEvent $event){
        $item = $event->getItem();
        $player = $event->getPlayer();
        $block = $event->getBlock();
        if($block->getId() === Block::COMMAND_BLOCK){
            ///if(!$item instanceof Tool){
                ///$player->sendMessage($this->db['prefix'] . '수리는 도구만 가능합니다');
                ///return;
            ///}
            if(($item instanceof Tool or $item instanceof Sword or $item instanceof Axe or $item instanceof Armor or $item instanceof Pickaxe or $item instanceof Trident or $item instanceof Bow) && $item instanceof Durable){
                //$economy = $this->getServer()->getPluginManager()->getPlugin('EconomyAPI');
                if($item->getDamage() <= 0){
                    $player->sendMessage($this->db['prefix'] . '해당 아이템은 내구도가 닳지 않았습니다');
                    return;
                }
                $economy = EconomyAPI::getInstance();
                if($economy->myMoney($player) < $this->db['money']){
                    $player->sendMessage($this->db['prefix'] . '돈이 부족합니다. 수리에 필요한 비용: ' . $this->db['money'] . '원');
                    return;
                }
                $rand = mt_rand(1, 100);
                if($rand > $this->db['rand']){
                    $economy->reduceMoney($player, $this->db['money']);
                    $item->setDamage(0);
                    $player->getInventory()->setItemInHand($item);
                    $player->sendMessage($this->db['prefix'] . '아이템이 수리되었습니다');
                    //var_dump($rand);
                }else{
                    $economy->reduceMoney($player, $this->db['money']);
                    $player->sendMessage($this->db['prefix'] . '기계가 고장이 나서 수리에 실패하였습니다');
                    //var_dump($rand);
                }
            }
        }
    }
}