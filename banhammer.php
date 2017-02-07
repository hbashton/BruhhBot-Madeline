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
function banme($update, $MadelineProto, $msg)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $mods = "Only mods can use me to kick butts!";
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            );
        if (is_bot_admin($update, $MadelineProto)) {
            if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                if ($msg) {
                    if (is_numeric($msg)) {
                        $userid = (int) $msg;
                        $id = catch_id($update, $MadelineProto, $userid);
                    } else {
                        if (array_key_exists(
                            'entities',
                            $update['update']['message']
                        )
                        ) {
                            foreach ($update['update']['message']['entities']
                                as $key
                            ) {
                                if (array_key_exists('user_id', $key)) {
                                    $userid = $key['user_id'];
                                    $id = catch_id(
                                        $update,
                                        $MadelineProto,
                                        $userid
                                    );
                                    break;
                                } else {
                                    $message = "I don't know anyone with the name ".
                                    $msg;
                                }
                            }
                        }
                        if (!isset($userid)) {
                            $first_char = substr($msg, 0, 1);
                            if (preg_match_all('/@/', $first_char, $matches)) {
                                $id = catch_id($update, $MadelineProto, $msg);
                                if ($id[0]) {
                                    $userid = $id[1];
                                } else {
                                    $message = "I can't find a user called ".
                                    "$msg. Who's that?";
                                    $default['message'] = $message;
                                }
                            } else {
                                $message = "I don't know anyone with the name ".
                                $msg;
                                $default['message'] = $message;
                            }
                        }
                    }
                    if (isset($userid)) {
                        $banmod = "You can't ban mods?!?!";
                        if (!is_admin_mod(
                            $update,
                            $MadelineProto,
                            $userid,
                            $banmod,
                            true
                        )
                        ) {
                            $info = cache_get_info(
                                $update,
                                $MadelineProto,
                                $userid
                            );
                            if (!$info) {
                                $message = "$userid is not a valid ID";
                                $default['message'] = $message;
                            } else {
                                $username = $id[2];
                                check_json_array('banlist.json', $ch_id);
                                $file = file_get_contents("banlist.json");
                                $banlist = json_decode($file, true);
                                if (array_key_exists($ch_id, $banlist)) {
                                    if (!in_array($userid, $banlist[$ch_id])) {
                                        array_push($banlist[$ch_id], $userid);
                                        file_put_contents(
                                            'banlist.json',
                                            json_encode($banlist)
                                        );
                                        $message = "User $username banned from $title";
                                        try {
                                            $kick = $MadelineProto->
                                            channels->kickFromChannel(
                                                ['channel' => $peer,
                                                'user_id' => $userid,
                                                'kicked' => true]
                                            );
                                        } catch (
                                            \danog\MadelineProto\RPCErrorException
                                            $e
                                        ) {
                                        }
                                    } else {
                                        $message = "User ".$username." already banned";
                                        $default['message'] = $message;
                                    }
                                } else {
                                    $banlist[$ch_id] = [];
                                    try {
                                        $kick = $MadelineProto->
                                        channels->kickFromChannel(
                                            ['channel' => $peer,
                                            'user_id' => $userid,
                                            'kicked' => true, ]
                                        );
                                    } catch (\danog\MadelineProto\RPCErrorException $e) {
                                    }
                                    array_push($banlist[$ch_id], $userid);
                                    file_put_contents(
                                        'banlist.json',
                                        json_encode($banlist)
                                    );
                                    $message = "User ".$username." banned from ".$title;
                                    $default['message'] = $message;
                                }
                            }
                        }
                    } else {
                        $message = "Use /ban @username to ban someone from this chat!";
                        $code = [['_' => 'messageEntityItalic', 'offset' => 9,
                        'length' => 9]];
                        $default['entities'] = $code;
                        $default['message'] = $message;
                    }
                }
            }
        }
        if (!isset($sentMessage)) {
            if (isset($default['message'])) {
                $sentMessage = $MadelineProto->messages->sendMessage($default);
            }
        }
        if (isset($kick)) {
            \danog\MadelineProto\Logger::log($kick);
        }
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}


function unbanme($update, $MadelineProto, $msg)
{
    $msg_id = $update['update']['message']['id'];
    if (is_supergroup($update, $MadelineProto)) {
        $mods = "Only mods can use me to unban peeps!";
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id
        );
        if (is_bot_admin($update, $MadelineProto)) {
            if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                if ($msg) {
                    if (is_numeric($msg)) {
                        $userid = (int) $msg;
                        $id = catch_id($update, $MadelineProto, $userid);
                    } else {
                        if (array_key_exists(
                            'entities',
                            $update['update']['message']
                        )
                        ) {
                            foreach ($update['update']['message']['entities']
                                as $key
                            ) {
                                if (array_key_exists('user_id', $key)) {
                                    $userid = $key['user_id'];
                                    $id = catch_id($update, $MadelineProto, $userid);
                                    break;
                                } else {
                                    $message = "I don't know anyone with the name ".
                                    $msg;
                                    $default['message'] = $message;
                                }
                            }
                        }
                        if (!isset($userid)) {
                            $first_char = substr($msg, 0, 1);
                            if (preg_match_all('/@/', $first_char, $matches)) {
                                $id = catch_id($update, $MadelineProto, $msg);
                                if ($id[0]) {
                                    $userid = $id[1];
                                } else {
                                    $message = "I can't find a user called ".
                                    "$msg. Who's that?";
                                    $default['message'] = $message;
                                }
                            } else {
                                $message = "I don't know anyone with the name ".
                                $msg;
                                $default['message'] = $message;
                            }
                        }
                    }
                    if (isset($userid)) {
                        $username = $id[2];
                        check_json_array('banlist.json', $ch_id);
                        $file = file_get_contents("banlist.json");
                        $banlist = json_decode($file, true);
                        $mention = [[
                        '_' => 'inputMessageEntityMentionName',
                        'offset' => 5,
                        'length' => strlen($username),
                        'user_id' => $userid]];
                        if (array_key_exists($ch_id, $banlist)) {
                            if (in_array($userid, $banlist[$ch_id])) {
                                if (($key = array_search(
                                    $userid,
                                    $banlist[$ch_id]
                                )) !== false
                                ) {
                                    unset($banlist[$ch_id][$key]);
                                }
                                file_put_contents(
                                    'banlist.json',
                                    json_encode($banlist)
                                );
                                $message = "User $username unbanned from $title";
                                $default['message'] = $message;
                                $default['entities'] = $mention;
                                try {
                                    $kick = $MadelineProto->
                                    channels->kickFromChannel(
                                        ['channel' => $peer,
                                        'user_id' => $userid,
                                        'kicked' => false]
                                    );
                                } catch (\danog\MadelineProto\RPCErrorException $e) {
                                }
                            } else {
                                $message = "User ".$username." is not banned!";
                                $default['message'] = $message;
                                $default['entities'] = $mention;
                            }
                        } else {
                            $message = "User $username is not banned!";
                            $default['message'] = $message;
                            $default['entities'];
                        }
                    }
                } else {
                    $message = "Use /unban @username to unban someone from this ".
                    "chat!";
                    $code = [['_' => 'messageEntityItalic', 'offset' => 11,
                    'length' => 9]];
                    $default['message'] = $message;
                    $default['entities'] = $code;
                }
            }
        }
        if (!isset($sentMessage)) {
            if (isset($default['message'])) {
                $sentMessage = $MadelineProto->messages->sendMessage(
                    $default
                );
            }
        }
        if (isset($kick)) {
            \danog\MadelineProto\Logger::log($kick);
        }
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}


function kickhim($update, $MadelineProto, $msg)
{
    $msg_id = $update['update']['message']['id'];
    if ($update['update']['message']['to_id']['_'] == 'peerChannel') {
        $mods = "Only mods can use me to kick butts!";
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id
        );
        if (is_bot_admin($update, $MadelineProto)) {
            if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                if ($msg) {
                    if (is_numeric($msg)) {
                        $userid = (int) $msg;
                        $id = catch_id($update, $MadelineProto, $userid);
                    } else {
                        if (array_key_exists(
                            'entities',
                            $update['update']['message']
                        )
                        ) {
                            foreach ($update['update']['message']['entities']
                                as $key
                            ) {
                                if (array_key_exists('user_id', $key)) {
                                    $userid = $key['user_id'];
                                    $id = catch_id($update, $MadelineProto, $userid);
                                    break;
                                } else {
                                    $message = "I don't know anyone with the name ".
                                    $msg;
                                    $default['message'] = $message;
                                }
                            }
                        }
                        if (!isset($userid)) {
                            $first_char = substr($msg, 0, 1);
                            if (preg_match_all('/@/', $first_char, $matches)) {
                                $id = catch_id($update, $MadelineProto, $msg);
                                if ($id[0]) {
                                    $userid = $id[1];
                                } else {
                                    $message = "I can't find a user called ".
                                    "$msg. Who's that?";
                                    $default['message'] = $message;
                                }
                            } else {
                                $message = "I don't know anyone with the name ".
                                $msg;
                                $default['message'] = $message;
                            }
                        }
                    }
                    if (isset($userid)) {
                        $username = $id[2];
                        $kickmod = "You can't kick mods?!?!";
                        if (!is_admin_mod(
                            $update,
                            $MadelineProto,
                            $userid,
                            $kickmod,
                            true)) {
                            $mention = [[
                            '_' => 'inputMessageEntityMentionName',
                            'offset' => 5,
                            'length' => strlen($username),
                            'user_id' => $userid]];
                            $info = cache_get_info($update, $MadelineProto, $userid);
                            if (array_key_exists('username', $info['User'])) {
                                $username = $info['User']['username'];
                            } else {
                                $username = $info['User']['first_name'];
                            }
                            try {
                                $kick = $MadelineProto->channels->kickFromChannel(
                                    ['channel' => $peer,
                                    'user_id' => $userid,
                                    'kicked' => true]
                                );
                                $kickback = $MadelineProto->
                                channels->kickFromChannel(
                                    ['channel' => $peer,
                                    'user_id' => $userid,
                                    'kicked' => false]
                                );
                                $message = "User $username kicked from $title";
                                $default['message'] = $message;
                            } catch (\danog\MadelineProto\RPCErrorException $e) {
                                $mention = [[
                                '_' => 'inputMessageEntityMentionName',
                                'offset' => 0,
                                'length' => strlen($username),
                                'user_id' => $userid]];
                                $message = "$username isn't even here man.";
                                $default['message'] = $message;
                                $default['entities'] = $mention;
                            }

                        }
                    }
                } else {
                    $message = "Use /kick @username to kick someone from this ".
                    "chat!";
                    $code = [['_' => 'messageEntityItalic', 'offset' => 9,
                    'length' => 9]];
                    $default['message'] = $message;
                    $default['entities'] = $code;
                }
            }
        }
        if (!isset($sentMessage)) {
            if (isset($default['message'])) {
                $sentMessage = $MadelineProto->messages->sendMessage(
                    $default
                );
            }
        }
        if (isset($kick)) {
            \danog\MadelineProto\Logger::log($kick);
            \danog\MadelineProto\Logger::log($kickback);
        }
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}
