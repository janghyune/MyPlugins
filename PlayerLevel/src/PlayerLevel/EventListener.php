<?php
declare(strict_types=1);
namespace PlayerLevel;

use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerExperienceChangeEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\Player;

class EventListener implements Listener{

    /** @var Main */
    protected $plugin;

    public function __construct(){
        $this->plugin = Main::getInstance();
    }

    public function onTransaction(InventoryTransactionEvent $event){
        $transaction = $event->getTransaction();
        $actions = $transaction->getActions();
        foreach($actions as $action){
            if($action instanceof SlotChangeAction){
                $inv = $action->getInventory();
                $target = $action->getTargetItem();
                $old = $action->getSourceItem();
                $player = $transaction->getSource()->getPlayer();
                if($inv instanceof ArmorInventory){
                    if($old->getId() === Item::SHULKER_SHELL){
                        $event->setCancelled(true);
                    }
                }
            }
        }
    }

    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        $armor = $player->getArmorInventory();
        $level = $player->getXpLevel();
        if($armor->getHelmet()->getId() === 0){
            if($level < (int) $this->plugin->db["helmet-level"]){
                $armor->setHelmet(ItemFactory::get(Item::SHULKER_SHELL)->setCustomName("§c잠긴칸")->setLore(["§b" . (int) $this->plugin->db["helmet-level"] . " §f레벨 달성시 잠금해제"]));
            }
        }
        if($armor->getChestplate()->getId() === 0){
            if($level < (int) $this->plugin->db["chest-plate-level"]){
                $armor->setChestplate(ItemFactory::get(Item::SHULKER_SHELL)->setCustomName("§c잠긴칸")->setLore(["§b" . (int) $this->plugin->db["chest-plate-level"] . " §f레벨 달성시 잠금해제"]));
            }
        }
        if($armor->getLeggings()->getId() === 0){
            if($level < (int) $this->plugin->db["pants-level"]){
                $armor->setLeggings(ItemFactory::get(Item::SHULKER_SHELL)->setCustomName("§c잠긴칸")->setLore(["§b" . (int) $this->plugin->db["pants-level"] . " §f레벨 달성시 잠금해제"]));
            }
        }
        if($armor->getBoots()->getId() === 0){
            if($level < (int) $this->plugin->db["boots-level"]){
                $armor->setBoots(ItemFactory::get(Item::SHULKER_SHELL)->setCustomName("§c잠긴칸")->setLore(["§b" . (int) $this->plugin->db["boots-level"] . " §f레벨 달성시 잠금해제"]));
            }
        }
    }

    public function onLevelUp(PlayerExperienceChangeEvent $event){
        $player = $event->getEntity();
        if($player instanceof Player){
            $armor = $player->getArmorInventory();
            $level = $event->getNewLevel();
            $tag = "§d§l* §7레벨 : §r§7";
            if($armor->getHelmet()->getId() === Item::SHULKER_SHELL){
                if($level >= (int) $this->plugin->db["helmet-level"]){
                    $armor->setHelmet(ItemFactory::get(0));
                    $player->sendMessage($tag . $level . " 레벨을 달성하여 투구 칸이 풀렸습니다!");
                }
            }
            if($armor->getChestplate()->getId() === Item::SHULKER_SHELL){
                if($level >= (int) $this->plugin->db["chest-plate-level"]){
                    $armor->setChestplate(ItemFactory::get(0));
                    $player->sendMessage($tag . $level . " 레벨을 달성하여 갑옷 칸이 풀렸습니다!");
                }
            }
            if($armor->getLeggings()->getId() === Item::SHULKER_SHELL){
                if($level >= (int) $this->plugin->db["pants-level"]){
                    $armor->setLeggings(ItemFactory::get(0));
                    $player->sendMessage($tag . $level . " 레벨을 달성하여 바지 칸이 풀렸습니다!");
                }
            }
            if($armor->getBoots()->getId() === Item::SHULKER_SHELL){
                if($level >= (int) $this->plugin->db["boots-level"]){
                    $armor->setBoots(ItemFactory::get(0));
                    $player->sendMessage($tag . $level . " 레벨을 달성하여 신발 칸이 풀렸습니다!");
                }
            }
        }
    }
}