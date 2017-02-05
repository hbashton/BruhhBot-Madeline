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
    switch ($update['update']['message']['to_id']['_']) {
    case 'peerUser':
        $peer = $MadelineProto->get_info(
            $update['update']
            ['message']['from_id']
        )['bot_api_id'];
        $ch_id = $peer;
        $cont = "true";
        $peerUSER = "yes";
        break;
    case 'peerChannel':
        $peer = $MadelineProto->
        get_info($update['update']['message']['to_id'])
        ['InputPeer'];
        $ch_id = -100 . $update['update']['message']['to_id']['channel_id'];
        $cont = "true";
        break;
    }
    if (isset($cont)) {
        $msg_id = $update['update']['message']['id'];
        $mods = "Only mods get to save messages. You don't fit that criteria.";
        if (isset($peerUSER) 
            or from_admin_mod($update, $MadelineProto, $mods, true)
        ) {
            if (!empty($name) && !empty($msg)) {
                if (!file_exists('saved.json')) {
                    $json_data = [];
                    $json_data[$ch_id] = [];
                    file_put_contents('saved.json', json_encode($json_data));
                }
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
                    $message = "Message ".$name." has been saved";
                    $code = [['_' => 'messageEntityBold', 'offset' => 8,
                    'length' => strlen($name)]];
                    $sentMessage = $MadelineProto->messages->sendMessage(
                        ['peer' => $peer, 'reply_to_msg_id' =>
                        $msg_id, 'message' => $message, 'entities' => $code]
                    );
                } else {
                    $saved[$ch_id] = [];
                    $saved[$ch_id]["from"] = [];
                    $saved[$ch_id][$name] = $msg;
                    file_put_contents('saved.json', json_encode($saved));
                    $message = "Message ".$name." has been saved";
                    $code = [['_' => 'messageEntityBold', 'offset' => 8,
                    'length' => strlen($name)]];
                    $sentMessage = $MadelineProto->messages->sendMessage(
                        ['peer' => $peer, 'reply_to_msg_id' =>
                        $msg_id, 'message' => $message, 'entities' => $code]
                    );
                }
            } else {
                $message = "Use /save name message to save a message for later!";
                $code = [['_' => 'messageEntityBold', 'offset' => 10,
                    'length' => 4], ['_' => 'messageEntityBold', 'offset' => 15,
                    'length' => 7]];
                $sentMessage = $MadelineProto->messages->sendMessage(
                    ['peer' => $peer, 'reply_to_msg_id' =>
                    $msg_id, 'message' => $message, 'entities' => $code]
                );
            }
        }
        if (!isset($sentMessage)) {
            $sentMessage = $MadelineProto->messages->sendMessage(
                ['peer' => $peer, 'reply_to_msg_id' =>
                $msg_id, 'message' => $message]
            );
        }
        \danog\MadelineProto\Logger::log($sentMessage);
    }
}

function getme($update, $MadelineProto, $name) 
{
    switch ($update['update']['message']['to_id']['_']) {
    case 'peerUser':
        $peer = $MadelineProto->get_info(
            $update['update']
            ['message']['from_id']
        )['bot_api_id'];
        $ch_id = $peer;
        $cont = "true";
        $peerUSER = "yes";
        break;
    case 'peerChannel':
        $peer = $MadelineProto->
        get_info($update['update']['message']['to_id'])
        ['InputPeer'];
        $ch_id = -100 . $update['update']['message']['to_id']['channel_id'];
        $cont = "true";
        break;
    }
    if (isset($cont)) {
        $msg_id = $update['update']['message']['id'];
        if (!file_exists('saved.json')) {
            $json_data = [];
            $json_data[$ch_id] = [];
            file_put_contents('saved.json', json_encode($json_data));
        }
        $file = file_get_contents("saved.json");
        $saved = json_decode($file, true);
        if (array_key_exists($ch_id, $saved)) {
            foreach ($saved[$ch_id] as $i => $ii) {
                if ($i == $name) {
                    $message = $name.":"."\r\n".$saved[$ch_id][$i];
                }
            }
            if (!isset($message)) {
                var_dump("LOL");
                if (array_key_exists("from", $saved[$ch_id])) {
                    foreach ($saved[$ch_id]["from"] as $i => $ii) {
                        if ($i == $name) {
                            $replyid = $ii["msgid"];
                            $replychat = $ii["chat"];
                            break;
                        }
                        var_dump($i, $ii);
                    }
                }
            }
            if (isset($message)) {
                $entity = [['_' => 'messageEntityBold', 'offset' => 0,
                'length' => strlen($name)]];
                $sentMessage = $MadelineProto->messages->sendMessage(
                    ['peer' => $peer, 'reply_to_msg_id' =>
                    $msg_id, 'message' => $message, 'entities' => $entity]
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
    switch ($update['update']['message']['to_id']['_']) {
    case 'peerUser':
        $peer = $MadelineProto->get_info(
            $update['update']
            ['message']['from_id']
        )['bot_api_id'];
        $ch_id = $peer;
        $cont = "true";
        $peerUSER = "yes";
        break;
    case 'peerChannel':
        $peer = $MadelineProto->
        get_info($update['update']['message']['to_id'])
        ['InputPeer'];
        $ch_id = -100 . $update['update']['message']['to_id']['channel_id'];
        $cont = "true";
        break;
    }
    $replyto = $update['update']['message']['id'];
    $mods = "Only mods get to save messages. You don't fit that criteria.";
    if (isset($peerUSER) or from_admin_mod($update, $MadelineProto, $mods, true)) {
        if (array_key_exists("reply_to_msg_id", $update["update"]["message"])) {
            $msg_id = $update['update']['message']["reply_to_msg_id"];
            if (!file_exists('saved.json')) {
                $json_data = [];
                $json_data[$ch_id] = [];
                file_put_contents('saved.json', json_encode($json_data));
            }
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
                $sentMessage = $MadelineProto->messages->sendMessage(
                    ['peer' => $peer, 'reply_to_msg_id' =>
                    $msg_id, 'message' => $message, 'entities' => $code]
                );


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
                $sentMessage = $MadelineProto->messages->sendMessage(
                    ['peer' => $peer, 'reply_to_msg_id' =>
                    $msg_id, 'message' => $message, 'entities' => $code]
                );
            }
        } else {
            $message = "Save a message by reply with /save from name";
            $code = [['_' => 'messageEntityCode', 'offset' => 40,
            'length' => 4]];
            $code[] = ['_' => 'messageEntityCode', 'offset' => 29,
            'length' => 10];
            $sentMessage = $MadelineProto->messages->sendMessage(
                ['peer' => $peer, 'reply_to_msg_id' =>
                $replyto, 'message' => $message, 'entities' => $code]
            );
        }
    }
    if (!isset($sentMessage)) {
        $sentMessage = $MadelineProto->messages->sendMessage(
            ['peer' => $peer, 'reply_to_msg_id' =>
            $msg_id, 'message' => $message]
        );
    }
    \danog\MadelineProto\Logger::log($sentMessage);
}
