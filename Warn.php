<?php
/**
 * @name Warn
 * @author alvin0319
 * @main alvin0319\Warn
 * @version 1.0.0
 * @api 4.0.0
 */
namespace alvin0319;

use pocketmine\math\Vector3;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\Player;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\level\Position;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;

class Warn extends PluginBase implements Listener{

    public $prefix = '§l§d* §f경고 §7: §f';

    public $db;//DB

    public $config;//CONFIG

    public $setting;//SETTING

    public $set;//SET

    public function onEnable() : void{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        @mkdir($this->getDataFolder());
        $this->config = new Config($this->getDataFolder() . 'WarnDB.yml', Config::YAML);
        $this->db = $this->config->getAll();
        $this->setting = new Config($this->getDataFolder() . 'Setting.yml', Config::YAML, [
            'warn-count' => 5
        ]);
        $this->set = $this->setting->getAll();
        $this->getServer()->getCommandMap()->register('warn', new WarnSetCommand($this));
        $this->getServer()->getCommandMap()->register('warn', new AddWarnCommand($this));
        $this->getServer()->getCommandMap()->register('warn', new DeleteWarnCommand($this));
        $this->getServer()->getCommandMap()->register('warn', new SeeWarnCommand($this));
    }
    public function onJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        $name = strtolower($player->getName());
        if (! isset($this->db[$name] ['경고'])) {
            $this->db[$name] ['경고'] = 0;
            $this->db[$name] ['감옥번호'] = 0;
            $this->save();
        }
    }
    public function onMove(PlayerMoveEvent $event) {
        $player = $event->getPlayer();
        $name = strtolower($player->getName());
        $id = $player->getLevel()->getBlock(new Position((float) $player->x, (float) $player->y - 1, (float) $player->z))->getId();
        $data = $player->getLevel()->getBlock(new Position((float) $player->x, (float) $player->y - 1, (float) $player->z))->getDamage();
        if ($this->db[$name] ['경고'] >= $this->set['warn-count']) {
            if (($id === 0 and $data === 0) || $id === $this->set['blockid'] and $data === $this->set['blockdata']) {
                return;
            }
            $x = $this->set['감옥'] [$this->db[$name] ['감옥번호']] ['x'];
            $y = $this->set['감옥'] [$this->db[$name] ['감옥번호']] ['y'];
            $z = $this->set['감옥'] [$this->db[$name] ['감옥번호']] ['z'];
            $lv = $this->set['감옥'] [$this->db[$name] ['감옥번호']] ['lv'];
            $player->teleport(new Position($x, $y, $z, $this->getServer()->getLevelManager()->getLevelByName($lv)), $player->getYaw(), $player->getPitch());
            $player->sendTip('§c탈출할수 없습니다');
        }
    }
    public function onChat(PlayerChatEvent $event) {
        $player = $event->getPlayer();
        $name = strtolower($player->getName());
        if ($this->db[$name] ['경고'] >= $this->set['warn-count']) {
            $event->setCancelled(true);
            $player->sendMessage($this->prefix . '감옥에 있을때는 채팅이 불가합니다');
        }
    }
    public function onPlayerCommandPreprocessEvent(PlayerCommandPreprocessEvent $event) {
        $player = $event->getPlayer();
        $name = strtolower($player->getName());
        $message = $event->getMessage();
        if($this->db[$name] ['경고'] >= $this->set['warn-count']){
            if(substr($message, 0, 1) === '/'){
                $event->setCancelled();
                $player->sendMessage($this->prefix . '감옥에 있을때는 명령어 사용이 불가능합니다');
            }
        }
    }
    public function save() {
        $this->config->setAll($this->db);
        $this->setting->setAll($this->set);
        $this->config->save();
        $this->setting->save();
    }
}
class AddWarnCommand extends Command{

    protected $plugin;

    public function __construct(Warn $plugin){
        $this->plugin = $plugin;
        parent::__construct('경고추가', '경고 추가 명령어', '/경고추가 <닉네임> <횟수> <감옥번호>', ['awarn']);
    }
    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender->isOp()){
            return true;
        }
        if(!isset($args[0])){
            $sender->sendMessage($this->plugin->prefix . $this->getUsage());
            return true;
        }
        $player = array_shift($args);
        $amount = array_shift($args);
        $num = array_shift($args);
        if(!isset($this->plugin->db[strtolower(strtolower($player))])){
            $sender->sendMessage($this->plugin->prefix . '해당 유저는 서버 접속 기록이 없습니다');
            return true;
        }
        if(!is_numeric($amount)){
            $sender->sendMessage($this->plugin->prefix . '횟수는 숫자여야 합니다');
            return true;
        }
        if(!is_numeric($num)){
            $sender->sendMessage($this->plugin->prefix . '감옥번호는 숫자여야 합니다');
            return true;
        }
        $this->plugin->db[strtolower($player)] ['경고'] += $amount;
        $this->plugin->db[strtolower($player)] ['감옥번호'] = $num;
        $this->plugin->save();
        $sender->sendMessage($this->plugin->prefix . $player . ' 플레이어에게 ' . $amount . ' 만큼의 경고를 부여했습니다');
        $this->plugin->getServer()->broadcastMessage($this->plugin->prefix . $sender->getName() . ' 님이 ' . $player . ' 님께 ' . $amount . ' 만큼의 경고를 부여했습니다');
        return true;
    }
}
class DeleteWarnCommand extends Command{

    protected $plugin;

    public function __construct(Warn $plugin){
        $this->plugin = $plugin;
        parent::__construct('경고감면', '경고 감면 명령어', '/경고감면 <닉네임> <횟수>', ['dwarn']);
    }
    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender->isOp()){
            return true;
        }
        if(!isset($args[0])){
            $sender->sendMessage($this->plugin->prefix . $this->getUsage());
            return true;
        }
        $player = array_shift($args);
        $amount = array_shift($args);
        if(!isset($this->plugin->db[strtolower(strtolower($player))])){
            $sender->sendMessage($this->plugin->prefix . '해당 유저는 서버 접속 기록이 없습니다');
            return true;
        }
        if(!is_numeric($amount)){
            $sender->sendMessage($this->plugin->prefix . '횟수는 숫자여야 합니다');
            return true;
        }
        $this->plugin->db[strtolower($player)] ['경고'] -= $amount;
        $this->plugin->save();
        $sender->sendMessage($this->plugin->prefix . $player . ' 플레이어에게 ' . $amount . ' 만큼의 경고를 김면했습니다');
        $this->plugin->getServer()->broadcastMessage($this->plugin->prefix . $sender->getName() . ' 님이 ' . $player . ' 님께 ' . $amount . ' 만큼의 경고를 감면했습니다');
        return true;
    }
}
class SeeWarnCommand extends Command{

    protected $plugin;

    public function __construct(Warn $plugin){
        $this->plugin = $plugin;
        parent::__construct('경고보기', '나 또는 다른 사람의 경고를 봅니다', '/경고보기 <닉네임>', ['swarn']);
    }
    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!isset($args[0])){
            $sender->sendMessage($this->plugin->prefix . '내 경고수: ' . $this->plugin->db[strtolower($sender->getName())] ['경고']);
            return true;
        }else{
            $player = array_shift($args);
            if(!isset($this->plugin->db[strtolower($player)])){
                $sender->sendMessage($this->plugin->prefix . '해당 유저는 서버 접속 기록이 없습니다');
                return true;
            }
            $sender->sendMessage($this->plugin->prefix . $player . ' 님의 경고수: ' . $this->plugin->db[strtolower($player)] ['경고']);
            //if($this->plugin->db[strtolower($player)] >= $this->plugin->set['warn-count']){
            //    $sender->sendMessage($this->plugin->prefix . '이 유저는 ' . $this->plugin->db[strtolower($player)] ['감옥번호'] . ' 번 감옥에 들어가 있습니다');
            //}
        }
        return true;
    }
}
class WarnSetCommand extends Command{

    protected $plugin;

    public function __construct(Warn $plugin){
        $this->plugin = $plugin;
        parent::__construct('경고설정', '경고설정 명령어', '/경고설정 [감옥설정 | 블럭설정]', ['setwarn']);
    }
    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender instanceof Player){
            $sender->sendMessage($this->plugin->prefix . '콘솔에서는 사용하실수 없습니다');
            return true;
        }
        if(!$sender->isOp()){
            return true;
        }
        if(!isset($args[0])){
            $sender->sendMessage($this->plugin->prefix . $this->getUsage());
            return true;
        }
        if($args[0] === '감옥설정'){
            if(!isset($args[1]) or !is_numeric($args[1])){
                $sender->sendMessage($this->plugin->prefix . '/경고설정 감옥설정 [번호]');
                return true;
            }
            $this->plugin->set['감옥'] [$args[1]] ['x'] = $sender->x;
            $this->plugin->set['감옥'] [$args[1]] ['y'] = $sender->y;
            $this->plugin->set['감옥'] [$args[1]] ['z'] = $sender->z;
            $this->plugin->set['감옥'] [$args[1]] ['lv'] = $sender->getLevel()->getFolderName();
            $this->plugin->save();
            $sender->sendMessage($this->plugin->prefix . '설정이 완료되었습니다');
        }
        if($args[0] === '블럭설정'){
            $x = (int) round($sender->x - 0.5);
            $y = (int) round($sender->y - 1);
            $z = (int) round($sender->z - 0.5);
            $id = $sender->getLevel()->getBlock(new Vector3($x, $y, $z))->getId();
            $dm = $sender->getLevel()->getBlock(new Vector3($x, $y, $z))->getDamage();
            $this->plugin->set['blockid'] = $id;
            $this->plugin->set['blockdata'] = $dm;
            $this->plugin->save();
            $sender->sendMessage($this->plugin->prefix . '설정이 완료되었습니다');
        }
        if($args[0] === '경고갯수'){
            if(!isset($args[1]) or !is_numeric($args[1])){
                $sender->sendMessage($this->plugin->prefix . '갯수는 숫자여야 합니다');
                return true;
            }
            $this->plugin->set['warn-count'] = $args[1];
            $sender->sendMessage($this->plugin->prefix . '경고의 갯수가 설정되었습니다');
        }
        return true;
    }
}