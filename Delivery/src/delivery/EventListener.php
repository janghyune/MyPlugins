<?php
declare(strict_types=1);
namespace delivery;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

/**
 * Class EventListener
 * @package delivery
 */
class EventListener implements Listener{

    protected $prefix = '§d<§f시스템§d> §r§f';

    /** @var Delivery */
    protected $plugin;

    /**
     * EventListener constructor.
     * @param Delivery $plugin
     */
    public function __construct(Delivery $plugin){
        $this->plugin = $plugin;
    }

    /**
     * @param DataPacketReceiveEvent $event
     * @throws \ReflectionException
     */
    public function onPacketReceive(DataPacketReceiveEvent $event){
        $packet = $event->getPacket();
        $player = $event->getPlayer();
        if($packet instanceof ModalFormResponsePacket){
            $id = $packet->formId;
            $data = json_decode($packet->formData, true);
            if($id === 70){
                if($data === 0){
                    $player->sendMessage($this->prefix . '택배 UI 에서 나왔습니다');
                }elseif($data === 1){
                    $this->SendDelivery($player);
                }elseif($data === 2){
                    $this->ReceiveDelivery($player);
                }
            }elseif($id === 71){
                if(!isset($data[0])){
                    $player->sendMessage($this->prefix . '이름을 입력해주세요');
                    return;
                }
                if(!isset($this->plugin->db['player'] [strtolower($data[0])])){
                    $player->sendMessage($this->prefix . '해당 유저는 서버에서 찾아볼수 없습니다');
                    return;
                }
                if(isset($this->plugin->db['player'] [strtolower($data[0])])){
                    $player->sendMessage($this->prefix . '이 유저는 아직 당신이 보낸 택배를 받지 않았습니다');
                    return;
                }
                if($player->getInventory()->getItemInHand()->getId() === 0){
                    $player->sendMessage($this->prefix . '아이템 아이디는 공기가 아니어야 합니다');
                    return;
                }
                $this->plugin->addDelivery($player, $data[0]);
                $player->sendMessage($this->prefix . $data[0] . ' 님 에게 택배를 발송하였습니다');
                $target = $this->plugin->getServer()->getPlayer($data[0]);
                if($target instanceof Player){
                    $target->sendMessage($this->prefix . $player->getName() . ' 님으로부터 택배가 도착했습니다');
                }
            }elseif($id === 72){
                if($data !== null){
                    $arr = [];
                    foreach($this->plugin->db['player'] [strtolower($player->getName())] as $name => $value){
                        array_push($arr, $name);
                    }
                    $this->plugin->getDelivery($player, $arr[$data]);
                    $player->sendMessage($this->prefix . '택배를 받았습니다');
                }
            }
        }
    }

    /**
     * @param PlayerJoinEvent $event
     */
    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        $name = strtolower($player->getName());
        if(!isset($this->plugin->db['player'] [$name])){
            $this->plugin->db['player'] [$name] = [];
        }
        $count = 0;
        foreach($this->plugin->db['player'] [$name] as $name => $value){
            $count++;
        }
        if($count !== 0){
            $player->sendMessage($this->prefix . $count . '개의 택배가 도착해있습니다');
        }
    }

    public function ReceiveDelivery(Player $player){
        /** @var array */
        $arr = [];
        foreach($this->plugin->db['player'] [strtolower($player->getName())] as $name => $value){
            $date = $this->plugin->db['player'] [strtolower($player->getName())] [$name] ['date'];
            array_push($arr, array('text' => '- ' . $name . TextFormat::EOL . '보낸날짜: ' . $date));
        }
        /** @var  array */
        $encode = [
            'type' => 'form',
            'title' => '택배를 받아보아요',
            'content' => '받기를 원하는 택배를 선택해주세요!',
            'buttons' => $arr
        ];
        $packet = new ModalFormRequestPacket();
        $packet->formId = 72;
        $packet->formData = json_encode($encode);
        $player->sendDataPacket($packet);
    }
    public function SendDelivery(Player $player){
        $encode = [
            'type' => 'custom_form',
            'title' => '택배를 보내보아요',
            'content' => [
                [
                    'type' => 'input',
                    'text' => '받을 사람의 닉네임을 입력해주세요!'
                ]
            ]
        ];
        $packet = new ModalFormRequestPacket();
        $packet->formId = 71;
        $packet->formData = json_encode($encode);
        $player->sendDataPacket($packet);
    }
}