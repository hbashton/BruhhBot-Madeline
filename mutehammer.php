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
                        $id = catch_id($update, $MadelineProto, $msg);
                        if ($id[0]) {
                            $userid = $id[1];
                        }
                        if (isset($userid)) {
                            $banmod = "You can't mute Moderators";
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
                                    $message = "$userid is not a person I know of";
                                    $default['message'] = $message;
                                } else {
                                    $username = $id[2];
                                    check_json_array('mutelist.json', $ch_id);
                                    $file = file_get_contents("mutelist.json");
                                    $mutelist = json_decode($file, true);
                                    $mention = create_mention(5, $username, $userid);
                                    if (array_key_exists($ch_id, $mutelist)) {
                                        if (!in_array($userid, $mutelist[$ch_id])) {
                                            array_push($mutelist[$ch_id], $userid);
                                            file_put_contents(
                                                'mutelist.json',
                                                json_encode($mutelist)
                                            );
                                            $message = "User $username has been ".
                                            "stripped of their right to speak freely";
                                            $default['message'] = $message;
                                            $default['entities'] = $mention;
                                        } else {
                                            $message = "User $username is already ".
                                            "quiet";
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
                                        $message = "User $username has been muted";
                                        $default['message'] = $message;
                                        $default['entities'] = $mention;
                                    }
                                }
                            }
                        }
                    } else {
                        $message = "Use /mute @username to make someone shut up";
                        $code = create_style('code', 4, 15);
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
                        $id = catch_id($update, $MadelineProto, $msg);
                        if ($id[0]) {
                            $userid = $id[1];
                        }
                        if (isset($userid)) {
                            $username = $id[2];
                            check_json_array('mutelist.json', $ch_id);
                            $file = file_get_contents("mutelist.json");
                            $mutelist = json_decode($file, true);
                            $mention = create_mention(5, $username, $userid);
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
                                    $message = "User $username has regained their ".
                                    "right of free speach....as long as they speak ".
                                    "kindly of the Dictator.";
                                    $len = strlen($message) - 8;
                                    $entity = create_style(
                                        'bold',
                                        $len,
                                        $title,
                                        false
                                    );
                                    $mention[] = $entity;
                                    $default['message'] = $message;
                                    $default['entities'] = $mention;
                                } else {
                                    $message = "User $username can already speak ".
                                    "freely";
                                    $default['message'] = $message;
                                    $default['entities'] = $mention;
                                }
                            } else {
                                $message = "User $username can already speak freely";
                                $default['message'] = $message;
                                $default['entities'] = $mention;
                            }
                        }
                    } else {
                        $message = "Use /unmute @username to unban someone from this ".
                        "chat!";
                        $code = create_style('code', 4, 17);
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
        $style = create_style('bold', 0, 8);
        if (is_moderated($ch_id)) {
            if (is_bot_admin($update, $MadelineProto)) {
                if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                    check_json_array('mutelist.json', $ch_id);
                    $file = file_get_contents("mutelist.json");
                    $mutelist = json_decode($file, true);
                    if (array_key_exists($ch_id, $mutelist)) {
                        if (!in_array($userid, $mutelist[$ch_id])) {
                            $style[] = create_style('bold', 58, 13, false);
                            array_push($mutelist[$ch_id], $userid);
                            file_put_contents(
                                'mutelist.json',
                                 json_encode($mutelist)
                            );
                            $message = "Everyone has lost the right of free ".
                            "speech.\r\nThis is now a dictatorship.";
                            $default['message'] = $message;
                            $default['entities'] = $style;
                        } else {
                            $style = create_style('bold', 17, 8);
                            $message = "I am the current dictator. ".
                            "Everyone has already lost their right to talk.";
                            $default['message'] = $message;
                            $default['entities'] = $style;
                        }
                    } else {
                        $mutelist[$ch_id] = [];
                        array_push($mutelist[$ch_id], $userid);
                        file_put_contents(
                            'mutelist.json',
                             json_encode($mutelist)
                        );
                        $style[] = create_style('bold', 58, 13, false);
                        $message = "Everyone has lost the right of free ".
                        "speech.\r\nThis is now a dictatorship.";
                        $default['message'] = $message;
                        $default['entities'] = $style;
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
                    $style = create_style('bold', 0, 12);
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
                            $style[] = create_style('bold', 54, 8, false);
                            $message = "The dictator has been ousted from ".
                            "power by a militia. ".
                            "Everyone now has the right of free speech";
                            $default['message'] = $message;
                            $default['entities'] = $style;
                        } else {
                            $style[] = create_style('bold', 41, 9, false);
                            $message = "The dictator is nowhere to be ".
                            "found, and everyone already has the right to ".
                            "speak freely.";
                            $default['message'] = $message;
                            $default['entities'] = $style;
                        }
                    } else {
                        $style[] = create_style('bold', 41, 9, false);
                        $message = "The dictator is nowhere to be ".
                        "found, and everyone already has the right to ".
                        "speak freely.";
                        $default['message'] = $message;
                        $default['entities'] = $style;
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
            $message = "Muted Users for $title:\r\n";
            $style = create_style('bold', 0, $message, false);
            check_json_array('mutelist.json', $ch_id);
            $file = file_get_contents("mutelist.json");
            $mutelist = json_decode($file, true);
            if (array_key_exists($ch_id, $mutelist)) {
                if (!in_array('all', $mutelist[$ch_id])) {
                    foreach ($mutelist[$ch_id] as $i => $key) {
                        $username = catch_id($update, $MadelineProto, $key)[2];
                        if (!isset($entity)) {
                            $offset = strlen($message);
                            $entity = create_mention($offset, $username, $key);
                            $length = $offset + strlen($username) + strlen($key) + 5;
                            $message = $message."$username [$key]"."\r\n";
                        } else {
                            $entity[] = create_mention($length, $username, $key, false);
                            $length = $length + 5 + strlen($username) + strlen($key);
                            $message = $message."$username [$key]"."\r\n";
                        }
                    }
                } else {
                    $entity = create_style('bold', 0, 12);
                    $default['message'] = "The Dictator reigns supreme.";
                    $default['entities'] = $entity;
                    $sentMessage = $MadelineProto->messages->sendMessage(
                        $default
                    );
                }
            }
            if (!isset($entity)) {
                $entity = create_style('bold', 30, $title);
                $message = "There are no muted users for $title";
                $default['message'] = $message;
                $default['entities'] = $entity;
                $sentMessage = $MadelineProto->messages->sendMessage(
                    $default
                );
            }
            if (!isset($sentMessage)) {
                $entity[] = $style;
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
