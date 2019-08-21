<?php

namespace chat;

use pocketmine\Server;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerLoginEvent;

class chat extends PluginBase implements Listener{
	public $groups = [];
	public $players = [];
	const GLOBAL_CHAT = "___GLOBAL_CHAT___ ";

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->groups = $this->read("groups.json");
		$this->players = $this->read("players.json");
	}
	
	public function PlayerLogin(PlayerLoginEvent $event){
		if(!isset($this->players[$event->getPlayer()->getName()])){
			$this->logingroup($event->getPlayer()->getName(),self::GLOBAL_CHAT);
		}
	}
	
	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		switch(strtolower($label)){
			case "chat":
				if(!isset($args[0])){
					$this->help($sender,$label);
					return true;
				}
				$array = $args;
				unset($array[0]);
				$groupname = implode(" ", $array);
				switch(strtolower($args[0])){
					case "add":
					case "a":
						if(!$sender->isOP()){
							$sender->sendMessage("§eあなたはop権限が無いため、指定された引数の操作をすることは出来ません。");
						}
						if(!isset($args[1])){
							$sender->sendMessage("/".$label." ".$args[0]." [グループ名] : §aチャットグループの作成を行います。");
							return true;
						}
						if(isset($this->groups[$groupname])){
							$sender->sendMessage("指定されたグループは作成済みのため、新たに作成することは出来ません。");
							return true;
						}
						if($this->isInvalid($groupname)){
							$sender->sendMessage("指定されたグループ名は無効のため、作成することは出来ません。");
							return true;
						}
						$this->groups[$groupname] = [];
						$sender->sendMessage("チャットグループを作成致しました。");
						$this->save();
					break;
					case "delete":
					case "d":
						if(!$sender->isOP()){
							$sender->sendMessage("§eあなたはop権限が無いため、指定された引数の操作をすることは出来ません。");
						}
						if(!isset($args[1])){
							$sender->sendMessage("/".$label." ".$args[0]." [グループ名] : §aチャットグループの削除を行います。");
							return true;
						}
						if(!isset($this->groups[$groupname])){
							$sender->sendMessage("指定されたグループは存在しないため、削除することは出来ません。");
							return true;
						}
						foreach($this->groups[$groupname] as $name => $bool){
							unset($this->players[$name][$groupname]);
						}
						unset($this->groups[$groupname]);
						$sender->sendMessage("チャットグループを削除致しました。");
						$this->save();
					break;
					case "login":
					case "in":
					case "l":
						if(!isset($args[1])){
							$sender->sendMessage("/".$label." ".$args[0]." [グループ名] : §aチャットグループにログインします。");
							return true;
						}
						if(!isset($this->groups[$groupname])){
							$sender->sendMessage("指定されたグループは存在しないため、ログインすることは出来ません。");
							return true;
						}
						if(!$this->logingroup($sender->getName(),$groupname)){
							$player->sendMessage("指定されたグループに既に参加しているため、参加することは出来ません。");
							return true;
						}
						$sender->sendMessage("チャットグループ「".$groupname."」にログインしました！");
						$this->save();
					break;
					case "logout":
					case "out":
					case "o":
						if(!isset($args[1])){
							$sender->sendMessage("/".$label." ".$args[0]." [グループ名] : §aチャットグループからログアウトします。");
							return true;
						}
						if(!isset($this->groups[$groupname])){
							$sender->sendMessage("指定されたグループは存在しないため、ログアウトすることは出来ません。");
							return true;
						}
						if(!$this->logingroup($sender->getName(),$groupname)){
							$player->sendMessage("指定されたグループに参加していないため、ログアウトすることは出来ません。");
							return true;
						}
						$sender->sendMessage("チャットグループ「".$groupname."」にログインしました！");
						$this->save();
					break;
					case "global":
					case "g":
						if(isset($args[1])){
							switch(strtolower($args[1])){
								case "on";
									if(!$this->logingroup($sender->getName(),self::GLOBAL_CHAT)){
										$sender->sendMessage("グローバルチャットは既にオンのため、オンにすることは出来ません。");
										return true;
									}
									$sender->sendMessage("グローバルチャットをオンにしました。");
								break;
								case "off":
									if(!$this->logoutgroup($sender->getName(),self::GLOBAL_CHAT)){
										$sender->sendMessage("グローバルチャットは既にオフのため、オフにすることは出来ません。");
										return true;
									}
									$sender->sendMessage("グローバルチャットをオフにしました。");
								break;
								default:
									$sender->sendMessage("/".$label." ".$args[0]." [on / off]");
								break;
							}
							return true;
						}
						if($this->hasgroup($sender->getName(),self::GLOBAL_CHAT)){
							$this->logoutgroup($sender->getName(),self::GLOBAL_CHAT);
							$sender->sendMessage("グローバルチャットをオフにしました。");
						}else{
							$this->logingroup($sender->getName(),self::GLOBAL_CHAT);
							$sender->sendMessage("グローバルチャットをオンにしました。");
						}
						$this->save();
					break;
					case "list":
					case "l":
						$sender->sendMessage("§a=====グループチャット一覧=====");
						foreach($this->groups as $groupmame => $array){
							if(!$this->isInvalid($groupname)){
								$sender->sendMessage($groupmame);
							}
						}
					break;
					default:
						$this->help($sender,$label);
					break;
				}
			break;
		}
		return true;
	}
	
	public function logingroup(string $player,string $groupname): bool{
		if($this->hasgroup($player,$groupname)){
			return false;
		}
		$this->players[$player][$groupname] = true;
		$this->groups[$groupname][$player] = true;
		return true;
	}

	public function hasgroup(string $player,string $groupname): bool{
		return isset($this->players[$player][$groupname]);
	}

	public function logoutgroup(string $player,string $groupname): bool{
		if(!$this->hasgroup($player,$groupname)){
			return false;
		}
		unset($this->players[$player][$groupname]);
		unset($this->groups[$groupname][$player]);
		return true;
	}
	
	public function PlayerChat(PlayerChatEvent $event){
		$list = [];
		$recipients = [];
		$sender = $event->getPlayer();
		$players = $this->getPlayers();
		if(isset($this->players[$sender->getName()])&&count($this->players[$sender->getName()]) !== 0){
			$sendergroup = $this->players[$sender->getName()];
			foreach($sendergroup as $group => $bool){
				$list += $this->groups[$group];
			}
			foreach($list as $name => $bool){
				$recipients[] = $players[$name];
			}
		}else{
			$recipients = [$sender];
		}
		$event->setRecipients($recipients);
	}

	public function getPlayers(){
		$players = $this->getServer()->getOnlinePlayers();
		$returnPlayers = [];
		foreach($players as $id => $player){
			$returnPlayers[$player->getName()] = $player;
		}
		return $returnPlayers;
	}

	public function isInvalid(string $groupname){
		return $groupname == self::GLOBAL_CHAT;
	}

	//public function sendchat
	
	public function help(player $player,string $label){
		if($player->isOP()){
			$player->sendMessage("/".$label." [add / a] [グループ名] : §aグループチャット作成します。");
			$player->sendMessage("/".$label." [delete / d] [グループ名] : §aグループチャットを削除します。");
		}
		$player->sendMessage("/".$label." [list / l] : §aグループチャットの一覧を閲覧します。");
		$player->sendMessage("/".$label." [global / g] : §aグローバルチャットをオン / オフの切り替えを行います。");
		$player->sendMessage("/".$label." [login / in / i] [グループ名]: §aチャットグループにログインします。");
		$player->sendMessage("/".$label." [logout /out / o] [グループ名]: §aチャットグループからログアウトします。");
	}
	
	public function save(){
		$this->write("groups.json",$this->groups);
		$this->write("players.json",$this->players);
	}
	public function read($filename){
		if(file_exists($this->getDataFolder().$filename)){
			$data = file_get_contents($this->getDataFolder().$filename);
			return json_decode($data,true);
		}else{
			return [];
		}
	}

	public function write($filename,$data){
		$json = json_encode($data,JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING);
		file_put_contents($this->getDataFolder().$filename,$json);
	}
}
