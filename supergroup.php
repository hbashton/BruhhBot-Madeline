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
        $title = htmlentities($chat['title']);
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode' => 'html'
            );
        if (is_moderated($ch_id)) {
            if (is_bot_admin($update, $MadelineProto)) {
                if (from_master($update, $MadelineProto, $mods, true)) {
                    $id = catch_id($update, $MadelineProto, $msg);
                    if ($id[0]) {
                        $userid = $id[1];
                        $username = $id[2];
                    } else {
                        $str = $MadelineProto->responses['addadmin']['idk'];
                        $repl = array(
                            "msg" => $msg
                        );
                        $message = $MadelineProto->engine->render($str, $repl);
                        $default['message'] = $message;
                    }
                    if (isset($userid)) {
                        $mention = html_mention($username, $userid);
                        $channelRoleModerator = [
                            '_' => 'channelRoleModerator',
                        ];
                        try {
                            $editadmin = $MadelineProto->channels->editAdmin(
                                ['channel' => $peer, 'user_id' => $userid,
                                'role' => $channelRoleModerator ]
                            );
                            $str = $MadelineProto->responses['addadmin']['success'];
                            $repl = array(
                                "mention" => $mention,
                                "title" => $title
                            );
                            $message = $MadelineProto->engine->render($str, $repl);
                            $default['message'] = $message;
                            \danog\MadelineProto\Logger::log($editadmin);

                        } catch (Exception $e) {
                            $message = $MadelineProto->responses['addadmin']['exception'];
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
}
function rmadmin($update, $MadelineProto, $msg)
{
    if (is_supergroup($update, $MadelineProto)) {
        
        $msg_id = $update['update']['message']['id'];
        $mods = "Only my master can humiliate someone like this";
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = htmlentities($chat['title']);
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode' => 'html'
            );
        if (is_moderated($ch_id)) {
            if (is_bot_admin($update, $MadelineProto)) {
                if (from_master($update, $MadelineProto, $mods, true)) {
                    $id = catch_id($update, $MadelineProto, $msg);
                    if ($id[0]) {
                        $userid = $id[1];
                        $username = $id[2];
                    } else {
                        $str = $MadelineProto->responses['rmadmin']['idk'];
                        $repl = array(
                            "msg" => $msg
                        );
                        $message = $MadelineProto->engine->render($str, $repl);
                        $default['message'] = $message;
                    }
                    if (isset($userid)) {
                        try {
                            $mention = html_mention($username, $userid);
                            $channelRoleEmpty = ['_' => 'channelRoleEmpty', ];
                            $editadmin = $MadelineProto->channels->editAdmin(
                                ['channel' => $peer, 'user_id' => $userid,
                                'role' => $channelRoleEmpty ]
                            );
                            \danog\MadelineProto\Logger::log($editadmin);
                            $str = $MadelineProto->responses['rmadmin']['success'];
                            $repl = array(
                                "mention" => $mention,
                                "title" => $title
                            );
                            $message = $MadelineProto->engine->render($str, $repl);
                            $default['message'] = $message;
                        } catch (Exception $e) {
                            $message = $MadelineProto->responses['rmadmin']['exception'];
                            $default['message'] = $message;
                        }
                    }
                    if (isset($default['message'])) {
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
    }
}
function idme($update, $MadelineProto, $msg)
{
    
    if (is_peeruser($update, $MadelineProto)) {
        $peer = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
        $str = $MadelineProto->responses['idme']['peeruser'];
        $repl = array(
            "peer" => $peer
        );
        $noid = $MadelineProto->engine->render($str, $repl);
        $cont = true;
    }
    if (is_supergroup($update, $MadelineProto)) {
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = htmlentities($chat['title']);
        $ch_id = $chat['id'];
        $str = $MadelineProto->responses['idme']['supergroup'];
        $tg_id = str_replace("-100", "", $ch_id);
        $repl = array(
            "title" => $title,
            "ch_id" => $tg_id
        );
        $noid = $MadelineProto->engine->render($str, $repl);
        $cont = true;
    }
    $msg_id = $update['update']['message']['id'];
    $default = array(
        'peer' => $peer,
        'reply_to_msg_id' => $msg_id,
        'parse_mode' => 'html'
        );
    if (isset($cont)) {
        if (!empty($msg)) {
            $id = catch_id($update, $MadelineProto, $msg);
            if ($id[0]) {
                $username = $id[2];
                $userid = $id[1];
                $mention = html_mention($username, $userid);
                $str = $MadelineProto->responses['idme']['idmessage'];
                $repl = array(
                    "mention" => $mention,
                    "userid" => $userid
                );
                $message = $MadelineProto->engine->render($str, $repl);
                $default['message'] = $message;
            }
            if (!isset($message)) {
                $str = $MadelineProto->responses['idme']['idk'];
                $repl = array(
                    "msg" => $msg
                );
                $message = $MadelineProto->engine->render($str, $repl);
                $default['message'] = $message;
            }
        } else {
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
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = htmlentities($chat['title']);
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode' => 'html'
            );
        $admins = cache_get_chat_info($update, $MadelineProto);
        foreach ($admins['participants'] as $key) {
            if (array_key_exists('user', $key)) {
                $id = $key['user']['id'];
            } else {
                if (array_key_exists('bot', $key)) {
                    $id = $key['bot']['id'];
                }
            }
            $username = catch_id($update, $MadelineProto, $id)[2];
            if (array_key_exists("role", $key)) {
                if ($key['role'] == "moderator"
                    or $key['role'] == "creator"
                ) {
                    $mod = true;
                } else {
                    $mod = false;
                }
            } else {
                $mod = false;
            }
            if ($mod) {
                $mention = html_mention($username, $id);
                if (!isset($message)) {
                    $str = $MadelineProto->responses['adminlist']['header'];
                    $repl = array(
                        "title" => $title
                    );
                    $message = $MadelineProto->engine->render($str, $repl);
                    $message = $message."$mention - $id\r\n";
                } else {
                    $message = $message."$mention - $id\r\n";
                }
            }
        }
        $default['message'] = $message;
        $sentMessage = $MadelineProto->messages->sendMessage(
            $default
        );
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}

function modlist($update, $MadelineProto)
{
    $msg_id = $update['update']['message']['id'];
    if (is_supergroup($update, $MadelineProto)) {
        
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = htmlentities($chat['title']);
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode' => 'html'
        );
        if (is_moderated($ch_id)) {
            check_json_array('promoted.json', $ch_id);
            $file = file_get_contents("promoted.json");
            $promoted = json_decode($file, true);
            if (array_key_exists($ch_id, $promoted)) {
                foreach ($promoted[$ch_id] as $i => $key) {
                    $username = catch_id($update, $MadelineProto, $key)[2];
                    $mention = html_mention($username, $key);
                    if (!isset($message)) {
                        $str = $MadelineProto->responses['modlist']['header'];
                        $repl = array(
                            "title" => $title
                        );
                        $message = $MadelineProto->engine->render($str, $repl);
                        $message = $message."$mention - $key\r\n";
                        $default['message'] = $message;
                    } else {
                        $message = $message."$mention - $key\r\n";
                        $default['message'] = $message;
                    }
                }
            }
            if (!isset($message)) {
                $str = $MadelineProto->responses['modlist']['none'];
                        $repl = array(
                            "title" => $title
                        );
                $message = $MadelineProto->engine->render($str, $repl);
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

function pinmessage($update, $MadelineProto, $silent)
{
    $msg_id = $update['update']['message']['id'];
    if (is_supergroup($update, $MadelineProto)) {
        
        $mods = $MadelineProto->responses['pinmessage']['mods'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $ch_id = $chat['id'];
        $title = htmlentities($chat['title']);
        $peer2 = cache_get_info(
            $update,
            $MadelineProto,
            getenv('MASTER_USERNAME')
        )['bot_api_id'];
        $tg_id = str_replace("-100", "", $ch_id);
        $fromid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
        $username = catch_id($update, $MadelineProto, $fromid)[2];
        $mention = html_mention($username, $fromid);
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode' => 'html'
        );
        $default2 = array(
            'peer' => $peer2,
            'parse_mode' => 'html'
        );
        if (is_moderated($ch_id)) {
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
                            $message = $MadelineProto->responses['pinmessage']['success'];
                            $default['message'] = $message;
                            \danog\MadelineProto\Logger::log($pin);
                            $message2 = "User $mention pinned a message in <b>$title</b> - $tg_id";
                            $default2['message'] = $message2;
                            $sentMessage2 = $MadelineProto->messages->sendMessage(
                                $default2
                            );
                            \danog\MadelineProto\Logger::log($sentMessage2);
                            $forwardMessage = $MadelineProto->messages->forwardMessages([
                                'silent' => false,
                                'from_peer' => $ch_id,
                                'id' => [$pin_id],
                                'to_peer' => $peer2]
                            );
                            \danog\MadelineProto\Logger::log($forwardMessage);
                        } catch (Exception $e) {
                        }
                    } else {
                        $message = $MadelineProto->responses['pinmessage']['help'];
                        $default['message'] = $message;
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
}

function delmessage($update, $MadelineProto)
{
    $msg_id = $update['update']['message']['id'];
    if (is_supergroup($update, $MadelineProto)) {
        
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode' => 'html'
        );
        if (is_moderated($ch_id)) {
            if (is_bot_admin($update, $MadelineProto, true)) {
                if (from_admin_mod($update, $MadelineProto)) {
                    if (array_key_exists(
                        "reply_to_msg_id",
                        $update['update']['message']
                    )
                    ) {
                        try {
                            $del_id = $update['update']['message']['reply_to_msg_id'];
                            $delete = $MadelineProto->channels->deleteMessages(
                                ['channel' => $peer,
                                'id' => [$del_id]]
                            );
                            \danog\MadelineProto\Logger::log($delete);
                            $del_id = $msg_id - 1;
                            $delete = $MadelineProto->channels->deleteMessages(
                                ['channel' => $peer,
                                'id' => [$msg_id]]
                            );
                            \danog\MadelineProto\Logger::log($delete);
                        } catch (Exception $e) {
                        }
                    } else {
                        $message = $repsonses['delmessage']['help'];
                        $default['message'] = $message;
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
}

function delmessage_user($update, $MadelineProto, $msg)
{
    
    $msg_id = $update['update']['message']['id'];
    if (is_supergroup($update, $MadelineProto)) {
        
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode' => 'html'
        );
        if (is_moderated($ch_id)) {
            if (is_bot_admin($update, $MadelineProto, true)) {
                if (from_admin_mod($update, $MadelineProto)) {
                    if ($msg) {
                        $id = catch_id($update, $MadelineProto, $msg);
                        if ($id[0]) {
                            $userid = $id[1];
                            $username = $id[2];
                        } else {
                            $str = $MadelineProto->responses['delmessage_user']['idk'];
                            $repl = array(
                                "msg" => $msg
                            );
                            $message = $MadelineProto->engine->render($str, $repl);
                            $default['message'] = $message;
                        }
                        if (isset($userid)) {
                            $mention = html_mention($username, $userid);
                            if (!is_admin_mod($update, $MadelineProto, $userid)) {
                                try {
                                    $delete = $MadelineProto->channels->deleteUserHistory(
                                        ['channel' => $peer,
                                        'user_id' => $userid]
                                    );
                                    \danog\MadelineProto\Logger::log($delete);
                                    $str = $MadelineProto->responses['delmessage_user']['success'];
                                    $repl = array(
                                        "mention" => $mention
                                    );
                                    $message = $MadelineProto->engine->render($str, $repl);
                                    $default['message'] = $message;
                                } catch (Exception $e) {
                                }
                            } else {
                                $message = $MadelineProto->responses['delmessage_user']['mod'];
                                $default['message'] = $message;
                            }
                        }
                    } else {
                        $message = $reponses['delmessage_user']['help'];
                        $default['message'] = $message;
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
}

function leave_setting($update, $MadelineProto, $msg)
{
    if (is_peeruser($update, $MadelineProto)) {
        
        $userid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
        if (is_master($MadelineProto, $userid)) {
            $msg_id = $update['update']['message']['id'];
            $default = array(
            'peer' => $userid,
            'reply_to_msg_id' => $msg_id,
            'parse_mode' => 'html'
            );
            $arr = ["on", "off"];
            if ($msg) {
                if (in_array($msg, $arr)) {
                    check_json_array('leave.json', false, false);
                    $file = file_get_contents("leave.json");
                    $leave = json_decode($file, true);
                    if ($msg == "on") {
                        if (in_array("on", $leave)) {
                            $message = $MadelineProto->responses['leave_setting']['already_on'];
                        } else {
                            if (isset($leave[0])) {
                                unset($leave[0]);
                            }
                            $leave[0] = "on";
                            $message = $MadelineProto->responses['leave_setting']['on'];
                            $default['message'] = $message;
                        }
                    } else {
                        if (in_array("off", $leave)) {
                            $message = $MadelineProto->responses['leave_setting']['already_off'];
                        } else {
                            if (isset($leave[0])) {
                                unset($leave[0]);
                            }
                            $leave[0] = "off";
                            $message = $MadelineProto->responses['leave_setting']['off'];
                            $default['message'] = $message;
                        }
                    }
                } else {
                    $message = $MadelineProto->responses['leave_setting']['help'];
                    $default['message'] = $message;
                }
            } else {
                $message = $MadelineProto->responses['leave_setting']['help'];
                $default['message'] = $message;
            }
        } else {
            $message = $MadelineProto->responses['leave_setting']['fuck_off'];
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
function pinalert($update, $MadelineProto)
{
    $chat = parse_chat_data($update, $MadelineProto);
    $ch_id = $chat['id'];
    $chatpeer = $chat['peer'];
    $title = htmlentities($chat['title']);
    $msgid = $update['update']['message']['id'];
    $pin_id = $update['update']['message']['reply_to_msg_id'];
    $peer = cache_get_info(
        $update,
        $MadelineProto,
        getenv('MASTER_USERNAME')
    )['bot_api_id'];
    $tg_id = str_replace("-100", "", $ch_id);
    $fromid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
    $username = catch_id($update, $MadelineProto, $fromid)[2];
    $mention = html_mention($username, $fromid);
    $default = array(
        'peer' => $peer,
        'parse_mode' => 'html'
    );
    $message = "User $mention pinned a message in <b>$title</b> - $tg_id";
    $default['message'] = $message;
    $sentMessage = $MadelineProto->messages->sendMessage(
        $default
    );
    \danog\MadelineProto\Logger::log($sentMessage);
    $forwardMessage = $MadelineProto->messages->forwardMessages([
        'silent' => false,
        'from_peer' => $ch_id,
        'id' => [$pin_id],
        'to_peer' => $peer]
    );
    \danog\MadelineProto\Logger::log($forwardMessage);
}
