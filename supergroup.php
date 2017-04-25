<?php
/**
    Copyright (C) 2016-2017 Hunter Ashton

    This file is part of BruhhBot, the most vulnerable bot on Telegram.

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
function addadmin($update, $MadelineProto, $msg = "")
{
    if (bot_present($update, $MadelineProto)) {
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
                        if (!empty($msg) or array_key_exists('reply_to_msg_id', $update['update']['message'])) {
                            $uMadelineProto = $MadelineProto->API->uMadelineProto;
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
                                $channelRoleEditor = [
                                    '_' => 'channelRoleEditor',
                                ];
                                    $editadmin = $uMadelineProto->channels->editAdmin(
                                        ['channel' => $peer, 'user_id' => $userid,
                                        'role' => $channelRoleEditor ]
                                    );
                                    $str = $MadelineProto->responses['addadmin']['success'];
                                    $repl = array(
                                        "mention" => $mention,
                                        "title" => $title
                                    );
                                    $message = $MadelineProto->engine->render($str, $repl);
                                    $default['message'] = $message;
                                    \danog\MadelineProto\Logger::log($editadmin);
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
    }
}
function rmadmin($update, $MadelineProto, $msg = "")
{
    if (bot_present($update, $MadelineProto)) {
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
                        if (!empty($msg) or array_key_exists('reply_to_msg_id', $update['update']['message'])) {
                            $uMadelineProto = $MadelineProto->API->uMadelineProto;
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
                                    $editadmin = $uMadelineProto->channels->editAdmin(
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
                    }
                    if (isset($sentMessage)) {
                        \danog\MadelineProto\Logger::log($sentMessage);
                    }
                }
            }
        }
    }
}
function idme($update, $MadelineProto, $msg = "")
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
        if (bot_present($update, $MadelineProto)) {
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
    }
    $msg_id = $update['update']['message']['id'];
    if (isset($cont)) {
        $default = array(
        'peer' => $peer,
        'reply_to_msg_id' => $msg_id,
        'parse_mode' => 'html'
        );
        if (!empty($msg) or array_key_exists('reply_to_msg_id', $update['update']['message'])) {
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
    if (bot_present($update, $MadelineProto)) {
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
                        or $key['role'] == "editor"
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
}

function modlist($update, $MadelineProto)
{
    if (bot_present($update, $MadelineProto)) {
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
            if (is_moderated($ch_id)) {
                check_json_array('promoted.json', $ch_id);
                $file = file_get_contents("promoted.json");
                $promoted = json_decode($file, true);
                if (isset($promoted[$ch_id])) {
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
}

function pinmessage($update, $MadelineProto, $silent, $user = false)
{
    if (bot_present($update, $MadelineProto)) {
        if (is_supergroup($update, $MadelineProto)) {
            $msg_id = $update['update']['message']['id'];
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
                            if (!$user) {
                            $uMadelineProto = $MadelineProto->API->uMadelineProto;
                            }
                            try {
                                $pin_id = $update['update']['message']['reply_to_msg_id'];
                                if (!$user) {
                                    $pin = $uMadelineProto->
                                    channels->updatePinnedMessage(
                                        ['silent' => $silent,
                                        'channel' => $peer,
                                        'id' => $pin_id ]
                                    );
                                } else {
                                    $pin = $MadelineProto->
                                    channels->updatePinnedMessage(
                                        ['silent' => $silent,
                                        'channel' => $peer,
                                        'id' => $pin_id ]
                                    );
                                }
                                $message = $MadelineProto->responses['pinmessage']['success'];
                                $default['message'] = $message;
                                \danog\MadelineProto\Logger::log($pin);
                                $message2 = "User $mention pinned a message in <b>$title</b> - $tg_id";
                                if (!$user) {
                                    alert_moderators($MadelineProto, $ch_id, $message2);
                                    alert_moderators_forward($MadelineProto, $ch_id, $pin_id);
                                }
                            } catch (Exception $e) {}
                        } else {
                            $message = $MadelineProto->responses['pinmessage']['help'];
                            $default['message'] = $message;
                        }
                    }
                    if (isset($default['message']) && !$user) {
                        $sentMessage = $MadelineProto->messages->sendMessage(
                            $default
                        );
                        \danog\MadelineProto\Logger::log($sentMessage);
                    }
                }
            }
        }
    }
}

function delmessage($update, $MadelineProto)
{
    if (bot_present($update, $MadelineProto)) {
        if (is_supergroup($update, $MadelineProto)) {
            $msg_id = $update['update']['message']['id'];
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
                            $uMadelineProto = $MadelineProto->API->uMadelineProto;
                            try {
                                $del_id = $update['update']['message']['reply_to_msg_id'];
                                $delete = $uMadelineProto->channels->deleteMessages(
                                    ['channel' => $peer,
                                    'id' => [$del_id,$msg_id]]
                                );
                                \danog\MadelineProto\Logger::log($delete);
                            } catch (Exception $e) {}
                        } else {
                            $message = $MadelineProto->responses['delmessage']['help'];
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
}

function delmessage_user($update, $MadelineProto, $msg = "")
{
    if (bot_present($update, $MadelineProto)) {
        if (is_supergroup($update, $MadelineProto)) {
            $msg_id = $update['update']['message']['id'];
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
                            $uMadelineProto = $MadelineProto->API->uMadelineProto;
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
                                        $delete = $uMadelineProto->channels->deleteUserHistory(
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
                            $message = $MadelineProto->responses['delmessage_user']['help'];
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
}

function purgemessage($update, $MadelineProto)
{
    if (bot_present($update, $MadelineProto)) {
        if (is_supergroup($update, $MadelineProto)) {
            $msg_id = $update['update']['message']['id'];
            $chat = parse_chat_data($update, $MadelineProto);
            $peer = $chat['peer'];
            $ch_id = $chat['id'];
            $default = array(
                'peer' => $peer,
                'reply_to_msg_id' => $msg_id,
                'parse_mode' => 'html'
            );
            $mods = $MadelineProto->responses['purgemessage']['mods'];

            if (is_moderated($ch_id)) {
                if (is_bot_admin($update, $MadelineProto, true)) {
                    if (from_admin_mod($update, $MadelineProto)) {
                        if (array_key_exists(
                            "reply_to_msg_id",
                            $update['update']['message']
                        )
                        ) {
                            $uMadelineProto = $MadelineProto->API->uMadelineProto;
                            try {
                                $del_id = $update['update']['message']['reply_to_msg_id'];
                                $default['message'] = "Deleting all messages after $del_id..";
                                $sentMessage = $MadelineProto->messages->sendMessage(
                                    $default
                                );
                                \danog\MadelineProto\Logger::log($sentMessage);
                                foreach ($sentMessage['updates'] as $messageObject) {
                                    if (!array_key_exists('_', $messageObject)) return;
                                    if ($messageObject["_"] == "updateMessageID") {
                                        $newMessageID = $messageObject['id'];
                                        break;
                                    }
                                }
                                $delete = $uMadelineProto->channels->deleteMessages(
                                    ['channel' => $peer,
                                    'id' => range($del_id, $newMessageID)]
                                );
                                \danog\MadelineProto\Logger::log($delete);
                                unset($default['message']);
                            } catch (Exception $e) {}
                        } else {
                            $message = $MadelineProto->responses['purgemessage']['help'];
                            $default['message'] = $message;
                        }
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
    try {
        $chat = parse_chat_data($update, $MadelineProto);
        $ch_id = $chat['id'];
        if (!is_moderated($ch_id)) return;
        $chatpeer = $chat['peer'];
        $title = htmlentities($chat['title']);
        $msgid = $update['update']['message']['id'];
        $pin_id = $update['update']['message']['reply_to_msg_id'];
        $tg_id = str_replace("-100", "", $ch_id);
        $fromid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
        if ($fromid == $MadelineProto->get_info(getenv('BOT_USERNAME'))['bot_api_id']) return;
        $username = catch_id($update, $MadelineProto, $fromid)[2];
        $mention = html_mention($username, $fromid);
        $message = "User $mention pinned a message in <b>$title</b> - $tg_id";
        alert_moderators($MadelineProto, $ch_id, $message);
        alert_moderators_forward($MadelineProto, $ch_id, $pin_id);
    } catch (Exception $e) {}
}

function get_chat_rules($update, $MadelineProto)
{
    $uMadelineProto = $MadelineProto->API->uMadelineProto;
    if (bot_present($update, $MadelineProto)) {
        if (is_supergroup($update, $MadelineProto)) {
            $msg_id = $update['update']['message']['id'];
            $chat = parse_chat_data($update, $MadelineProto);
            $peer = $chat['peer'];
            $fromid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
            $title = $chat['title'];
            $ch_id = $chat['id'];
            $default = array(
                'peer' => $fromid,
                'reply_to_msg_id' => $msg_id,
                'parse_mode' => 'html'
                );
            if (is_moderated($ch_id)) {
                check_json_array("settings.json", $ch_id);
                $file = file_get_contents("settings.json");
                $settings = json_decode($file, true);
                if (!isset($settings[$ch_id]["rules"])) {
                    $settings[$ch_id]["rules"] = "";
                }
                if ($settings[$ch_id]["rules"] != "") {
                    $default['message'] = "Rules for $title:\n".$settings[$ch_id]["rules"];
                    $bold = create_style('bold', 10, $title);
                } else {
                    $default['message'] = "There are no rules for $title";
                    $bold = create_style('bold', 23, $title);
                }
                $default['entities'] = $bold;
            }
            if (isset($default['message'])) {
                try {
                    $sentMessage = $MadelineProto->messages->sendMessage(
                        $default
                    );
                } catch (Exception $e) {
                    var_dump($e->getMessage());
                    if ($e->getMessage() == "USER_IS_BLOCKED" or $e->getMessage() == "PEER_ID_INVALID") {
                        if (isset($default['entities'])) unset($default['entities']);
                        $default['peer'] = $peer;
                        $botusername = preg_replace("/@/", "",getenv("BOT_API_USERNAME"));
                        $url = "https://telegram.me/$botusername?start=rules-$ch_id";
                        $keyboardButtonUrl = ['_' => 'keyboardButtonUrl', 'text' => "Get the rules!", 'url' => $url, ];
                        $buttons = [$keyboardButtonUrl];
                        $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons ];
                        $rows = [$row];
                        $replyInlineMarkup = ['_' => 'replyInlineMarkup', 'rows' => $rows, ];
                        $default['reply_markup'] = $replyInlineMarkup;
                        $default['message'] = "Please start a chat with me so I can send you the rules for $title";
                        $sentMessage = $MadelineProto->messages->sendMessage(
                                $default
                            );
                    } else {
                        $default['message'] = "Rules HTML formatted incorrectly";
                        $default['peer'] = $ch_id;
                        $sentMessage = $MadelineProto->messages->sendMessage(
                                $default
                            );
                        \danog\MadelineProto\Logger::log($sentMessage);
                    }
                }
            }
        }
    }
}

function get_chat_rules_deeplink($update, $MadelineProto, $ch_id)
{
    $uMadelineProto = $MadelineProto->API->uMadelineProto;
    $msg_id = $update['update']['message']['id'];
    $chat = cache_get_info($update, $MadelineProto, $ch_id, true);
    $fromid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
    $title = $chat['title'];
    $default = array(
        'peer' => $fromid,
        'parse_mode' => 'html'
        );
    check_json_array("settings.json", $ch_id);
    $file = file_get_contents("settings.json");
    $settings = json_decode($file, true);
    if (!isset($settings[$ch_id]["rules"])) {
        $settings[$ch_id]["rules"] = "";
    }
    if ($settings[$ch_id]["rules"] != "") {
        $default['message'] = "Rules for $title:\n".$settings[$ch_id]["rules"];
        $bold = create_style('bold', 10, $title);
    } else {
        $default['message'] = "There are no rules for $title";
        $bold = create_style('bold', 23, $title);
    }
    $default['entities'] = $bold;
    if (isset($default['message'])) {
        try {
            $sentMessage = $MadelineProto->messages->sendMessage(
                $default
            );
        } catch (Exception $e) {
            try {
                $default['message'] = fixtags($default['message']);
                $sentMessage = $MadelineProto->messages->sendMessage(
                    $default
                );
                $settings[$ch_id]['rules'] = fixtags($default['message']);
            } catch (Exception $e) {
                $default['message'] = "Rules HTML formatted incorrectly.";
                $sentMessage = $MadelineProto->messages->sendMessage(
                    $default
                );
            }
        }
    }
}

function alert_moderators($MadelineProto, $ch_id, $text)
{
    $default = array(
        'message' => $text,
        'parse_mode' => 'html'
    );
    $users = [];
    $admins = cache_get_info(false, $MadelineProto, $ch_id, true);
    foreach ($admins['participants'] as $key) {
        if (array_key_exists('user', $key)) {
            $id = $key['user']['id'];
        } else {
            if (array_key_exists('bot', $key)) {
                $id = $key['bot']['id'];
            }
        }
        if (array_key_exists("role", $key)) {
            if ($key['role'] == "moderator"
                or $key['role'] == "creator"
                or $key['role'] == "editor"
            ) {
                $mod = true;
            } else {
                $mod = false;
            }
        } else {
            $mod = false;
        }
        if ($mod) {
            $users[] = $id;
        }
    }
    check_json_array('promoted.json', $ch_id);
    $file = file_get_contents("promoted.json");
    $promoted = json_decode($file, true);
    if (isset($promoted[$ch_id])) {
        foreach ($promoted[$ch_id] as $id) {
            if (in_array($id, $users)) continue;
            $users[] = $id;
        }
    }
    foreach ($users as $peer) {
        try {
            if (!alert_check($ch_id, $peer)) continue;
            $default['peer'] = $peer;
            $sentMessage = $MadelineProto->messages->sendMessage(
                    $default
                );
            \danog\MadelineProto\Logger::log($sentMessage);
        } catch (Exception $e) {
            continue;
        }
    }
}

function alert_moderators_forward($MadelineProto, $ch_id, $msg_id)
{
    $users = [];
    $admins = cache_get_info(false, $MadelineProto, $ch_id, true);
    foreach ($admins['participants'] as $key) {
        if (array_key_exists('user', $key)) {
            $id = $key['user']['id'];
        } else {
            if (array_key_exists('bot', $key)) {
                $id = $key['bot']['id'];
            }
        }
        if (array_key_exists("role", $key)) {
            if ($key['role'] == "moderator"
                or $key['role'] == "creator"
                or $key['role'] == "editor"
            ) {
                $mod = true;
            } else {
                $mod = false;
            }
        } else {
            $mod = false;
        }
        if ($mod) {
            $users[] = $id;
        }
    }
    check_json_array('promoted.json', $ch_id);
    $file = file_get_contents("promoted.json");
    $promoted = json_decode($file, true);
    if (isset($promoted[$ch_id])) {
        foreach ($promoted[$ch_id] as $id) {
            if (in_array($id, $users)) continue;
            $users[] = $id;
        }
    }
    foreach ($users as $peer) {
        try {
            if (alert_check($ch_id, $peer)) {
                $forwardMessage = $MadelineProto->messages->forwardMessages([
                    'silent' => false,
                    'from_peer' => $ch_id,
                    'id' => [$msg_id],
                    'to_peer' => $peer]
                );
                \danog\MadelineProto\Logger::log($forwardMessage);
            }
        } catch (Exception $e) {
            continue;
        }
    }
}
