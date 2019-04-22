<?php
declare(strict_types=1);
namespace delivery\event;

use pocketmine\event\Cancellable;
use pocketmine\event\player\PlayerEvent;
use pocketmine\Player;

class SendDeliveryEvent extends PlayerEvent implements Cancellable{

    protected $player;

    protected $receiver;

    public function __construct(Player $player, string $receiver){
        $this->player = $player;
        $this->receiver = $receiver;
    }
    public function getPlayer() : Player{
        return $this->player;
    }
    public function getTarget() : string{
        return $this->receiver;
    }
}