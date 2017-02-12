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
function banme($update, $MadelineProto, $msg, $send = true)
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
        if (is_moderated($ch_id)) {
            if (is_bot_admin($update, $MadelineProto)) {
                if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                    if ($msg) {
                        $id = catch_id($update, $MadelineProto, $msg);
                        if ($id[0]) {
                            $userid = $id[1];
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
                                $username = $id[2];
                                check_json_array('banlist.json', $ch_id);
                                $file = file_get_contents("banlist.json");
                                $banlist = json_decode($file, true);
                                $mention = create_mention(5, $username, $userid);
                                if (array_key_exists($ch_id, $banlist)) {
                                    if (!in_array($userid, $banlist[$ch_id])) {
                                        array_push($banlist[$ch_id], $userid);
                                        file_put_contents(
                                            'banlist.json',
                                            json_encode($banlist)
                                        );
                                        $message = "User $username banned ".
                                        "from $title";
                                        $default['message'] = $message;
                                        $default['entities'] = $mention;
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
                                        $message = "User $username already banned";
                                        $default['message'] = $message;
                                        $default['entities'] = $mention;
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
                                    $default['entities'] = $mention;
                                }
                            }
                        }
                    } else {
                        $message = "Use /ban @username to ban someone from this ".
                        "chat!";
                        $code = create_style('code', 9, 9);
                        $default['entities'] = $code;
                        $default['message'] = $message;
                    }
                }
            }
        }
        if (isset($default['message']) && $send) {
            $sentMessage = $MadelineProto->messages->sendMessage($default);
        }
        if (isset($kick)) {
            \danog\MadelineProto\Logger::log($kick);
        }
        if (isset($sentMessage) && $send) {
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
        if (is_moderated($ch_id)) {
            if (is_bot_admin($update, $MadelineProto)) {
                if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                    if ($msg) {
                        $id = catch_id($update, $MadelineProto, $msg);
                        if ($id[0]) {
                            $userid = $id[1];
                        }
                        if (isset($userid)) {
                            $username = $id[2];
                            check_json_array('banlist.json', $ch_id);
                            $file = file_get_contents("banlist.json");
                            $banlist = json_decode($file, true);
                            $mention = create_mention(5, $username, $userid);
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
                                $default['entities'] = $mention;
                            }
                        }
                    } else {
                        $message = "Use /unban @username to unban someone from this ".
                        "chat!";
                        $code = create_style('italic', 11, 9);
                        $default['message'] = $message;
                        $default['entities'] = $code;
                    }
                }
            }
        }
        if (isset($default['message'])) {
            $sentMessage = $MadelineProto->messages->sendMessage(
                $default
            );
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
    if (is_supergroup($update, $MadelineProto)) {
        $mods = "Only mods can use me to kick butts!";
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id
        );
        if (is_moderated($ch_id)) {
            if (is_bot_admin($update, $MadelineProto)) {
                if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                    if ($msg) {
                        $id = catch_id($update, $MadelineProto, $msg);
                        if ($id[0]) {
                            $userid = $id[1];
                        }
                        if (isset($userid)) {
                            $username = $id[2];
                            $kickmod = "You can't kick mods?!?!";
                            if (!is_admin_mod(
                                $update,
                                $MadelineProto,
                                $userid,
                                $kickmod,
                                true
                            )
                            ) {
                                $username = $id[2];
                                $mention = create_mention(5, $username, $userid);
                                try {
                                    $kick = $MadelineProto->
                                    channels->kickFromChannel(
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
                                    $default['entities'] = $mention;
                                } catch (\danog\MadelineProto\RPCErrorException $e) {
                                    $mention = create_mention(0, $username, $userid);
                                    $message = "$username isn't even here man.";
                                    $default['message'] = $message;
                                    $default['entities'] = $mention;
                                }

                            }
                        }
                    } else {
                        $message = "Use /kick @username to kick someone from this ".
                        "chat!";
                        $code = create_styl('code', 9, 9);
                        $default['message'] = $message;
                        $default['entities'] = $code;
                    }
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

function kickme($update, $MadelineProto)
{
    $msg_id = $update['update']['message']['id'];
    if (is_supergroup($update, $MadelineProto)) {
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id
        );
        $userid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
        if (is_moderated($ch_id)) {
            if (is_bot_admin($update, $MadelineProto)) {
                if (!from_admin_mod($update, $MadelineProto)) {
                    $id = catch_id($update, $MadelineProto, $userid);
                    $username = $id[2];
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
                        $default['entities'] = $mention;
                    } catch (\danog\MadelineProto\RPCErrorException $e) {
                        $mention = create_mention(0, $username, $userid);
                        $message = "$username isn't even here man.";
                        $default['message'] = $message;
                        $default['entities'] = $mention;
                    }
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

function getbanlist($update, $MadelineProto)
{
    $msg_id = $update['update']['message']['id'];
    if (is_supergroup($update, $MadelineProto)) {
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id
        );
        if (is_moderated($ch_id)) {
            $message = "Banned Users for $title:"."\r\n";
            $len = 17 + strlen($title);
            $messageEntityBold = create_style('bold', 0, $len);
            check_json_array('banlist.json', $ch_id);
            $file = file_get_contents("banlist.json");
            $banlist = json_decode($file, true);
            if (array_key_exists($ch_id, $banlist)) {
                foreach ($banlist[$ch_id] as $i => $key) {
                    $user = cache_get_info($update, $MadelineProto, (int) $key);
                    $username = catch_id($update, $MadelineProto, $key)[2];
                    if (!isset($entity_)) {
                        $offset = strlen($message);
                        $entity_ = [['_' => 'inputMessageEntityMentionName', 'offset' =>
                        $offset, 'length' => strlen($username), 'user_id' =>
                        $key]];
                        $length = $offset + strlen($username) + strlen($key) + 5;
                        $message = $message."$username [$key]"."\r\n";
                    } else {
                        $entity_[] = ['_' =>
                        'inputMessageEntityMentionName', 'offset' => $length,
                        'length' => strlen($username), 'user_id' => $key];
                        $length = $length + 5 + strlen($username) + strlen($key);
                        $message = $message."$username [$key]"."\r\n";
                    }
                }
            }
            if (!isset($entity_)) {
                $entity = [['_' => 'messageEntityBold', 'offset' => 30,
                'length' => strlen($title) ]];
                $message = "There are no banned users for ".$title;
                $default['message'] = $message;
                $default['entities'] = $entity;
                $sentMessage = $MadelineProto->messages->sendMessage(
                    $default
                );
            }
            if (!isset($sentMessage)) {
                $entity = $entity_;
                $entity[] = $messageEntityBold;
                unset($entity_);
                $default['message'] = $message;
                $default['entities'] = $entity;
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

function unbanall($update, $MadelineProto, $msg)
{
    $msg_id = $update['update']['message']['id'];
    if (is_supergroup($update, $MadelineProto)) {
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id
        );
        if (is_moderated($ch_id)) {
            if (is_bot_admin($update, $MadelineProto)) {
                if (from_master($update, $MadelineProto)) {
                    if ($msg) {
                        if (is_numeric($msg)) {
                            $userid_ = (int) $msg;
                            $id = catch_id($update, $MadelineProto, $userid_);
                            if ($id[0]) {
                                $userid = $id[1];
                            }
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
                            check_json_array('gbanlist.json', false, false);
                            $file = file_get_contents("gbanlist.json");
                            $gbanlist = json_decode($file, true);
                            $mention = [[
                            '_' => 'inputMessageEntityMentionName',
                            'offset' => 5,
                            'length' => strlen($username),
                            'user_id' => $userid]];
                            if (in_array($userid, $gbanlist)) {
                                if (($key = array_search(
                                    $userid,
                                    $gbanlist
                                )) !== false
                                ) {
                                    unset($gbanlist[$key]);
                                }
                                file_put_contents(
                                    'gbanlist.json',
                                    json_encode($gbanlist)
                                );
                                $message = "User $username has been given ".
                                "another chance";
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
                                $message = "User ".$username." is not globally".
                                "banned. I already like them";
                                $default['message'] = $message;
                                $default['entities'] = $mention;
                            }
                        }
                    } else {
                        $message = "Use /unbanall @username to unban someone from ".
                        "all my groups!";
                        $code = [['_' => 'messageEntityItalic', 'offset' => 14,
                        'length' => 9]];
                        $default['message'] = $message;
                        $default['entities'] = $code;
                    }
                }
            }
        }
        if (isset($default['message'])) {
            $sentMessage = $MadelineProto->messages->sendMessage(
                $default
            );
        }
        if (isset($kick)) {
            \danog\MadelineProto\Logger::log($kick);
        }
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}

function banall($update, $MadelineProto, $msg, $send = true)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            );
        if (is_moderated($ch_id)) {
            if (is_bot_admin($update, $MadelineProto)) {
                if (from_master($update, $MadelineProto)) {
                    if ($msg) {
                        if (is_numeric($msg)) {
                            var_dump(true);
                            $userid_ = (int) $msg;
                            $id = catch_id($update, $MadelineProto, $userid_);
                            if ($id[0]) {
                                $userid = $id[1];
                            }
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
                                    check_json_array('gbanlist.json', false, false);
                                    $file = file_get_contents("gbanlist.json");
                                    $gbanlist = json_decode($file, true);
                                    $mention = [[
                                    '_' => 'inputMessageEntityMentionName',
                                    'offset' => 5,
                                    'length' => strlen($username),
                                    'user_id' => $userid]];
                                    if (!in_array($userid, $gbanlist)) {
                                        array_push($gbanlist, $userid);
                                        file_put_contents(
                                            'gbanlist.json',
                                            json_encode($gbanlist)
                                        );
                                        $message = "User $username has been ".
                                        "globally banned! I do NOT like them!";
                                        $default['message'] = $message;
                                        $default['entities'] = $mention;
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
                                        $message = "User ".$username." already ".
                                        "globally banned";
                                        $default['message'] = $message;
                                        $default['entities'] = $mention;
                                    }
                                }
                            }
                        }
                    } else {
                        $message = "Use /banall @username to ban someone from ".
                        "all my groups!";
                        $code = [['_' => 'messageEntityItalic', 'offset' => 12,
                        'length' => 12]];
                        $default['entities'] = $code;
                        $default['message'] = $message;
                    }
                }
            }
        }
        if (isset($default['message']) && $send) {
            $sentMessage = $MadelineProto->messages->sendMessage($default);
        }
        if (isset($kick)) {
            \danog\MadelineProto\Logger::log($kick);
        }
        if (isset($sentMessage) && $send) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}

function getgbanlist($update, $MadelineProto)
{
    $msg_id = $update['update']['message']['id'];
    if (is_supergroup($update, $MadelineProto)) {
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id
        );
        if (is_moderated($ch_id)) {
            $message = "PEOPLE I DO NOT LIKE:"."\r\n";
            $messageEntityBold = ['_' => 'messageEntityBold', 'offset' => 0,
            'length' => strlen($message) ];
            check_json_array('gbanlist.json', false, false);
            $file = file_get_contents("gbanlist.json");
            $gbanlist = json_decode($file, true);
            foreach ($gbanlist as $i => $key) {
                var_dump($key);
                $user = cache_get_info($update, $MadelineProto, (int) $key);
                $username = catch_id($update, $MadelineProto, $key)[2];
                if (!isset($entity_)) {
                    $offset = strlen($message);
                    $entity_ = [['_' => 'inputMessageEntityMentionName', 'offset' =>
                    $offset, 'length' => strlen($username), 'user_id' =>
                    $key]];
                    $length = $offset + strlen($username) + 2;
                    $message = $message.$username."\r\n";
                } else {
                    $entity_[] = ['_' =>
                    'inputMessageEntityMentionName', 'offset' => $length,
                    'length' => strlen($username), 'user_id' => $key];
                    $length = $length + 2 + strlen($username);
                    $message = $message.$username."\r\n";
                }
            }
            if (!isset($entity_)) {
                $entity = [['_' => 'messageEntityBold', 'offset' => 43,
                'length' => 8 ]];
                $message = "There are no users globally banned! I like everyone!";
                $default['message'] = $message;
                $default['entities'] = $entity;
                $sentMessage = $MadelineProto->messages->sendMessage(
                    $default
                );
            }
            if (!isset($sentMessage)) {
                $entity = $entity_;
                $entity[] = $messageEntityBold;
                unset($entity_);
                $default['message'] = $message;
                $default['entities'] = $entity;
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
