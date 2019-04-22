<?php
/**
 * @name Passive
 * @author alvin0319
 * @main alvin0319\Passive
 * @version 1.0.0
 * @api 4.0.0
 */
declare(strict_types=1);
namespace alvin0319;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\entity\effect\Effect;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\Task;
use pocketmine\utils\Config;

class Passive extends PluginBase implements Listener{

    protected $ch = [];

    /** @var Config */
    protected $config;

    public $db;

    protected function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getScheduler()->scheduleRepeatingTask(new class($this) extends Task{ protected $plugin; public function __construct(Passive $plugin){ $this->plugin = $plugin; } public function onRun(int $currentTick){ $this->plugin->save(); }}, 1200);
        $this->config = new Config($this->getDataFolder() . "Config.yml", Config::YAML, [
            "player" => [],
            "mode" => []
        ]);
        $this->db = $this->config->getAll();
        $command = new PluginCommand("패시브", $this);
        $command->setDescription("패시브 명령어입니다.");
        $command->setUsage("/패시브 [활성화 | 비활성화]");
        $this->getServer()->getCommandMap()->register("패시브", $command);
    }

    protected function onDisable(){
        $this->save();
    }

    public function save(){
        $this->config->setAll($this->db);
        $this->config->save();
    }

    public function onLogin(PlayerLoginEvent $event){
        $player = $event->getPlayer();
        if(!isset($this->db["player"] [$player->getName()])){
            $this->db["player"] [$player->getName()] = false;
        }
        if(!isset($this->db["mode"] [$player->getName()])){
            $this->db["mode"] [$player->getName()] = "활성화";
        }
    }

    public function onMove(PlayerMoveEvent $event){
        $player = $event->getPlayer();
        if(!isset($this->db["player"] [$player->getName()])){
            $this->db["player"] [$player->getName()] = false;
            return;
        }
        if($this->db["player"] [$player->getName()] === false){
            $this->UI($player);
        }
    }

    public function UI(Player $player) : bool{
        $encode = ["type" => "form", "title" => "패시브 선택창", "content" => "패시브를 선택해주세요!\n한번 선택하면 다시는 변경 못하니 신중히 선택해주세요!", "buttons" => [["text" => "신속"], ["text" => "점프"]]];
        $packet = new ModalFormRequestPacket();
        $packet->formId = 9999123;
        $packet->formData = json_encode($encode);
        return $player->sendDataPacket($packet);
    }

    public function onTeleport(EntityTeleportEvent $event){
        $entity = $event->getEntity();
        if($entity instanceof Player){
            if(!isset($this->db["player"] [$entity->getName()])){
                $this->db["player"] [$player->getName()] = false;
            }
            if($this->db["mode"] [$entity->getName()] === "비활성화"){
                return;
            }
            if($this->db["player"] [$entity->getName()] === "신속"){
                $entity->addEffect(new EffectInstance(Effect::getEffect(Effect::SPEED), 99999999, 1));
            }elseif($this->db["player"] [$entity->getName()] === "점프"){
                $entity->addEffect(new EffectInstance(Effect::getEffect(Effect::JUMP), 99999999, 2));
            }
        }
    }

    public function onRespawn(PlayerRespawnEvent $event){
        $this->getScheduler()->scheduleTask(new class($this, $event->getPlayer()) extends Task{

            /** @var Player */
            protected $player;

            protected $db;

            public function __construct(Passive $plugin, Player $player){
                $this->player = $player;
                $this->db = $plugin->db;
            }
            public function onRun(int $currentTick){
                $entity = $this->player;
                if(!isset($this->db["player"] [$entity->getName()])){
                    $this->db["player"] [$player->getName()] = false;
                }
                if($this->db["mode"] [$entity->getName()] === "비활성화"){
                    return;
                }
                if($this->db["player"] [$entity->getName()] === "신속"){
                    $entity->addEffect(new EffectInstance(Effect::getEffect(Effect::SPEED), 99999999, 1));
                }elseif($this->db["player"] [$entity->getName()] === "점프"){
                    $entity->addEffect(new EffectInstance(Effect::getEffect(Effect::JUMP), 99999999, 2));
                }
            }
        });
    }

    public function onJoin(PlayerJoinEvent $event){
        $entity = $event->getPlayer();
        if(!isset($this->db["player"] [$entity->getName()])){
            $this->db["player"] [$player->getName()] = false;
        }
        if($this->db["mode"] [$entity->getName()] === "비활성화"){
            return;
        }
        if($this->db["player"] [$entity->getName()] === "신속"){
            $entity->addEffect(new EffectInstance(Effect::getEffect(Effect::SPEED), 99999999, 1));
        }elseif($this->db["player"] [$entity->getName()] === "점프"){
            $entity->addEffect(new EffectInstance(Effect::getEffect(Effect::JUMP), 99999999, 2));
        }//elseif($this->db["player"] [$entity->getName()] === "재생"){
            //$entity->addEffect(new EffectInstance(Effect::getEffect(Effect::HEALING), 99999999, 2));
        //}else{
            //return;
        //}
    }

    public function onPacket(DataPacketReceiveEvent $event){
        $packet = $event->getPacket();
        $player = $event->getPlayer();
        if($packet instanceof ModalFormResponsePacket){
            $id = $packet->formId;
            $data = json_decode($packet->formData, true);
            if($id === 9999123){
                if($data === 0){
                    $this->ch[$player->getName()] = "신속";
                    $this->UI2($player);
                }elseif($data === 1){
                    $this->ch[$player->getName()] = "점프";
                    $this->UI2($player);
                }//elseif($data === 2){
                    //$this->ch[$player->getName()] = "재생";
                    //$this->UI2($player);
                //}
            }elseif($id === 9999121){
                if(!$data){
                    $this->UI($player);
                    unset($this->ch[$player->getName()]);
                    return;
                }
                if($this->db["player"] [$player->getName()] !== false){
                    $player->sendMessage("잘못된 요청입니다.");
                    return;
                }
                $this->db["player"] [$player->getName()] = $this->ch[$player->getName()];
                $player->sendMessage("선택하였습니다.");
                if($this->db["player"] [$player->getName()] === "신속"){
                    $player->addEffect(new EffectInstance(Effect::getEffect(Effect::SPEED), 99999999, 1));
                }elseif($this->db["player"] [$player->getName()] === "점프"){
                    $player->addEffect(new EffectInstance(Effect::getEffect(Effect::JUMP), 99999999, 2));
                }
            }
        }
    }

    public function UI2(Player $player) : bool{
        $encode = ["type" => "modal", "title" => "정말 " . $this->ch[$player->getName()] . " 을(를) 선택하시겠습니까?", "content" => '고르시려면 예 를, 취소하시려면 아니오 를 눌러주세요.', "button1" => "예", "button2" => "아니오"];
        $packet = new ModalFormRequestPacket();
        $packet->formId = 9999121;
        $packet->formData = json_encode($encode);
        return $player->sendDataPacket($packet);
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if($command->getName() === "패시브"){
            if(!$sender instanceof Player) return true;
            if(!isset($args[0])){
                $sender->sendMessage($command->getUsage());
                return true;
            }
            switch($args[0]){
                case "활성화":
                    $this->db["mode"] [$sender->getName()] = "활성화";
                    $sender->sendMessage("패시브가 활성화되었습니다.");
                    if($this->db["player"] [$sender->getName()] === "신속"){
                        $sender->addEffect(new EffectInstance(Effect::getEffect(Effect::SPEED), 99999999, 1));
                    }elseif($this->db["player"] [$sender->getName()] === "점프"){
                        $sender->addEffect(new EffectInstance(Effect::getEffect(Effect::JUMP), 99999999, 2));
                    }
                    break;
                case "비활성화":
                    $this->db["mode"] [$sender->getName()] = "비활성화";
                    $sender->sendMessage("패시브가 비활성화되었습니다.");
                    if($this->db["player"] [$sender->getName()] === "신속"){
                        $sender->removeEffect(Effect::SPEED);
                    }elseif($this->db["player"] [$sender->getName()] === "점프"){
                        $sender->removeEffect(Effect::JUMP);
                    }
                    break;
                default:
                    $sender->sendMessage($command->getUsage());
            }
        }
        return true;
    }
}