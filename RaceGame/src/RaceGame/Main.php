<?php
declare(strict_types=1);
namespace RaceGame;

use pocketmine\block\BlockIds;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\Task;
use pocketmine\utils\Config;

class Main extends PluginBase implements Listener{

    /** @var array|int */
    public $time = [];

    /** @var Config */
    protected $config;

    public $db;

    /** @var array|string */
    public $mode = [];

    public $prefix = "§d§l* §7달리기 : §r§7";

    public function onEnable(){
        $this->config = new Config($this->getDataFolder() . "Config.yml", Config::YAML, ["player" => [], "start" => false]);
        $this->db = $this->config->getAll();

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $command = new PluginCommand("달리기", $this);
        $command->setDescription("달리기 명령어입니다.");
        $command->setUsage("/달리기 [시작 | 시작지점설정 | 순위]");
        $this->getServer()->getCommandMap()->register("달리기", $command);
    }

    public function onDisable(){
        $this->config->setAll($this->db);
        $this->config->save();
    }

    public function start(Player $player){
        $this->mode[$player->getName()] = "start";
        $player->sendMessage($this->prefix . "§d10§7초뒤 달리기가 시작됩니다.");
        $a = explode(":", $this->db["start"]);
        $player->teleport(new Position((float) $a[0], (float) $a[1], (float) $a[2], $this->getServer()->getLevelManager()->getLevelByName($a[3])), $player->getYaw(), $player->getPitch());
        $this->getScheduler()->scheduleDelayedTask(new class($this, $player->getName()) extends Task{

            protected $plugin;

            protected $name;

            public function __construct(Main $plugin, string $name){
                $this->plugin = $plugin;
                $this->name = $name;
            }

            public function onRun(int $currentTick){
                $player = $this->plugin->getServer()->getPlayer($this->name);
                if(!$player instanceof Player){
                    unset($this->plugin->mode[$this->name]);
                    return;
                }
                unset($this->plugin->mode[$this->name]);
                $this->plugin->time[$player->getName()] = time();
                $player->sendMessage($this->plugin->prefix . "달리기가 시작되었습니다!");
                $a = explode(":", $this->plugin->db["start"]);
                $player->teleport(new Position((float) $a[0], (float) $a[1], (float) $a[2], $this->plugin->getServer()->getLevelManager()->getLevelByName($a[3])), $player->getYaw(), $player->getPitch());
                //$player->removeAllEffects();
            }
        }, 20 * 10);
    }

    public function onMove(PlayerMoveEvent $event){
        $player = $event->getPlayer();
        if(isset($this->mode[$player->getName()])){
            $player->sendTip("§c잠시후 시작됩니다.");
        }
        $block = $player->getLevel()->getBlock(new Vector3($player->getX(), $player->getY() - 1, $player->getZ()));
        if($block->getId() === BlockIds::OBSIDIAN){
            if(!isset($this->time[$player->getName()])){
                return;
            }
            $time = (int) $this->time[$player->getName()];
            $now = (int) time();
            $record = $now - $time;
            $date = date("i분 s초", $record);
            $player->sendMessage($this->prefix . $date . " 만에 달리기를 클리어 하셨습니다!");
            $player->getInventory()->addItem(Item::get(ItemIds::PRISMARINE_SHARD, 0, 1));
            $player->teleport($this->getServer()->getDefaultLevel()->getSafeSpawn());
            unset($this->time[$player->getName()]);
            //if($record < (int) $this->db["player"] [$player->getName()]){
            $this->db["player"] [$player->getName()] = $record;
            //}
        }
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if($command->getName() === "달리기"){
            if(!$sender instanceof Player){
                $sender->sendMessage($this->prefix . "콘솔에서는 사용하실수 없습니다.");
                return true;
            }
            if(!isset($args[0])){
                $sender->sendMessage($this->prefix . $command->getUsage());
                return true;
            }
            switch($args[0]){
                case "시작":
                    if($this->db["start"] === false){
                        $sender->sendMessage($this->prefix . "아직 달리기의 스폰이 정해지지 않았습니다.");
                        return true;
                    }
                    $this->start($sender);
                    break;
                case "시작지점설정":
                    if(!$sender->isOp()){
                        $sender->sendMessage($this->prefix . "권한이 부족합니다.");
                        return true;
                    }
                    $x = (int) $sender->getX();
                    $y = (int) $sender->getY();
                    $z = (int) $sender->getZ();
                    $level = $sender->getLevel()->getFolderName();
                    $this->db["start"] = $x . ":" . $y . ":" . $z . ":" . $level;
                    $sender->sendMessage($this->prefix . "설정되었습니다.");
                    break;
                case "순위":
                    /**
                     * Source code from 새나
                     * @link https://github.com/xodid8881/QuestTren_B1/blob/master/QuestTren_B1/src/Tren/Main.php#L85
                     */
                    $index = isset($args[1]) ? (int) $args[1] : 1;
                    $count = 0;
                    $rankindex = $index * 5 - 4;
                    $arr = [];
                    foreach($this->db["player"] as $name => $time){
                        $arr[$name] = $time;
                    }
                    asort($arr);
                    foreach($arr as $name => $time){
                        if(++$count >= ($index * 5 - 4) and $count <= ($index * 5)){
                            $sender->sendMessage("§d§l[ §f" . $rankindex++ . " §d] §r§7" . $name . " 님: " . date("i분 s초", (int) $time));
                        }
                    }
                    break;

                default:
                    $sender->sendMessage($this->prefix . $command->getUsage());
            }
        }
        return true;
    }

    public function onPlayerCommandPreprocess(PlayerCommandPreprocessEvent $event){
        $player = $event->getPlayer();
        if(substr($event->getMessage(), 0, 1) === "/"){
            if(isset($this->time[$player->getName()]) or isset($this->mode[$player->getName()])){
                $event->setCancelled(true);
                $player->sendMessage($this->prefix . "달리기 도중에는 명령어를 사용하실수 없습니다.");
            }
        }
    }

    public function onQuit(PlayerQuitEvent $event){
        $player = $event->getPlayer();
        if(isset($this->time[$player->getName()])){
            unset($this->time[$player->getName()]);
        }
        if(isset($this->mode[$player->getName()])){
            unset($this->mode[$player->getName()]);
        }
    }
}