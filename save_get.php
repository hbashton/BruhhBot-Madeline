<?php
/**
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
function saveme($update, $MadelineProto, $msg, $name)
{
    if (is_peeruser($update, $MadelineProto)) {
        $peer = cache_get_info(
            $update,
            $MadelineProto,
            $update['update']['message']['from_id']
        )['bot_api_id'];
        $ch_id = $peer;
        $cont = true;
        $peerUSER = true;
    }
    if (is_supergroup($update, $MadelineProto)) {
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $ch_id = $chat['id'];
        $cont = true;
        $peerUSER = false;
    }
    if ($cont) {
        $msg_id = $update['update']['message']['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            );
        $mods = "Only mods get to save messages. You don't fit that criteria.";
        if ($peerUSER
            or from_admin_mod($update, $MadelineProto, $mods, true)
        ) {
            if (!empty($name) && !empty($msg)) {
                check_json_array('saved.json', $ch_id);
                $file = file_get_contents("saved.json");
                $saved = json_decode($file, true);
                if ($name == "from") {
                    savefrom($update, $MadelineProto, $msg);
                    return;
                }
                if (array_key_exists($ch_id, $saved)) {
                    if (!array_key_exists("from", $saved[$ch_id])) {
                        $saved[$ch_id]["from"] = [];
                    }
                    if (array_key_exists($name, $saved[$ch_id]["from"])) {
                        unset($saved[$ch_id]["from"][$name]);
                    }
                    $saved[$ch_id][$name] = $msg;
                    file_put_contents('saved.json', json_encode($saved));
                    $message = "Message $name has been saved";
                    $code = [['_' => 'messageEntityBold', 'offset' => 8,
                    'length' => strlen($name)]];
                    $default['message'] = $message;
                    $default['entities'] = $code;
                } else {
                    $saved[$ch_id] = [];
                    $saved[$ch_id]["from"] = [];
                    $saved[$ch_id][$name] = $msg;
                    file_put_contents('saved.json', json_encode($saved));
                    $message = "Message $name has been saved";
                    $code = [['_' => 'messageEntityBold', 'offset' => 8,
                    'length' => strlen($name)]];
                    $default['message'] = $message;
                    $default['entities'] = $code;
                }
            } else {
                $message = "Use /save name message to save a message for later!";
                $code = [['_' => 'messageEntityBold', 'offset' => 10,
                    'length' => 4], ['_' => 'messageEntityBold', 'offset' => 15,
                    'length' => 7]];
                $default['message'] = $message;
                $default['entities'] = $code;
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
}

function getme($update, $MadelineProto, $name)
{
    if (is_peeruser($update, $MadelineProto)) {
        $peer = cache_get_info(
            $update,
            $MadelineProto,
            $update['update']['message']['from_id']
        )['bot_api_id'];
        $ch_id = $peer;
        $cont = true;
        $peerUSER = true;
    }
    if (is_supergroup($update, $MadelineProto)) {
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $ch_id = $chat['id'];
        $cont = true;
        $peerUSER = false;
    }
    if ($cont) {
        $msg_id = $update['update']['message']['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            );
        check_json_array('saved.json', $ch_id);
        $file = file_get_contents("saved.json");
        $saved = json_decode($file, true);
        if (array_key_exists($ch_id, $saved)) {
            foreach ($saved[$ch_id] as $i => $ii) {
                if ($i == $name) {
                    $message = $name.":"."\r\n".$saved[$ch_id][$i];
                    $default['message'] = $message;
                }
            }
            if (!isset($message)) {
                if (array_key_exists("from", $saved[$ch_id])) {
                    foreach ($saved[$ch_id]["from"] as $i => $ii) {
                        if ($i == $name) {
                            $replyid = $ii["msgid"];
                            $replychat = $ii["chat"];
                            break;
                        }
                    }
                }
            }
            if (isset($message)) {
                $entity = [['_' => 'messageEntityBold', 'offset' => 0,
                'length' => strlen($name)]];
                $default['entities'] = $entity;
                $sentMessage = $MadelineProto->messages->sendMessage(
                    $default
                );
            }
            if (isset($replyid)) {
                $sentMessage =$MadelineProto->messages->forwardMessages(
                    ['from_peer' => $replychat, 'id' => [$replyid], 'to_peer' =>
                    $peer, ]
                );
            }
        }
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}

function savefrom($update, $MadelineProto, $name)
{
    if (is_peeruser($update, $MadelineProto)) {
        $peer = cache_get_info(
            $update,
            $MadelineProto,
            $update['update']['message']['from_id']
        )['bot_api_id'];
        $ch_id = $peer;
        $cont = true;
        $peerUSER = true;
    }
    if (is_supergroup($update, $MadelineProto)) {
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $ch_id = $chat['id'];
        $cont = true;
        $peerUSER = false;
    }
    $replyto = $update['update']['message']['id'];
    $mods = "Only mods get to save messages. You don't fit that criteria.";
    if ($peerUSER or from_admin_mod($update, $MadelineProto, $mods, true)) {
        if (array_key_exists("reply_to_msg_id", $update["update"]["message"])) {
            $msg_id = $update['update']['message']["reply_to_msg_id"];
            check_json_array('saved.json', $ch_id);
            $file = file_get_contents("saved.json");
            $saved = json_decode($file, true);
            if (array_key_exists($ch_id, $saved)) {
                if (!array_key_exists("from", $saved[$ch_id])) {
                    $saved[$ch_id]["from"] = [];
                }
                $saved[$ch_id]["from"][$name] = [];
                $saved[$ch_id]["from"][$name]["chat"] = $ch_id;
                $saved[$ch_id]["from"][$name]["msgid"] = $msg_id;
                if (array_key_exists($name, $saved[$ch_id])) {
                    unset($saved[$ch_id][$name]);
                }
                file_put_contents('saved.json', json_encode($saved));
                $message = "Message ".$name." has been saved";
                $code = [['_' => 'messageEntityBold', 'offset' => 8,
                'length' => strlen($name)]];
                $default['message'] = $message;
                $default['entities'] = $code;
            } else {
                $saved[$ch_id] = [];
                $saved[$ch_id]["from"] = [];
                $saved[$ch_id]["from"][$name] = [];
                $saved[$ch_id]["from"][$name]["chat"] = $ch_id;
                $saved[$ch_id]["from"][$name]["msgid"] = $msg_id;
                file_put_contents('saved.json', json_encode($saved));
                $message = "Message ".$name." has been saved";
                $code = [['_' => 'messageEntityBold', 'offset' => 8,
                'length' => strlen($name)]];
                $default['message'] = $message;
                $default['entities'] = $code;
            }
        } else {
            $message = "Save a message by reply with /save from name";
            $code = [['_' => 'messageEntityCode', 'offset' => 40,
            'length' => 4]];
            $code[] = ['_' => 'messageEntityCode', 'offset' => 29,
            'length' => 10];
            $default['message'] = $message;
            $default['entities'] = $code;
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
