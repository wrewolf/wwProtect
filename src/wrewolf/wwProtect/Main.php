<?php

   namespace wrewolf\wwProtect;

   use pocketmine\command\Command;
   use pocketmine\command\CommandSender;
   use pocketmine\command\PluginCommand;
   use pocketmine\event\block\BlockBreakEvent;
   use pocketmine\event\block\BlockPlaceEvent;
   use pocketmine\event\Listener;
   use pocketmine\Player;
   use pocketmine\plugin\PluginBase;
   use pocketmine\utils\TextFormat;

   class Main extends PluginBase implements Listener
   {

      private $config, $groups, $name, $password, $user, $db, $socket;

      public function onEnable()
      {

         $this->getLogger()->info("Loading data...");
         $this->saveDefaultConfig();
         $this->saveResource("dbparam.yml", false); //Do not replace default
         $Dbparams       = $this->getConfig();
         $this->name     = $Dbparams->get("name");
         $this->user     = $Dbparams->get("user");
         $this->password = $Dbparams->get("password");
         $this->socket   = $Dbparams->get("socket");
         $this->getServer()->getPluginManager()->registerEvents($this, $this);
         $this->db = new \mysqli(null, $this->user, $this->password, $this->name, 3306, $this->socket);

         $this->reload();
         //$this->config= new Config($this->getDataFolder() . "areas.yml");
         //$this->groups= new Config($this->getDataFolder() . "groups.yml");

         //Command protect
         $command_protect = new PluginCommand("protect", $this);
         $command_protect->setAliases(array("pr", "protect"));
         $command_protect->setAliases(array("protect1", "protect pos1"));
         $command_protect->setAliases(array("p1", "protect pos1"));
         $command_protect->setAliases(array("protect2", "protect pos2"));
         $command_protect->setAliases(array("p2", "protect pos2"));
         $command_protect->register($this->getServer()->getCommandMap());
         //Command group
         $command_group = new PluginCommand("group", $this);
         $command_group->setAliases(array("lsgroup", "group ls"));
         $command_group->register($this->getServer()->getCommandMap());

         $this->getLogger()->info("wwProtect Loaded");
         $this->getLogger()->info("wwGroup Loaded");
      }

      public function reload()
      {
         $this->config = array();

         $rez          = $this->db->query("SELECT * FROM Protect");
         $this->config = array();
         while($row = $rez->fetch_assoc()) {
            $this->config[$row['level']][$row['name']] = array(
               'protect'  => $row['enabled'],
               'members'  => $row['members'],
               'interact' => $row['isDoor'],
               'pvp'      => $row['isPvp'],
               'min'      => array($row['x1'], $row['y1'], $row['z1']),
               'max'      => array($row['x2'], $row['y2'], $row['z2'])
            );
            //$this->getLogger()->info(implode(', ', $row));
         }
         $rez          = $this->db->query("SELECT * FROM groups");
         $this->groups = array();
         while($row = $rez->fetch_assoc()) {
            $this->groups[$row['name']] = json_decode($row['members'], true);
            //$this->getLogger()->info(implode(', ', $row));
         }
      }

      public function onDisable()
      {
         //$this->config->save();
         //$this->groups->save();
      }

      private $tmp_store = array();

      /**
       * @param CommandSender $sender
       * @param Command       $command
       * @param string        $label
       * @param array         $args
       *
       * @return bool|void
       */
      public function onCommand(CommandSender $sender, Command $command, $label, array $args)
      {
         $user = strtolower($sender->getName());
         $this->getLogger()->info(implode(", ", $args));
         if($sender->getName() !== "CONSOLE") {
            $player = $sender->getServer()->getPlayer($sender->getName());
            $level  = $sender->getServer()->getPlayer($sender->getName())->getLevel()->getName();
            $mode   = '';
            switch($command->getName()) {
               case "protect":
                  if(count($args) == 1) {
                     $args = strtolower($args[0]);
                     if($args == 'pos1') {
                        if(! isset($this->tmp_store[$user])) $this->tmp_store[$user] = array();
                        $this->tmp_store[$user][1] = array(0 => $player->getFloorX(), 1 => $player->getFloorY(), 2 => $player->getFloorZ(), 'level' => $player->getLevel()->getName());
                     } else if($args == 'pos2') {
                        if(! isset($this->tmp_store[$user])) $this->tmp_store[$user] = array();
                        $this->tmp_store[$user][2] = array(0 => $player->getFloorX(), 1 => $player->getFloorY(), 2 => $player->getFloorZ(), 'level' => $player->getLevel()->getName());
                     } else if($args == 'g') {
                        $sender->sendMessage(TextFormat::RED . "/protect g: <Group Name>\n\tplease set group name");
                     } else {
                        $sender->sendMessage($command->getUsage());
                     }
                  } else if(count($args) == 2) {
                     $mode  = strtolower(array_shift($args));
                     $group = strtolower(array_shift($args));
                  }
                  if(count($args) == 0 || $mode == 'g') {
                     $this->getLogger()->info("Pos1: " . implode(", ", $this->tmp_store[$user][1]));
                     $this->getLogger()->info("Pos2: " . implode(", ", $this->tmp_store[$user][2]));
                     $pos1 = $this->tmp_store[$user][1];
                     $pos2 = $this->tmp_store[$user][2];
                     if($pos1['level'] != $pos2['level']) {
                        $sender->sendMessage(TextFormat::RED . "[wwProtect] Both point need locate in one map");
                        return true;
                     }
                     $minX = min($pos1[0], $pos2[0]);
                     $maxX = max($pos1[0], $pos2[0]);
                     $minY = min($pos1[1], $pos2[1]);
                     $maxY = max($pos1[1], $pos2[1]);
                     $minZ = min($pos1[2], $pos2[2]);
                     $maxZ = max($pos1[2], $pos2[2]);
                     $max  = array($maxX, $maxY, $maxZ);
                     $min  = array($minX, $minY, $minZ);

                     if($mode == "") {
                        $this->config[$level][$user] = array("protect" => true, "min" => $min, "max" => $max);
                     } else {
                        $this->config[$level]["g:" . $group] = array("protect" => true, "min" => $min, "max" => $max);
                     }

                     if(! $sender->getServer()->isOp($sender->getName()))
                        if(($maxX - $minX) * ($maxY - $minY) * ($maxZ - $minZ) >= 12000) {
                           $sender->sendMessage(TextFormat::GOLD . "[wwProtect] Can't protect. Max area 12000 blocks (e.g. 15x28x28)");
                           break;
                        }
                     $members = json_encode(array($user));
                     if($mode == "") {
                        $query                       = "INSERT INTO Protect
                           (`name`, members, `level`, x1, y1, z1, x2, y2, z2, enabled)
                           VALUES
                           ('$user','$members','$level','$minX','$minY','$minZ','$maxX','$maxY','$maxZ','1')
                           ON DUPLICATE KEY UPDATE
                           `level`='$level',
                           members='$members',
                           x1='$minX',
                           y1='$minY',
                           z1='$minZ',
                           x2='$maxX',
                           y2='$maxY',
                           z2='$maxZ',
                           enabled='1'
                           ";
                        $this->config[$level][$user] = array("protect" => true, "min" => $min, "max" => $max);
                     } else {
                        if($this->inGroup($user, $group) || $sender->isOp()) {
                           $query                            = "INSERT INTO Protect
                           (`name`, members, `level`, x1, y1, z1, x2, y2, z2, enabled)
                           VALUES
                           ('g:$group','$members','$level','$minX','$minY','$minZ','$maxX','$maxY','$maxZ','1')
                           ON DUPLICATE KEY UPDATE
                           `level`='$level',
                           members='$members',
                           x1='$minX',
                           y1='$minY',
                           z1='$minZ',
                           x2='$maxX',
                           y2='$maxY',
                           z2='$maxZ',
                           enabled='1'
                           ";
                           $this->config[$level]["g:$group"] = array("protect" => true, "min" => $min, "max" => $max);
                        } else {
                           $query = "";
                           $sender->sendMessage("[wwGroups] Access denied to group $group");
                        }
                     }
                     $this->db->query($query);
                     $sender->sendMessage("[wwProtect] Protected this area ($minX, $minY, $minZ)-($maxX, $maxY, $maxZ) : $level");
                  } else if($mode = 'share') {
                     if($this->inGroup($user, $group)) {
                        $this->config[$level]["g:$group"] = $this->config[$level][$user];
                        $this->db->query("UPDATE Protect SET `name`='g:$group' WHERE `name`='user'");
                     }
                  } else {
                     $sender->sendMessage($command->getUsage());
                  }
                  return true;
               case "sprotect":
                  $sender->sendMessage(TextFormat::RED . "For Console use only");
                  return true;
               case "group":
                  if(count($args) == 0 || (count($args) == 1 && $args[0] = 'ls')) {
                     $sender->sendMessage("[wwGroups] " . implode(", ", array_keys($this->groups)));
                  } else if(count($args) == 2) {
                     $cmd   = strtolower(array_shift($args));
                     $group = strtolower(array_shift($args));
                     switch($cmd) {
                        case "rm":
                           if($this->inGroup($user, $group) || $sender->isOp()) {
                              $this->groups[$group] = array($user);
                              foreach($this->groups[$group] as $player) {
                                 if($sender->getServer()->getPlayer($player)->isOnline())
                                    $sender->getServer()->getPlayer($player)->sendMessage("[wwGroups] Group $group removed");
                              }
                              unset($this->groups[$group]);
                              $this->db->query("DELETE FROM groups WHERE name='$group'");
                           } else {
                              $sender->sendMessage("[wwGroups] Access denied");
                           }
                           return true;
                        case "add":
                           $this->groups[$group] = array($user);
                           $this->saveGroup($group);
                           return true;
                        case "ls":
                           if($sender->isOp()) {
                              $groups = $this->getUserGroups($group);
                              $sender->sendMessage("[wwGroups] " . implode(", ", $groups));
                           } else {
                              $sender->sendMessage("[wwGroups] Access denied");
                           }
                           return true;
                     }
                  } else if(count($args) == 3) {
                     $cmd   = strtolower(array_shift($args));
                     $user  = strtolower(array_shift($args));
                     $group = strtolower(array_shift($args));
                     switch($cmd) {
                        case "rm":
                           if($this->inGroup($user, $group) || $sender->isOp()) {
                              if($sender->getServer()->getPlayer($player)->isOnline())
                                 $sender->getServer()->getPlayer($player)->sendMessage("[wwGroups] You removed from group $group");
                              unset($this->groups[$group][$user]);
                              $this->saveGroup($group);
                           } else {
                              $sender->sendMessage("[wwGroups] Access denied");
                           }
                           return true;
                        case "add":
                           if($this->inGroup($user, $group) || $sender->isOp()) {
                              if($sender->getServer()->getPlayer($player)->isOnline())
                                 $sender->getServer()->getPlayer($player)->sendMessage("[wwGroups] You added to group $group");
                              $this->groups[$group][] = $user;
                              $this->saveGroup($group);
                           } else {
                              $sender->sendMessage("[wwGroups] Access denied");
                           }
                           return true;
                     }
                  } else {
                     $sender->sendMessage("[wwGroups] " . $command->getUsage());
                  }
                  return true;
            }
         } else {
            switch($command->getName()) {
               case "group":
                  $sender->sendMessage("[wwGroups] " . implode(", ", array_keys($this->groups)));
                  return true;
            }
         }
      }

      private function saveGroup($name)
      {
         $members = json_encode($this->config[$name]);
         $this->db->query("
           INSERT INTO groups
                  (`name`, members)
           VALUES
                   ('$name','$members')
           ON DUPLICATE KEY UPDATE
                   members='$members';
           ");
      }

      private function inGroup($user, $group)
      {
         return in_array($user, $this->groups[$group]);
      }


      private function getUserGroups($user)
      {
         $groups = [];
         foreach($this->groups as $name => $users) {
            if(in_array($user, $users))
               $groups[] = $name;
         }
         return $groups;
      }

      /**
       * @param BlockPlaceEvent $event
       *
       * @priority        HIGHEST
       * @ignoreCancelled false
       */
      public function onBlockPlaceEvent(BlockPlaceEvent $event)
      {
         $player = $event->getPlayer();
         $block  = $event->getBlock();
         //$message = "U place " . $block->getName() . " on (" . $block->getFloorX() . ', ' . $block->getFloorY() . ', ' . $block->getFloorZ() . ') ';
         //$this->getLogger()->info($message);
         //$player->sendMessage($message);
         if(! $this->checkProtect($player, $block)) {
            $event->setCancelled(true);
         }
      }

      /**
       * @param BlockBreakEvent $event
       *
       * @priority        HIGHEST
       * @ignoreCancelled false
       */
      public function onBlockBreakEvent(BlockBreakEvent $event)
      {
         $player = $event->getPlayer();
         $block  = $event->getBlock();

         //$message = "U break" . $block->getName() . " on (" . $block->getFloorX() . ', ' . $block->getFloorY() . ', ' . $block->getFloorZ() . ') ';
         //$player->sendMessage($message);
         //$this->getLogger()->info($message);
         if(! $this->checkProtect($player, $block)) {
            $event->setCancelled(true);
         }
      }

      private function checkProtect(Player $player, $block)
      {

         $level = $player->getLevel()->getName();
         if(isset($this->config) && isset($this->config[$level]))
            foreach($this->config[$level] as $name => $config) {
               if(! $config['protect'] or $name == $player->getName())
                  continue;
               if($this->checkCoordinates($player, $block, $name))
                  return false;
            }
         return true;
      }

      private function checkCoordinates(Player $player, $block, $name)
      {
         //Спецгруппа Sa
         if(isset($this->groups['Sa']))
            if(in_array(strtolower($player->getName()), $this->groups['Sa'])) {
               return false;
            }
         $level = strtolower($player->getLevel()->getName());

         if($this->config[$level][$name]['min'][0] <= $block->getFloorX() and $block->getFloorX() <= $this->config[$level][$name]['max'][0]) {
            if($this->config[$level][$name]['min'][1] <= $block->getFloorY() and $block->getFloorY() <= $this->config[$level][$name]['max'][1]) {
               if($this->config[$level][$name]['min'][2] <= $block->getFloorZ() and $block->getFloorZ() <= $this->config[$level][$name]['max'][2]) {
                  if(substr($name, 0, 2) == "g:") {
                     //Группы храним в отдельной таблице но вместе с основным кодом
                     if(isset($this->groups[substr($name, 2)]) && is_array($this->groups[substr($name, 2)]))
                        if(! in_array(strtolower($player->getName()), $this->groups[substr($name, 2)])) {
                           $name = substr($name, 2);
                           $player->sendMessage("[wwProtect] This is Group $name's private area.");
                           return true;
                        }
                  } else {
                     $player->sendMessage("[wwProtect] This is $name's private area.");
                     return true;
                  }
               }
            }
         }
         return false;
      }
   }