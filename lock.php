<?php
/*
    Copyright (C) 2016-2017 Hunter Ashton

    This file is part of BruhhBot.

    BruhhBot is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    BruhhBot is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with BruhhBot. If not, see <http://www.gnu.org/licenses/>.
 */

function lockme($update, $MadelineProto, $msg)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $mods = "Only the powers that be may use this command!";
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            );
        if (is_moderated($ch_id)) {
            $coniguration = file_get_contents("configuration.json");
            $cfg = json_decode($coniguration, true);
            if (!empty($msg)) {
                if (in_array($msg, $cfg["types"])) {
                    if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                        check_json_array('locked.json', $ch_id);
                        $file = file_get_contents("locked.json");
                        $locked = json_decode($file, true);
                        if (array_key_exists($ch_id, $locked)) {
                            if (!in_array($msg, $locked[$ch_id])) {
                                if ($msg == "flood") {
                                    $locked[$ch_id]['floodlimit'] = 10;
                                }
                                array_push($locked[$ch_id], $msg);
                                file_put_contents('locked.json', json_encode($locked));
                                $message = $cfg["lock"][$msg];
                                $entity = [['_' => 'messageEntityBold',
                                'offset' => 0,
                                'length' => $cfg['length'][$msg] ]];
                                $default['message'] = $message;
                                $default['entities'] = $entity;
                            } else {
                                $message = $cfg["lock"]["already"][$msg];
                                $entity = [['_' => 'messageEntityBold',
                                'offset' => 0,
                                'length' => $cfg['length'][$msg] ]];
                                $default['message'] = $message;
                                $default['entities'] = $entity;
                            }
                        } else {
                            $locked[$ch_id] = [];
                            if ($msg == "flood") {
                                $locked[$ch_id]['floodlimit'] = 10;
                            }
                            array_push($locked[$ch_id], $msg);
                            file_put_contents('locked.json', json_encode($locked));
                            $message = $cfg["lock"][$msg];
                            $entity = [['_' => 'messageEntityBold',
                                'offset' => 0,
                                'length' => $cfg['length'][$msg] ]];
                            $default['message'] = $message;
                            $default['entities'] = $entity;
                        }
                    }
                } else {
                    $message = "$msg is not a valid lock type";
                    $default['message'] = $message;
                }
            } else {
                $message = "Use /lock [type]";
                $default['message'] = $message;
            }
        }
        if (isset($default['message'])) {
            $sentMessage = $MadelineProto->messages->sendMessage(
                $default
            );
        }
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}

function unlockme($update, $MadelineProto, $msg)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $mods = "Only the powers that be may use this command!";
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            );
        if (is_moderated($ch_id)) {
            $coniguration = file_get_contents("configuration.json");
            $cfg = json_decode($coniguration, true);
            if (!empty($msg)) {
                if (in_array($msg, $cfg["types"])) {
                    if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                        check_json_array('locked.json', $ch_id);
                        $file = file_get_contents("locked.json");
                        $locked = json_decode($file, true);
                        if (array_key_exists($ch_id, $locked)) {
                            if (in_array($msg, $locked[$ch_id])) {
                                if (($key = array_search(
                                    $msg,
                                    $locked[$ch_id]
                                )) !== false
                                ) {
                                    unset($locked[$ch_id][$key]);
                                }
                                if ($msg == "flood") {
                                    unset($locked[$ch_id]['floodlimit']);
                                }
                                file_put_contents('locked.json', json_encode($locked));
                                $message = $cfg["unlock"][$msg];
                                $entity = [['_' => 'messageEntityBold',
                                'offset' => 0,
                                'length' => $cfg['length'][$msg]]];
                                $default['message'] = $message;
                                $default['entities'] = $entity;
                            } else {
                                $message = $cfg["unlock"]["already"][$msg];
                                $entity = [['_' => 'messageEntityBold',
                                'offset' => 0,
                                'length' => $cfg['length'][$msg]]];
                                $default['message'] = $message;
                                $default['entities'] = $entity;
                            }
                        } else {
                            $locked[$ch_id] = [];
                            if ($msg == "flood") {
                                unset($locked[$ch_id]['floodlimit']);
                            }
                            file_put_contents('locked.json', json_encode($locked));
                            $message = $cfg["unlock"]["already"][$msg];
                            $entity = [['_' => 'messageEntityBold',
                                'offset' => 0,
                                'length' => $cfg['length'][$msg]]];
                            $default['message'] = $message;
                            $default['entities'] = $entity;
                        }
                    }
                } else {
                    $message = "$msg is not a valid lock type";
                    $default['message'] = $message;
                }
            } else {
                $message = "Use /unlock [type]";
                $default['message'] = $message;
            }
        }
        if (isset($default['message'])) {
            $sentMessage = $MadelineProto->messages->sendMessage(
                $default
            );
        }
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}

function setflood($update, $MadelineProto, $msg)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $mods = "The floodgates only respond to mods";
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            );
        if (is_moderated($ch_id)) {
            if (!empty($msg)) {
                if (is_numeric($msg)) {
                    if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                        check_json_array('locked.json', $ch_id);
                        $file = file_get_contents("locked.json");
                        $locked = json_decode($file, true);
                        if (array_key_exists($ch_id, $locked)) {
                            $locked[$ch_id]['floodlimit'] = (int) $msg;
                            file_put_contents('locked.json', json_encode($locked));
                            $message = "Flood has been set to $msg";
                            $entity = [['_' => 'messageEntityBold',
                            'offset' => strlen($message) - strlen($msg),
                            'length' => strlen($msg) ]];
                            $default['message'] = $message;
                            $default['entities'] = $entity;
                        } else {
                            $locked[$ch_id] = [];
                            $locked[$ch_id]['floodlimit'] = (int) $msg;
                            file_put_contents('locked.json', json_encode($locked));
                            $message = "Flood has been set to $msg";
                            $entity = [['_' => 'messageEntityBold',
                            'offset' => strlen($message) - strlen($msg),
                            'length' => strlen($msg) ]];
                            $default['message'] = $message;
                            $default['entities'] = $entity;
                        }
                    }
                } else {
                    $message = "$msg is not a numeric value.";
                    $default['message'] = $message;
                }
            } else {
                $message = "Use /setflood integer";
                $default['message'] = $message;
            }
        }
        if (isset($default['message'])) {
            $sentMessage = $MadelineProto->messages->sendMessage(
                $default
            );
        }
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}
