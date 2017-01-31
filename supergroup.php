#!/usr/bin/env php
<?php

function idme($update, $MadelineProto, $msg_arr) {
    switch ($update['update']['message']['to_id']['_']) {
        case 'peerUser':
            $peer = $MadelineProto->get_info($update['update']
            ['message']['from_id'])['bot_api_id'];
            $cont = "true";
            break;
        case 'peerChannel':
            $peer = $MadelineProto->
            get_info($update['update']['message']['to_id'])
            ['InputPeer'];
            $cont = "true";
            break;
    }
    if ($cont == "true") {
        $first_char = substr($msg_arr, 0, 1);
        if (preg_match_all('/@/', $first_char, $matches)) {
            try {
                $MadelineProto->get_info($msg_arr);
                $username = "@".$MadelineProto->get_info($msg_arr)['User']
                ['username'];
                $userid = $MadelineProto->get_info($msg_arr)['bot_api_id'];
                $message = "The Telegram ID of ".$username." is ".$userid;
            } catch (\danog\MadelineProto\RPCErrorException $e) {
                $message = "I can't find a user called ".$msg_arr.". Who's that?";
            }
        } else {
            if (array_key_exists('entities', $update['update']['message'])) {
                foreach ($update['update']['message']['entities'] as $key) {
                    if (array_key_exists('user_id', $key)) {
                        $userid = $key['user_id'];
                        $message = "The Telegram ID of ".$msg_arr." is ".$userid;
                        break;
                    } else {
                        $message = "I can't find a user called ".$msg_arr.". Who's that?";
                    }
                }
            }
            if (!isset($userid)) {
                $message = "I can't find a user called ".$msg_arr.". Who's that?";
            }
        }
        if (!isset($message)) {
            $message = "I can't find a user called ".$msg_arr.". Who's that?";
        }
        $sentMessage = $MadelineProto->messages->sendMessage
    (['peer' => $peer, 'message' => $message]);
    \danog\MadelineProto\Logger::log($sentMessage);

    }
}

function adminlist($update, $MadelineProto) {
    if ($update['update']['message']['to_id']['_'] == 'peerChannel') {
    $channelParticipantsAdmin = ['_' => 'channelParticipantsAdmins'];
    $peer = $MadelineProto->get_info($update['update']['message']['to_id'])
    ['InputPeer'];
    $title = $MadelineProto->get_info(-100 . $update['update']['message']
    ['to_id']['channel_id'])['Chat']['title'];
    $message = "Admins for $title"."\r\n";
    $messageEntityBold = ['_' => 'messageEntityBold', 'offset' => 11,
    'length' => strlen($title) ];
    $admins = $MadelineProto->channels->getParticipants(
    ['channel' => -100 . $update['update']['message']['to_id']['channel_id'],
    'filter' => $channelParticipantsAdmin, 'offset' => 0, 'limit' => 0]);
    foreach ($admins['users'] as $key) {
        $adminid = $key['id'];
        if (array_key_exists('username', $key)) {
        $adminname = "@".$key['username'];
        } else {
        $adminname = $key['first_name'];
        }
        if (!isset($entity_)) {
            $offset = strlen($message);
            $entity_ = [['_' => 'inputMessageEntityMentionName', 'offset' =>
            $offset, 'length' => strlen($adminname), 'user_id' =>
            $adminid]];
            $length = $offset + strlen($adminname) + 2;
            $message = $message.$adminname."\r\n";
            var_dump($length);
        } else {
            $entity_[] = ['_' =>
            'inputMessageEntityMentionName', 'offset' => $length,
            'length' => strlen($adminname), 'user_id' => $adminid];
            $length = $length + 2 + strlen($adminname);
            $message = $message.$adminname."\r\n";
        }
    }
    $entity = $entity_;
    $entity[] = $messageEntityBold;
    var_dump($entity_, true);
    unset($entity_);
    $sentMessage = $MadelineProto->messages->sendMessage
    (['peer' => $peer, 'message' => $message,
    'entities' => $entity]);
    \danog\MadelineProto\Logger::log($sentMessage);
    }
}

function banme($update, $MadelineProto, $msg_str) {
    if ($update['update']['message']['to_id']['_'] == 'peerChannel') {
        $channelParticipantsAdmin = ['_' => 'channelParticipantsAdmins'];
        $peer = $MadelineProto->get_info($update['update']['message']['to_id'])
        ['InputPeer'];
        $title = $MadelineProto->get_info(-100 . $update['update']['message']
        ['to_id']['channel_id'])['Chat']['title'];
        $ch_id = -100 . $update['update']['message']['to_id']['channel_id'];
        $messageEntityBold = ['_' => 'messageEntityBold', 'offset' => 11,
        'length' => strlen($title) ];
        $admins = $MadelineProto->channels->getParticipants(
    ['channel' => -100 . $update['update']['message']['to_id']['channel_id'],
        'filter' => $channelParticipantsAdmin, 'offset' => 0, 'limit' => 0, ]);
        if (is_numeric($msg_str)) {
            $userid = (int) $msg_str;
        } else {
            if (array_key_exists('entities', $update['update']['message'])) {
                foreach ($update['update']['message']['entities'] as $key) {
                    if (array_key_exists('user_id', $key)) {
                        $userid = $key['user_id'];
                        break;
                    } else {
                        $message = "I can't find a user called ".$msg_str.". Who's that?";
                    }
                }
            }
            if (!isset($userid)) {
                $first_char = substr($msg_str,
                0, 1);
                if (preg_match_all('/@/', $first_char, $matches)) {
                    try {
                        $userid = $MadelineProto->get_info($msg_str)['bot_api_id'];
                    } catch (\danog\MadelineProto\RPCErrorException $e) {
                        $message = "I can't find a user called ".$msg_str.". Who's that?";
            }
                } else {
                    $message = "I can't find a user called ".$msg_str.". Who's that?";
                    $sentMessage = $MadelineProto->messages->sendMessage
                    (['peer' => $peer, 'message' => $message]);
                    \danog\MadelineProto\Logger::log($sentMessage);
                }
            }
        }
            if (isset($userid)) {
            foreach ($admins['users'] as $key) {
                $adminid = $key['id'];
                if ($adminid == $userid) {
                    $mod = "true";
                    break;
                } else {
                    $mod = "false";
                }
            }
            if ($mod == "false") {
                $info = $MadelineProto->get_info($userid);
                var_dump($info);
                if (array_key_exists('username', $info['User'])) {
                    $username = $info['User']['username'];
                } else {
                    $username = $info['User']['first_name'];
                }
                if (!file_exists('banlist.json')) {
                    $json_data = [];
                    $json_data[$ch_id] = [];
                    file_put_contents('banlist.json', json_encode($json_data));
                }
                    $file = file_get_contents("banlist.json");
                    $banlist = json_decode($file, true);
                    if (array_key_exists($ch_id, $banlist)) {
                        if (!in_array($userid, $banlist[$ch_id])) {
                            array_push($banlist[$ch_id], $userid);
                            file_put_contents('banlist.json', json_encode($banlist));
                            $message = "User ".$username." banned from ".$title;
                            $kick = $MadelineProto->channels->kickFromChannel(
                ['channel' => $peer, 'user_id' => $userid, 'kicked' => true]);
                        } else {
                            $message = "User ".$username." is already banned from ".$title;
                        }
                    } else {
                        $banlist[$ch_id] = [];
                        $kick = $MadelineProto->channels->kickFromChannel(
            ['channel' => $peer, 'user_id' => $userid, 'kicked' => true, ]);
                        array_push($banlist[$ch_id], $userid);
                        file_put_contents('banlist.json', json_encode($banlist));
                        $message = "User ".$username." banned from ".$title;
                        }
            } else {
                $message = "You can't ban mods!?";
            }
            }
            $sentMessage = $MadelineProto->messages->sendMessage
            (['peer' => $peer, 'message' => $message]);
            if(isset($kick)) {
            \danog\MadelineProto\Logger::log($kick);
            }
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }


function unbanme($update, $MadelineProto, $msg_str) {
    if ($update['update']['message']['to_id']['_'] == 'peerChannel') {
        $channelParticipantsAdmin = ['_' => 'channelParticipantsAdmins'];
        $peer = $MadelineProto->get_info($update['update']['message']['to_id'])
        ['InputPeer'];
        $title = $MadelineProto->get_info(-100 . $update['update']['message']
        ['to_id']['channel_id'])['Chat']['title'];
        $ch_id = -100 . $update['update']['message']['to_id']['channel_id'];
        $messageEntityBold = ['_' => 'messageEntityBold', 'offset' => 11,
        'length' => strlen($title) ];
        $admins = $MadelineProto->channels->getParticipants(
    ['channel' => -100 . $update['update']['message']['to_id']['channel_id'],
        'filter' => $channelParticipantsAdmin, 'offset' => 0, 'limit' => 0, ]);
        if (is_numeric($msg_str)) {
            $userid = (int) $msg_str;
        } else {
            if (array_key_exists('entities', $update['update']['message'])) {
                foreach ($update['update']['message']['entities'] as $key) {
                    if (array_key_exists('user_id', $key)) {
                        $userid = $key['user_id'];
                        break;
                    } else {
                        $message = "I can't find a user called ".$msg_str.". Who's that?";
                    }
                }
            }
            if (!isset($userid)) {
                $first_char = substr($msg_str,
                0, 1);
                if (preg_match_all('/@/', $first_char, $matches)) {
                    try {
                        $userid = $MadelineProto->get_info($msg_str)['bot_api_id'];
                    } catch (\danog\MadelineProto\RPCErrorException $e) {
                        $message = "I can't find a user called ".$msg_str.". Who's that?";
            }
                } else {
                    $message = "I can't find a user called ".$msg_str.". Who's that?";
                    $sentMessage = $MadelineProto->messages->sendMessage
                    (['peer' => $peer, 'message' => $message]);
                    \danog\MadelineProto\Logger::log($sentMessage);
                }
            }
        }
                if (isset($userid)) {
                $info = $MadelineProto->get_info($userid);
                if (array_key_exists('username', $info['User'])) {
                    $username = $info['User']['username'];
                } else {
                    $username = $info['User']['first_name'];
                }
                if (!file_exists('banlist.json')) {
                    $json_data = [];
                    $json_data[$ch_id] = [];
                    file_put_contents('banlist.json', json_encode($json_data));
                }
                    $file = file_get_contents("banlist.json");
                    $banlist = json_decode($file, true);
                    if (array_key_exists($ch_id, $banlist)) {
                        if (in_array($userid, $banlist[$ch_id])) {
                            if (($key = array_search
                    ($userid, $banlist[$ch_id])) !== false) {
                                unset($banlist[$ch_id][$key]);
                            }
                            file_put_contents('banlist.json', json_encode($banlist));
                            $message = "User ".$username." unbanned from ".$title;
                            $kick = $MadelineProto->channels->kickFromChannel(
                ['channel' => $peer, 'user_id' => $userid, 'kicked' => false]);
                        } else {
                            $message = "User ".$username." is already welcome in ".$title;
                        }
                    } else {
                        $message = "User ".$username." is already welcome in ".$title;
                        }
            }
            $sentMessage = $MadelineProto->messages->sendMessage
            (['peer' => $peer, 'message' => $message]);
            if(isset($kick)) {
            \danog\MadelineProto\Logger::log($kick);
            }
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
