<?php
/**
 * @name DontCraftItem
 * @author alvin0319
 * @main alvin0319\DontCraftItem
 * @version 1.0.0
 * @api 4.0.0
 */
declare(strict_types=1);
namespace alvin0319;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class DontCraftItem extends PluginBase implements Listener {

    /** @var Config */
    protected $config;

    public $db;

    public function onEnable() {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        @mkdir($this->getDataFolder());
        $this->config = new Config($this->getDataFolder() . 'Config.yml', Config::YAML, [
            'ban-item' => []
        ]);
        $this->db = $this->config->getAll();
        $this->getServer()->getCommandMap()->register('craftban', new AddCraftBanCommand($this));
    }

    public function onCraft(CraftItemEvent $event) {
        $result = $event->getOutputs();
        $id = array_pop($result)->getId();
        if(isset($this->db['ban-item'] [$id])){
            if($event->getPlayer()->isOp()){
                return;
            }
            $event->setCancelled(true);
            $event->getPlayer()->sendTip(TextFormat::RED . '이 아이템은 조합이 금지되어있습니다');
        }
    }

    public function onDisable()
    {
        $this->config->setAll($this->db);
        $this->config->save();
    }
}
class AddCraftBanCommand extends Command
{

    protected $plugin;

    public function __construct(DontCraftItem $plugin)
    {
        $this->plugin = $plugin;
        parent::__construct('조합밴', '조합밴을 관리합니다', '/조합밴 [추가 | 제거 | 목록]', ['cban']);
    }
    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$sender instanceof Player) return true;
        if(!$sender->isOp()){
            $sender->sendMessage(TextFormat::RED . '이 명령어를 수행할 권한이 없습니다');
            return true;
        }
        if(!isset($args[0])){
            $args[0] = 'x';
        }
        $inv = $sender->getInventory();
        switch($args[0]){
            case '추가':
                if($inv->getItemInHand()->getId() === 0){
                    $sender->sendMessage(TextFormat::YELLOW . '아이템의 아이디는 공기가 아니어야 합니다');
                    return true;
                }
                $this->plugin->db['ban-item'] [$inv->getItemInHand()->getId()] = true;
                $sender->sendMessage(TextFormat::YELLOW . '추가에 성공하였습니다');
                break;
            case '제거':
                if($inv->getItemInHand()->getId() === 0){
                    $sender->sendMessage(TextFormat::YELLOW . '아이템의 아이디는 공기가 아니어야 합니다');
                    return true;
                }
                if(!isset($this->plugin->db['ban-item'] [$inv->getItemInHand()->getId()])){
                    $sender->sendMessage(TextFormat::YELLOW . '해당 아이템은 등록되어있지 않습니다');
                    return true;
                }
                unset($this->plugin->db['ban-item'] [$inv->getItemInHand()->getId()]);
                $sender->sendMessage(TextFormat::YELLOW . '제거되었습니다');
                break;
            case '목록':
                $arr = [];
                foreach($this->plugin->db['ban-item'] as $item => $value){
                    array_push($arr, array('text' => '- ' . $item));
                }
                $packet = new ModalFormRequestPacket();
                $packet->formId = 11119;
                $packet->formData = json_encode([
                    'type' => 'form',
                    'title' => '조합밴 목록',
                    'content' => '목록',
                    'buttons' => $arr
                ]);
                $sender->sendDataPacket($packet);
                break;
            default:
                $sender->sendMessage(TextFormat::YELLOW . $this->getUsage());
        }
    }
}