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
function muteme($update, $MadelineProto, $msg, $send = true)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $mods = "You ain't shutting anyone up";
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
                            $banmod = "You can't mute your superiors";
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
                                    check_json_array('mutelist.json', $ch_id);
                                    $file = file_get_contents("mutelist.json");
                                    $mutelist = json_decode($file, true);
                                    $mention = [[
                                    '_' => 'inputMessageEntityMentionName',
                                    'offset' => 5,
                                    'length' => strlen($username),
                                    'user_id' => $userid]];
                                    if (array_key_exists($ch_id, $mutelist)) {
                                        if (!in_array($userid, $mutelist[$ch_id])) {
                                            array_push($mutelist[$ch_id], $userid);
                                            file_put_contents(
                                                'mutelist.json',
                                                json_encode($mutelist)
                                            );
                                            $message = "User $username has been muted";
                                            $default['message'] = $message;
                                            $default['entities'] = $mention;
                                        } else {
                                            $message = "User ".$username." already shut up";
                                            $default['message'] = $message;
                                            $default['entities'] = $mention;

                                        }
                                    } else {
                                        $mutelist[$ch_id] = [];
                                        array_push($mutelist[$ch_id], $userid);
                                        file_put_contents(
                                            'mutelist.json',
                                            json_encode($mutelist)
                                        );
                                        $message = "User ".$username." has been muted";
                                        $default['message'] = $message;
                                        $default['entities'] = $mention;
                                    }
                                }
                            }
                        }
                    } else {
                        $message = "Use /mute @username to make someone shut up";
                        $code = [['_' => 'messageEntityCode', 'offset' => 4,
                        'length' => 15]];
                        $default['entities'] = $code;
                        $default['message'] = $message;
                    }
                }
            }
        }
        if (isset($default['message']) && $send) {
            $sentMessage = $MadelineProto->messages->sendMessage($default);
        }
        if (isset($sentMessage) && $send) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}


function unmuteme($update, $MadelineProto, $msg)
{
    $msg_id = $update['update']['message']['id'];
    if (is_supergroup($update, $MadelineProto)) {
        $mods = "No one is gonna listen to you!";
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
                            check_json_array('mutelist.json', $ch_id);
                            $file = file_get_contents("mutelist.json");
                            $mutelist = json_decode($file, true);
                            $mention = [[
                            '_' => 'inputMessageEntityMentionName',
                            'offset' => 5,
                            'length' => strlen($username),
                            'user_id' => $userid]];
                            if (array_key_exists($ch_id, $mutelist)) {
                                if (in_array($userid, $mutelist[$ch_id])) {
                                    if (($key = array_search(
                                        $userid,
                                        $mutelist[$ch_id]
                                    )) !== false
                                    ) {
                                        unset($mutelist[$ch_id][$key]);
                                    }
                                    file_put_contents(
                                        'mutelist.json',
                                        json_encode($mutelist)
                                    );
                                    $message = "User $username has been unmuted";
                                    $default['message'] = $message;
                                    $default['entities'] = $mention;
                                } else {
                                    $message = "User ".$username." can already talk.";
                                    $default['message'] = $message;
                                    $default['entities'] = $mention;
                                }
                            } else {
                                $message = "User $username can already talk";
                                $default['message'] = $message;
                                $default['entities'] = $mention;
                            }
                        }
                    } else {
                        $message = "Use /unmute @username to unban someone from this ".
                        "chat!";
                        $code = [['_' => 'messageEntityCode', 'offset' => 4,
                        'length' => 17]];
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
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}

function muteall($update, $MadelineProto, $send = true)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $mods = "You ain't shutting anyone up";
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            );
        $userid = "all";
        $mention = [['_' => 'messageEntityBold',
        'offset' => 0,
        'length' => 8]];
        if (is_moderated($ch_id)) {
            if (is_bot_admin($update, $MadelineProto)) {
                if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                    check_json_array('mutelist.json', $ch_id);
                    $file = file_get_contents("mutelist.json");
                    $mutelist = json_decode($file, true);
                    if (array_key_exists($ch_id, $mutelist)) {
                        if (!in_array($userid, $mutelist[$ch_id])) {
                            $mention = [['_' => 'messageEntityBold',
                            'offset' => 0,
                            'length' => 8]];
                            $mention[] = ['_' => 'messageEntityBold',
                            'offset' => 58,
                            'length' => 13];
                            array_push($mutelist[$ch_id], $userid);
                            file_put_contents(
                                'mutelist.json',
                                 json_encode($mutelist)
                            );
                            $message = "Everyone has lost the right of free ".
                            "speech.\r\nThis is now a dictatorship.";
                            $default['message'] = $message;
                            $default['entities'] = $mention;
                        } else {
                            $mention = [['_' => 'messageEntityBold',
                            'offset' => 17,
                            'length' => 8],
                            ['_' => 'messageEntityBold',
                            'offset' => 27,
                            'length' => 8]];
                            $message = "I am the current dictator. ".
                            "Everyone has already lost their right to talk.";
                            $default['message'] = $message;
                            $default['entities'] = $mention;
                        }
                    } else {
                        $mutelist[$ch_id] = [];
                        array_push($mutelist[$ch_id], $userid);
                        file_put_contents(
                            'mutelist.json',
                             json_encode($mutelist)
                        );
                        $message = "Everyone has lost the right of free ".
                        "speech.\r\nThis is now a dictatorship.";
                        $default['message'] = $message;
                        $default['entities'] = $mention;
                    }
                }
            }
        }
        if (isset($default['message']) && $send) {
            $sentMessage = $MadelineProto->messages->sendMessage($default);
        }
        if (isset($sentMessage) && $send) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}

function unmuteall($update, $MadelineProto)
{
    $msg_id = $update['update']['message']['id'];
    if (is_supergroup($update, $MadelineProto)) {
        $mods = "No one is gonna listen to you!";
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $userid = "all";
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id
        );
        if (is_moderated($ch_id)) {
            if (is_bot_admin($update, $MadelineProto)) {
                if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                    check_json_array('mutelist.json', $ch_id);
                    $file = file_get_contents("mutelist.json");
                    $mutelist = json_decode($file, true);
                    $mention = [['_' => 'messageEntityBold',
                    'offset' => 0,
                    'length' => 12]];
                    if (array_key_exists($ch_id, $mutelist)) {
                        if (in_array($userid, $mutelist[$ch_id])) {
                            if (($key = array_search(
                                $userid,
                                $mutelist[$ch_id]
                            )) !== false
                            ) {
                                unset($mutelist[$ch_id][$key]);
                            }
                            file_put_contents(
                                'mutelist.json',
                                json_encode($mutelist)
                            );
                            $mention[] =
                            ['_' => 'messageEntityBold',
                            'offset' => 54,
                            'length' => 8];
                            $message = "The dictator has been ousted from ".
                            "power by a militia. ".
                            "Everyone now has the right of free speech";
                            $default['message'] = $message;
                            $default['entities'] = $mention;
                        } else {
                            $mention[] = ['_' => 'messageEntityBold',
                            'offset' => 41,
                            'length' => 8];
                            $message = "The dictator is nowhere to be ".
                            "found, and everyone already has the right to ".
                            "speak freely.";
                            $default['message'] = $message;
                            $default['entities'] = $mention;
                        }
                    } else {
                        $mention[] = ['_' => 'messageEntityBold',
                        'offset' => 41,
                        'length' => 8];
                        $message = "The dictator is nowhere to be ".
                        "found, and everyone already has the right to ".
                        "speak freely.";
                        $default['message'] = $message;
                        $default['entities'] = $mention;
                    }
                }
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


function getmutelist($update, $MadelineProto)
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
            $message = "Muted Users for $title:"."\r\n";
            $messageEntityBold = ['_' => 'messageEntityBold', 'offset' => 0,
            'length' => 16 + strlen($title) ];
            check_json_array('mutelist.json', $ch_id);
            $file = file_get_contents("mutelist.json");
            $mutelist = json_decode($file, true);
            if (array_key_exists($ch_id, $mutelist)) {
                if (!in_array('all', $mutelist[$ch_id])) {
                    foreach ($mutelist[$ch_id] as $i => $key) {
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
                } else {
                    $entity_ = [['_' => 'messageEntityBold', 'offset' => 0,
                    'length' => 12]];
                    $default['message'] = "The Dictator reigns supreme.";
                    $default['entities'] = $entity_;
                    $sentMessage = $MadelineProto->messages->sendMessage(
                        $default
                    );
                }
            }
            if (!isset($entity_)) {
                $entity = [['_' => 'messageEntityBold', 'offset' => 30,
                'length' => strlen($title) ]];
                $message = "There are no muted users for ".$title;
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
        }
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}
