#!/usr/bin/env php
<?php

function banme($update, $MadelineProto, $msg_str) {
    $msg_id = $update['update']['message']['id'];
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
        if (is_bot_admin($update, $MadelineProto)) {
            if (from_admin_mod($update, $MadelineProto)) {
                if ($msg_str) {
                    if (is_numeric($msg_str)) {
                        $userid = (int) $msg_str;
                    } else {
                        if (array_key_exists('entities', $update['update']['message'])) {
                            foreach ($update['update']['message']['entities'] as $key) {
                                if (array_key_exists('user_id', $key)) {
                                    $userid = $key['user_id'];
                                    break;
                                } else {
                                    $message = "I don't know anyone with the name ".$msg_str;
                                }
                            }
                        }
                        if (!isset($userid)) {
                            $first_char = substr($msg_str, 0, 1);
                            if (preg_match_all('/@/', $first_char, $matches)) {
                                try {
                                    $userid = $MadelineProto->get_info($msg_str)['bot_api_id'];
                                } catch (\danog\MadelineProto\RPCErrorException $e) {
                                    $message = "I can't find a user called ".$msg_str.". Who's that?";
                                }
                            } else {
                                $message = "I don't know anyone with the name ".$msg_str;
                                $sentMessage = $MadelineProto->messages->sendMessage
                                (['peer' => $peer, 'reply_to_msg_id' =>
                                $msg_id, 'message' => $message]);
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
                        if (is_master($userid, $MadelineProto)) {
                            $mod == "true";
                        }
                        if ($mod == "false") {
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
                                    if (!in_array($userid, $banlist[$ch_id])) {
                                        array_push($banlist[$ch_id], $userid);
                                        file_put_contents('banlist.json', json_encode($banlist));
                                        $message = "User ".$username." banned from ".$title;
                                        $kick = $MadelineProto->channels->kickFromChannel(
                            ['channel' => $peer, 'user_id' => $userid, 'kicked' => true]);
                                    } else {
                                        $message = "User ".$username." already banned";
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
                } else {
                $message = "Use /ban @username to ban someone from this chat!";
                $code = [['_' => 'messageEntityItalic', 'offset' => 9,
                'length' => 9]];
                $sentMessage = $MadelineProto->messages->sendMessage
                (['peer' => $peer, 'reply_to_msg_id' =>
                $msg_id, 'message' => $message, 'entities' => $code]);
                }
            } else {
                $message = "Only mods can use me to kick butts";
            }
        } else {
            $message = "I have to be an admin for this to work";
        }
        if (!isset($sentMessage)) {
            $sentMessage = $MadelineProto->messages->sendMessage
            (['peer' => $peer, 'reply_to_msg_id' =>
            $msg_id, 'message' => $message]);
        }
        if(isset($kick)) {
        \danog\MadelineProto\Logger::log($kick);
        }
        \danog\MadelineProto\Logger::log($sentMessage);
    }
}


function unbanme($update, $MadelineProto, $msg_str) {
    $msg_id = $update['update']['message']['id'];
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
        if (from_admin_mod($update, $MadelineProto)) {
            if ($msg_str) {
                if (is_numeric($msg_str)) {
                    $userid = (int) $msg_str;
                } else {
                    if (array_key_exists('entities', $update['update']['message'])) {
                        foreach ($update['update']['message']['entities'] as $key) {
                            if (array_key_exists('user_id', $key)) {
                                $userid = $key['user_id'];
                                break;
                            } else {
                                $message = "I don't know anyone with the name ".$msg_str;
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
                            $message = "I don't know anyone with the name ".$msg_str;
                            $sentMessage = $MadelineProto->messages->sendMessage
                            (['peer' => $peer, 'reply_to_msg_id' =>
                            $msg_id, 'message' => $message]);
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
                            $message = "User ".$username." is not banned!";
                        }
                    } else {
                        $message = "User ".$username." is not banned!";
                    }
                }
            } else {
                $message = "Use /unban @username to unban someone from this chat!";
                $code = [['_' => 'messageEntityItalic', 'offset' => 11,
                'length' => 9]];
                $sentMessage = $MadelineProto->messages->sendMessage
                (['peer' => $peer, 'reply_to_msg_id' =>
                $msg_id, 'message' => $message, 'entities' => $code]);
            }
        } else {
            $message = "Only mods can unban the unliked peoples";
        }
        if (!isset($sentMessage)) {
            $sentMessage = $MadelineProto->messages->sendMessage
            (['peer' => $peer, 'reply_to_msg_id' =>
            $msg_id, 'message' => $message]);
        }
        if(isset($kick)) {
            \danog\MadelineProto\Logger::log($kick);
        }
        \danog\MadelineProto\Logger::log($sentMessage);
    }
}