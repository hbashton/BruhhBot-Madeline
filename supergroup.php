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
function addadmin($update, $MadelineProto, $msg)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $mods = "Only my master can promote new admins";
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            );
        $fromid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
        if (is_bot_admin($update, $MadelineProto)) {
            if (from_master($update, $MadelineProto, $mods, true)) {
                $id = catch_id($update, $MadelineProto, $msg);
                if ($id[0]) {
                    $userid = $id[1];
                    $username = $id[2];
                } else {
                    $message = "I can't find a user called ".
                    "$msg. Who's that?";
                    $default['message'] = $message;
                }
                if (isset($userid)) {
                        $channelRoleModerator = [
                            '_' => 'channelRoleModerator',
                        ];
                        try {
                            $editadmin = $MadelineProto->channels->editAdmin(
                                ['channel' => $peer, 'user_id' => $userid,
                                'role' => $channelRoleModerator ]
                            );
                            $entity = [[
                                '_' => 'inputMessageEntityMentionName',
                                'offset' => 0,
                                'length' => strlen($username),
                                'user_id' => $userid
                            ]];
                            $message = "$username is now an admin!!!!!";
                            $default['message'] = $message;
                            $default['entities'] = $entity;
                            $sentMessage = $MadelineProto->messages->sendMessage(
                                $default
                            );
                            \danog\MadelineProto\Logger::log($editadmin);

                        } catch (Exception $e) {
                            $message = "I am not the owner of this chat, and ".
                            "cannot add any admins";
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
    }
}
function rmadmin($update, $MadelineProto, $msg)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $mods = "Only mods can use me to set this chat's photo!";
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            );
        $fromid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
        if (is_bot_admin($update, $MadelineProto)) {
            if (from_master($update, $MadelineProto, $mods, true)) {
                $id = catch_id($update, $MadelineProto, $msg);
                if ($id[0]) {
                    $userid = $id[1];
                    $username = $id[2];
                } else {
                    $message = "I can't find a user called ".
                    "$msg. Who's that?";
                    $default['message'] = $message;
                }
                if (isset($userid)) {
                    try {
                        $channelRoleEmpty = ['_' => 'channelRoleEmpty', ];
                        $editadmin = $MadelineProto->channels->editAdmin(
                            ['channel' => $peer, 'user_id' => $userid,
                            'role' => $channelRoleEmpty ]
                        );
                        \danog\MadelineProto\Logger::log($editadmin);
                        $entity = [[
                                '_' => 'inputMessageEntityMentionName',
                                'offset' => 0,
                                'length' => strlen($username),
                                'user_id' => $userid
                            ]];
                        $message = "$username is..no longer an admin. I am sorry";
                        $default['message'] = $message;
                        $default['entities'] = $entity;
                        $sentMessage = $MadelineProto->messages->sendMessage(
                            $default
                        );
                    } catch (Exception $e) {
                        $message = "I am not the owner of this group, and cannot ".
                        "add or remove admins.";
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
    }
}
function idme($update, $MadelineProto, $msg_arr)
{
    if (is_peeruser($update, $MadelineProto)) {
        $peer = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
        $noid = "Your Telegram ID is $peer";
        $cont = true;
    }
    if (is_supergroup($update, $MadelineProto)) {
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $noid = "The Telegram ID of $title is $ch_id";
        $cont = true;
    }
    $default = array(
        'peer' => $peer,
        'reply_to_msg_id' => $msg_id,
        );
    if (isset($cont)) {
        $msg_id = $update['update']['message']['id'];
        $first_char = substr($msg_arr, 0, 1);
        if (preg_match_all('/@/', $first_char, $matches)) {
            $id = catch_id($update, $MadelineProto, $msg_arr);
            if ($id[0]) {
                $username = $id[2];
                $userid = $id[1];
                $message = "The Telegram ID of $username is $userid";
                $default['message'] = $message;
            } else {
                $message = "I can't find a user called $msg_arr. Who's that?";
                $default['message'] = $message;
            }
        } else {
            if (array_key_exists('entities', $update['update']['message'])) {
                foreach ($update['update']['message']['entities'] as $key) {
                    if (array_key_exists('user_id', $key)) {
                        $userid = $key['user_id'];
                        $message = "The Telegram ID of $msg_arr is $userid";
                        $default['message'] = $message;
                        break;
                    } else {
                        $message = "I can't find a user called $msg_arr. ".
                        "Who's that?";
                        $default['message'] = $message;
                    }
                }
            }
            if (!isset($userid)) {
                $message = "I can't find a user called $msg_arr. Who's that?";
                $default['message'] = $message;
            }
        }
        if (!isset($message)) {
            $message = "I can't find a user called $msg_arr. Who's that?";
            $default['message'] = $message;
        }
        if (empty($msg_arr)) {
            $message = $noid;
            $default['message'] = $message;
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

function adminlist($update, $MadelineProto)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $mods = "Only mods can use me to set this chat's photo!";
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            );
        $fromid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
        $message = "Admins for $title"."\r\n";
        $messageEntityBold = ['_' => 'messageEntityBold', 'offset' => 11,
        'length' => strlen($title) ];
        $admins = cache_get_chat_info($update, $MadelineProto);
        foreach ($admins['participants'] as $key) {
            if (array_key_exists('user', $key)) {
                $id = $key['user']['id'];
            } else {
                if (array_key_exists('bot', $key)) {
                    $id = $key['bot']['id'];
                }
            }
            $adminname = catch_id($update, $MadelineProto, $id)[2];
            if (array_key_exists("role", $key)) {
                if ($key['role'] == "moderator"
                    or $key['role'] == "creator") {
                    $mod = true;
                } else {
                    $mod = false;
                }
            } else {
                $mod = false;
            }
            if ($mod) {
                if (!isset($entity_)) {
                    $offset = strlen($message);
                    $entity_ = [['_' => 'inputMessageEntityMentionName', 'offset' =>
                    $offset, 'length' => strlen($adminname), 'user_id' =>
                    $id]];
                    $length = $offset + strlen($adminname) + 2;
                    $message = $message.$adminname."\r\n";
                } else {
                    $entity_[] = ['_' =>
                    'inputMessageEntityMentionName', 'offset' => $length,
                    'length' => strlen($adminname), 'user_id' => $id];
                    $length = $length + 2 + strlen($adminname);
                    $message = $message.$adminname."\r\n";
                }
            }
        }
        $entity = $entity_;
        $entity[] = $messageEntityBold;
        unset($entity_);
        $sentMessage = $MadelineProto->messages->sendMessage(
            ['peer' => $peer, 'reply_to_msg_id' =>
            $msg_id, 'message' => $message,
            'entities' => $entity]
        );
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}

function modlist($update, $MadelineProto)
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
        $message = "Moderators for $title:"."\r\n";
        $messageEntityBold = ['_' => 'messageEntityBold', 'offset' => 0,
        'length' => 15 + strlen($title) ];
        check_json_array('promoted.json', $ch_id);
        $file = file_get_contents("promoted.json");
        $promoted = json_decode($file, true);
        if (array_key_exists($ch_id, $promoted)) {
            foreach ($promoted[$ch_id] as $i => $key) {
                $user = $MadelineProto->cache_get_info($key)['User'];
                $username = catch_id($update, $MadelineProto, $key);
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
        }
        if (!isset($entity_)) {
            $entity = [['_' => 'messageEntityBold', 'offset' => 28,
            'length' => strlen($title) ]];
            $message = "There are no moderators for ".$title;
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

function pinmessage($update, $MadelineProto, $silent)
{
    $msg_id = $update['update']['message']['id'];
    if ($update['update']['message']['to_id']['_'] == 'peerChannel') {
        $mods = "I can pin messages! But YOU can't make me!!! ;)";
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id
        );
        if (is_bot_admin($update, $MadelineProto, true)) {
            if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                if (array_key_exists(
                    "reply_to_msg_id",
                    $update['update']['message']
                )
                ) {
                    try {
                    $pin_id = $update['update']['message']['reply_to_msg_id'];
                    $pin = $MadelineProto->
                    channels->updatePinnedMessage(
                        ['silent' => $silent,
                        'channel' => $peer,
                        'id' => $pin_id ]
                    );
                    $message = "Message successfully pinned!";
                    $default['message'] = $message;
                    \danog\MadelineProto\Logger::log($pin);
                    } catch (Exception $e) {
                    }
                } else {
                    $entity = [['_' => 'messageEntityCode', 'offset' => 37,
                    'length' => 4 ], ['_' => 'messageEntityCode', 'offset' => 73,
                    'length' => 14 ]];
                    $message = "Pin a message by replying to it with \r\n/pin\r\n".
                    "to pin it silently, reply with /pin silent";
                    $default['message'] = $message;
                    $default['entities'] = $entity;
                }
            }
            if (isset($default['message'])) {
                $sentMessage = $MadelineProto->messages->sendMessage(
                    $default
                );
                \danog\MadelineProto\Logger::log($sentMessage);
            }
        }
    }
}
