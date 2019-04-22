<?php
declare(strict_types=1);
namespace PlayerLevel;

use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

function convert($player){
    return $player instanceof Player ? $player->getName() : $player;
}
class Main extends PluginBase{

    /** @var Config */
    protected $config;

    public $db;

    protected static $instance = null;

    public function onLoad() {
        self::$instance = $this;
    }

    public static function getInstance() : Main{
        return static::$instance;
    }

    public function onEnable(){
        $this->config = new Config($this->getDataFolder() . "Config.yml", Config::YAML, [
            "helmet-level" => 20,
            "chest-plate-level" => 40,
            "pants-level" => 10,
            "boots-level" => 30
        ]);
        $this->db = $this->config->getAll();
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
        $this->getServer()->getCommandMap()->register("level", new PlayerLevelCommand($this));
    }

    public function save() {
        $this->config->setAll($this->db);
        $this->config->save();
    }

    public function onDisable(){
        $this->config->setAll($this->db);
        $this->config->save();
    }
}