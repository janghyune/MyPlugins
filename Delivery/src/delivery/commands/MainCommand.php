<?php
declare(strict_types=1);
namespace delivery\commands;

use delivery\Delivery;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\Player;

class MainCommand extends Command{

    protected $plugin;

    public function __construct(Delivery $plugin){
        $this->plugin = $plugin;
        parent::__construct('택배', '택배 관련 명령어입니다', '/택배', ['delivery']);
    }
    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if($sender instanceof Player){
            $this->MainUI($sender);
        }
        return true;
    }
    public function MainUI(Player $player){
        $encode = [
            'type' => 'form',
            'title' => '원하시는 항목을 선택해주세요!',
            'content' => '택배',
            'buttons' => [
                [
                    'text' => '나가기'
                ],
                [
                    'text' => '택배 보내기'
                ],
                [
                    'text' => '택배 받기'
                ]
            ]
        ];
        $packet = new ModalFormRequestPacket();
        $packet->formId = 70;
        $packet->formData = json_encode($encode);
        $player->sendDataPacket($packet);
    }
}