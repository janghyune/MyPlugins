<?php
declare(strict_types=1);
namespace delivery\event;

use pocketmine\event\Cancellable;
use pocketmine\event\player\PlayerEvent;
use pocketmine\Player;

class GetDeliveryEvent extends PlayerEvent implements Cancellable{

    protected $player;

    public function __construct(Player $player){
        $this->player = $player;
    }
    public function getPlayer() : Player{
        return $this->player;
    }
}