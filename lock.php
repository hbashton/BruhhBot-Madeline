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
    if ($update['update']['message']['to_id']['_'] == 'peerChannel') {
        $peer = $MadelineProto->get_info($update['update']['message']['to_id'])
        ['InputPeer'];
        $msg_id = $update['update']['message']['id'];
        $coniguration = file_get_contents("configuration.json");
        $cfg = json_decode($coniguration, true);
        if (!empty($msg)) {
            if (in_array($msg, $cfg["types"])) {
                $mods = "Only the powers that be may use this command";
                if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                    $ch_id = -100 .
                    $update['update']['message']['to_id']['channel_id'];
                    $title = $MadelineProto->get_info(
                        -100 . $update['update']['message']
                        ['to_id']['channel_id']
                    )['Chat']['title'];
                    if (!file_exists('locked.json')) {
                        $json_data = [];
                        $json_data[$ch_id] = [];
                        file_put_contents('locked.json', json_encode($json_data));
                    }
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
                            $entity = ['_' => 'messageEntityBold',
                            'offset' => 0,
                            'length' => strlen($msg) ];
                        } else {
                            $message = $cfg["lock"]["already"][$msg];
                            $entity = ['_' => 'messageEntityBold',
                            'offset' => 0,
                            'length' => strlen($msg) ];
                        }
                    } else {
                        $locked[$ch_id] = [];
                        if ($msg == "flood") {
                            $locked[$ch_id]['floodlimit'] = 10;
                        }
                        array_push($locked[$ch_id], $msg);
                        file_put_contents('locked.json', json_encode($locked));
                        $message = $cfg["lock"][$msg];
                        $entity = ['_' => 'messageEntityBold',
                            'offset' => 0,
                            'length' => strlen($msg) ];
                    }
                }
            } else {
                $message = "$msg is not a valid lock type";
            }
        } else {
            $message = "Use /lock [type]";
        }
        if (isset($mention)) {
            $mention[] = $entity;
            $sentMessage = $MadelineProto->messages->sendMessage(
                ['peer' => $peer, 'reply_to_msg_id' =>
                $msg_id, 'message' => $message, 'entities' => $mention]
            );
        } else {
            $sentMessage = $MadelineProto->messages->sendMessage(
                ['peer' => $peer, 'reply_to_msg_id' =>
                $msg_id, 'message' => $message]
            );
        }
        \danog\MadelineProto\Logger::log($sentMessage);
    }
}

function unlockme($update, $MadelineProto, $msg)
{
    if ($update['update']['message']['to_id']['_'] == 'peerChannel') {
        $peer = $MadelineProto->get_info($update['update']['message']['to_id'])
        ['InputPeer'];
        $msg_id = $update['update']['message']['id'];
        $coniguration = file_get_contents("configuration.json");
        $cfg = json_decode($coniguration, true);
        if (!empty($msg)) {
            if (in_array($msg, $cfg["types"])) {
                $mods = "Only the powers that be may use this command";
                if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                    $ch_id = -100 .
                    $update['update']['message']['to_id']['channel_id'];
                    $title = $MadelineProto->get_info(
                        -100 . $update['update']['message']
                        ['to_id']['channel_id']
                    )['Chat']['title'];
                    if (!file_exists('locked.json')) {
                        $json_data = [];
                        $json_data[$ch_id] = [];
                        file_put_contents('locked.json', json_encode($json_data));
                    }
                    $file = file_get_contents("locked.json");
                    $locked = json_decode($file, true);
                    if (array_key_exists($ch_id, $locked)) {
                        if (in_array($msg, $locked[$ch_id])) {
                            if (($key = array_search($msg, $locked[$ch_id])) !== false) {
                                unset($locked[$ch_id][$key]);
                            }
                            if ($msg == "flood") {
                                unset($locked[$ch_id]['floodlimit']);
                            }
                            file_put_contents('locked.json', json_encode($locked));
                            $message = $cfg["unlock"][$msg];
                            $entity = ['_' => 'messageEntityBold',
                            'offset' => 0,
                            'length' => strlen($msg) ];
                        } else {
                            $message = $cfg["unlock"]["already"][$msg];
                            $entity = ['_' => 'messageEntityBold',
                            'offset' => 0,
                            'length' => strlen($msg) ];
                        }
                    } else {
                        $locked[$ch_id] = [];
                        if ($msg == "flood") {
                            unset($locked[$ch_id]['floodlimit']);
                        }
                        file_put_contents('locked.json', json_encode($locked));
                        $message = $cfg["unlock"]["already"][$msg];
                        $entity = ['_' => 'messageEntityBold',
                            'offset' => 0,
                            'length' => strlen($msg) ];
                    }
                }
            } else {
                $message = "$msg is not a valid lock type";
            }
        } else {
            $message = "Use /unlock [type]";
        }
        if (isset($mention)) {
            $mention[] = $entity;
            $sentMessage = $MadelineProto->messages->sendMessage(
                ['peer' => $peer, 'reply_to_msg_id' =>
                $msg_id, 'message' => $message, 'entities' => $mention]
            );
        } else {
            $sentMessage = $MadelineProto->messages->sendMessage(
                ['peer' => $peer, 'reply_to_msg_id' =>
                $msg_id, 'message' => $message]
            );
        }
        \danog\MadelineProto\Logger::log($sentMessage);
    }
}
