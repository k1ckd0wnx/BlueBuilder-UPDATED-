<?php

namespace BlueBuilder;

use pocketmine\plugin\PluginBase;

use pocketmine\event\Listener;

use pocketmine\Player;

use pocketmine\utils\TextFormat;

use pocketmine\command\Command;

use pocketmine\command\CommandSender;

use pocketmine\event\block\BlockPlaceEvent;

use pocketmine\event\block\BlockBreakEvent;

use pocketmine\block\Block;

use pocketmine\math\Vector3;

use pocketmine\Server;

class BlueBuilder extends PluginBase implements Listener {

    public function onEnable(){

        $this->getServer()->getPluginManager()->registerEvents($this,$this);

        $this->getLogger()->info("Plugin Enabled");

        if(!file_exists($this->getDataFolder() . "blueprints.json")) {

            file_put_contents($this->getDataFolder() . "blueprints.json","{}");

        }

        $data = json_decode(file_get_contents($this->getDataFolder()."blueprints.json"),true);

        while($item = array_shift($data)) {

            foreach($item as $name => $blockList) {

                $this->bluePrints[$name] = $blockList;

            }

        }

        

    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {

        if($cmd->getName() == "test"){

            $sender->sendMessage("This Is A Test!");

            if(isset($args[0])) {

                $sender->sendMessage("Args: ".$args[0]);

            }

        }

        if($cmd->getName() == "bluebuilder"){

            if(!isset($args[0])) {

                $sender->sendMessage("BlueBuilder was created by moebius2033 [EDITED BY MRHUSKYY\TURTLESH0CK] for use with PocketMine API version 3.0.0, 3.3.7, 4.0.0.");

            } else if ($args[0] == strtolower("list")) {

                $sender->sendMessage("Current list of blueprints:");

                if(empty($this->bluePrints)) {

                  $sender->sendMessage("There are none!");

                } else {

                    $this->list = "";

                    foreach ($this->bluePrints as $name => $blockList) {

                        $this->list .= $name." ";

                    }

                    $sender->sendMessage($this->list);

                }

            } else {

                $sender->sendMessage("Unknown command ".$args[0]);

            }

        }

        if($cmd->getName() == "bluecopy"){

            if(!($sender instanceof Player)) {

                $sender->sendMessage("Cannot perform action on the console.");

            } else {

                $n = strtolower($sender->getName());

                $l = $sender->getLevel();

                if(!isset($args[0])) {

                    $sender->sendMessage("Please specify a subcommand (pos1, pos2, create <name>).");

                } else if ($args[0] == strtolower("pos1")) {

                    if(isset($this->sel1[$n]) || isset($this->sel2[$n])) {

                        $sender->sendMessage("You're already selecting a position!");

                    } else {

                        $this->sel1[$n] = true;

                        $sender->sendMessage("Please place or break the first position.");

                    }

                } else if ($args[0] == strtolower("pos2")) {

                    if(isset($this->sel1[$n]) || isset($this->sel2[$n])) {

                        $sender->sendMessage("You're already selecting a position!");

                    } else {

                        $this->sel2[$n] = true;

                        $sender->sendMessage("Please place or break the second position.");

                    }

                } else if ($args[0] == strtolower("create")) {

                    if(isset($args[1])) {

                        if(isset($this->pos1[$n]) && isset($this->pos2[$n])) {

                            $bPos1 = $this->pos1[$n];

                            $bPos2 = $this->pos2[$n];

                            if(!isset($this->bluePrints[strtolower($args[1])])) {

                                $bxMin = min([$bPos1->getX(), $bPos2->getX()]);

                                $bxMax = max([$bPos1->getX(), $bPos2->getX()]);

                                $byMin = min([$bPos1->getY(), $bPos2->getY()]);

                                $byMax = max([$bPos1->getY(), $bPos2->getY()]);

                                $bzMin = min([$bPos1->getZ(), $bPos2->getZ()]);

                                $bzMax = max([$bPos1->getZ(), $bPos2->getZ()]);

                                $copiedBlocks = "";

                                $cx = 0;

                                $cy = 0;

                                $cz = 0;

                                for($bx=$bxMin;$bx<$bxMax+1;$bx++) {                                    

                                    for($by=$byMin;$by<$byMax+1;$by++) {

                                        for($bz=$bzMin;$bz<$bzMax+1;$bz++) {

                                            $thisBlock = $l->getBlock(new Vector3($bx,$by,$bz));

                                            $firstPos = strpos($thisBlock, "(");

                                            $copiedBlocks .= ($cx.",".$cy.",".$cz.",".substr($thisBlock, ($firstPos+1), -1).";");

                                            $cz++;

                                        }

                                        $cz = 0;

                                        $cy++;

                                    }

                                    $cy = 0;

                                    $cx++;

				}                                $this->bluePrints[strtolower($args[1])] = $copiedBlocks;

                                $this->saveAreas();

                                unset($this->pos1[$n]);

                                unset($this->pos2[$n]);

                                $sender->sendMessage("Area created!");

                            } else {

                                $sender->sendMessage("An area with that name already exists.");

                            }

                        } else {

                            $sender->sendMessage("Please select both positions first.");

                        }

                    } else {

                        $sender->sendMessage("Please specify a name for this area.");

                    }

                } else {

                    $sender->sendMessage("Unknown command ".$args[0]);

                }

            }

        }

        if($cmd->getName() == "blueprint"){

            if(!($sender instanceof Player)) {

                $sender->sendMessage("Cannot perform action on the console.");

            } else {

                $n = strtolower($sender->getName());

                $l = $sender->getLevel();

                if(!isset($args[0])) {

                    $sender->sendMessage("Please specify a subcommand (start, build <name>).");

                } else if ($args[0] == strtolower("start")) {

                    if(isset($this->start[$n])) {

                        $sender->sendMessage("You're already selecting a position!");

                    } else {

                        $this->start[$n] = true;

                        $sender->sendMessage("Please place or break the start position.");

                    }

                } else if ($args[0] == strtolower("build")) {

                    if(isset($args[1])) {

                        if(!isset($this->startMark[$n])) {

                            $sender->sendMessage("Please select the start position first.");

                        } else {

                            if (isset($this->bluePrints[$args[1]])) {

                                $neededBlocks = $this->bluePrints[$args[1]];

                                $blockArray = explode(";", $neededBlocks);

                                for ($i=0;$i<count($blockArray)-1;$i++) {

                                    $singleBlock = explode(",", $blockArray[$i]);

                                    $thisBlock = explode(":", $singleBlock[3]);

                                    $currentBlock = Block::get($thisBlock[0],$thisBlock[1]);

                                    $l->setBlock(new Vector3(($this->startMark[$n]->getX()+$singleBlock[0]),($this->startMark[$n]->getY()+$singleBlock[1]),($this->startMark[$n]->getZ()+$singleBlock[2])),$currentBlock);

                                }

                                $sender->sendMessage("Blueprint ".$args[1]." built!!!");

                                unset($this->startMark[$n]);

                            } else {

                                $sender->sendMessage("Blueprint ".$args[1]." doesn't exist.");

                            }

                        }

                    } else {

                        $sender->sendMessage("Please specify a blueprint you wish to build.");

                    }

                } else {

                    $sender->sendMessage("Unknown command ".$args[0]);

                }

            }

            return true;

        }

    }

    public function onBlockBreak(BlockBreakEvent $event) {

        $b = $event->getBlock();

        $p = $event->getPlayer();

        $n = strtolower($p->getName());

        if(isset($this->sel1[$n])) {

            unset($this->sel1[$n]);

            $this->pos1[$n] = new Vector3($b->getX(),$b->getY(),$b->getZ());

            $p->sendMessage("Position 1 set to: (" . $this->pos1[$n]->getX() . ", " . $this->pos1[$n]->getY() . ", " . $this->pos1[$n]->getZ() . ")");

            $event->setCancelled();

        } else if(isset($this->sel2[$n])) {

            unset($this->sel2[$n]);

            $this->pos2[$n] = new Vector3($b->getX(),$b->getY(),$b->getZ());

            $p->sendMessage("Position 2 set to: (" . $this->pos2[$n]->getX() . ", " . $this->pos2[$n]->getY() . ", " . $this->pos2[$n]->getZ() . ")");

            $event->setCancelled();

        } else if(isset($this->start[$n])) {

            unset($this->start[$n]);

            $this->startMark[$n] = new Vector3($b->getX(),$b->getY(),$b->getZ());

            $p->sendMessage("Start position set to: (" . $this->startMark[$n]->getX() . ", " . $this->startMark[$n]->getY() . ", " . $this->startMark[$n]->getZ() . ")");

            $event->setCancelled();

        }

    }

    public function onBlockPlace(BlockPlaceEvent $event) {

        $b = $event->getBlock();

        $p = $event->getPlayer();

        $n = strtolower($p->getName());

        if(isset($this->sel1[$n])) {

            unset($this->sel1[$n]);

            $this->pos1[$n] = new Vector3($b->getX(),$b->getY(),$b->getZ());

            $p->sendMessage("Position 1 set to: (" . $this->pos1[$n]->getX() . ", " . $this->pos1[$n]->getY() . ", " . $this->pos1[$n]->getZ() . ")");

            $event->setCancelled();

        } else if(isset($this->sel2[$n])) {

            unset($this->sel2[$n]);

            $this->pos2[$n] = new Vector3($b->getX(),$b->getY(),$b->getZ());

            $p->sendMessage("Position 2 set to: (" . $this->pos2[$n]->getX() . ", " . $this->pos2[$n]->getY() . ", " . $this->pos2[$n]->getZ() . ")");

            $event->setCancelled();

        } else if(isset($this->start[$n])) {

            unset($this->start[$n]);

            $this->startMark[$n] = new Vector3($b->getX(),$b->getY(),$b->getZ());

            $p->sendMessage("Start position set to: (" . $this->startMark[$n]->getX() . ", " . $this->startMark[$n]->getY() . ", " . $this->startMark[$n]->getZ() . ")");

            $event->setCancelled();

        }

    }

    

}
