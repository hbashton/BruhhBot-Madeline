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
function banme($update, $MadelineProto, $msg = "", $send = true)
{
    $uMadelineProto = $MadelineProto->API->uMadelineProto;
    if (bot_present($update, $MadelineProto)) {
        if (is_supergroup($update, $MadelineProto)) {
            $msg_id = $update['update']['message']['id'];
            $mods = $MadelineProto->responses['banme']['mods'];
            $chat = parse_chat_data($update, $MadelineProto);
            $peer = $chat['peer'];
            $title = htmlentities($chat['title']);
            $ch_id = $chat['id'];
            $default = array(
                'peer' => $peer,
                'reply_to_msg_id' => $msg_id,
                'parse_mode' => 'html',
                );
            if (is_moderated($ch_id)) {
                var_dump(1);
                if (is_bot_admin($update, $MadelineProto)) {
                    var_dump(2);
                    if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                        if (!empty($msg) or array_key_exists('reply_to_msg_id', $update['update']['message'])) {
                            $id = catch_id($update, $MadelineProto, $msg);
                            if ($id[0]) {
                                $userid = $id[1];
                            }
                            if (isset($userid)) {
                                $banmod = $MadelineProto->responses['banme']['banmod'];
                                if (!is_admin_mod(
                                    $update,
                                    $MadelineProto,
                                    $userid,
                                    $banmod,
                                    $send
                                )
                                ) {
                                    $username = $id[2];
                                    $mention = html_mention($username, $userid);
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
                                            $str = $MadelineProto->responses['banme']['banned'];
                                            $repl = array(
                                                "mention" => $mention,
                                                "title" => $title
                                            );
                                            $message = $MadelineProto->engine->render($str, $repl);
                                            $default['message'] = $message;
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
                                            $str = $MadelineProto->responses['banme']['already'];
                                            $repl = array(
                                                "mention" => $mention
                                            );
                                            $message = $MadelineProto->engine->render($str, $repl);
                                            $default['message'] = $message;
                                        }
                                    } else {
                                        $banlist[$ch_id] = [];
                                        try {
                                            $kick = $uMadelineProto->
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
                                        $str = $MadelineProto->responses['banme']['banned'];
                                        $repl = array(
                                            "mention" => $mention,
                                            "title" => $title
                                        );
                                        $message = $MadelineProto->engine->render($str, $repl);
                                        $default['message'] = $message;
                                    }
                                }
                            } else {
                                $str = $MadelineProto->responses['banme']['idk'];
                                $repl = array("msg" => $msg);
                                $message = $MadelineProto->engine->render($str, $repl);
                                $default['message'] = $message;
                            }
                        } else {
                            $message = $MadelineProto->responses['banme']['help'];
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
}


function unbanme($update, $MadelineProto, $msg = "")
{
    $uMadelineProto = $MadelineProto->API->uMadelineProto;
    if (bot_present($update, $MadelineProto)) {
        if (is_supergroup($update, $MadelineProto)) {
            $msg_id = $update['update']['message']['id'];
            $mods = $MadelineProto->responses['unbanme']['mods'];
            $chat = parse_chat_data($update, $MadelineProto);
            $peer = $chat['peer'];
            $title = html_bold($chat['title']);
            $ch_id = $chat['id'];
            $default = array(
                'peer' => $peer,
                'reply_to_msg_id' => $msg_id,
                'parse_mode' => 'html',
            );
            if (is_moderated($ch_id)) {
                if (is_bot_admin($update, $MadelineProto)) {
                    if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                        if (!empty($msg) or array_key_exists('reply_to_msg_id', $update['update']['message'])) {
                            $id = catch_id($update, $MadelineProto, $msg);
                            if ($id[0]) {
                                $userid = $id[1];
                            }
                            if (isset($userid)) {
                                $username = $id[2];
                                $mention = html_mention($username, $userid);
                                check_json_array('banlist.json', $ch_id);
                                $file = file_get_contents("banlist.json");
                                $banlist = json_decode($file, true);
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
                                        $str = $MadelineProto->responses['unbanme']['unbanned'];
                                        $repl = array(
                                            "mention" => $mention,
                                            "title" => $title
                                        );
                                        $message = $MadelineProto->engine->render($str, $repl);
                                        $default['message'] = $message;
                                        try {
                                            $kick = $uMadelineProto->
                                            channels->kickFromChannel(
                                                ['channel' => $peer,
                                                'user_id' => $userid,
                                                'kicked' => false]
                                            );
                                        } catch (\danog\MadelineProto\RPCErrorException $e) {
                                        }
                                    } else {
                                        $str = $MadelineProto->responses['unbanme']['already'];
                                        $repl = array(
                                            "mention" => $mention
                                        );
                                        $message = $MadelineProto->engine->render($str, $repl);
                                        $default['message'] = $message;
                                    }
                                } else {
                                    $str = $MadelineProto->responses['unbanme']['already'];
                                    $repl = array(
                                        "mention" => $mention
                                    );
                                    $message = $MadelineProto->engine->render($str, $repl);
                                    $default['message'] = $message;
                                }
                            } else {
                                $str = $MadelineProto->responses['unbanme']['idk'];
                                $repl = array("msg" => $msg);
                                $message = $MadelineProto->engine->render($str, $repl);
                                $default['message'] = $message;
                            }
                        } else {
                            $message = $MadelineProto->responses['unbanme']['help'];
                            $default['message'] = $message;
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
}


function kickhim($update, $MadelineProto, $msg = "")
{
    $uMadelineProto = $MadelineProto->API->uMadelineProto;
    if (bot_present($update, $MadelineProto)) {
        if (is_supergroup($update, $MadelineProto)) {
            $msg_id = $update['update']['message']['id'];
            $mods = $MadelineProto->responses['kickhim']['mods'];
            $chat = parse_chat_data($update, $MadelineProto);
            $peer = $chat['peer'];
            $title = htmlentities($chat['title']);
            $ch_id = $chat['id'];
            $default = array(
                'peer' => $peer,
                'reply_to_msg_id' => $msg_id,
                'parse_mode' => 'html',
            );
            if (is_moderated($ch_id)) {
                if (is_bot_admin($update, $MadelineProto)) {
                    if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                        if (!empty($msg) or array_key_exists('reply_to_msg_id', $update['update']['message'])) {
                            $id = catch_id($update, $MadelineProto, $msg);
                            if ($id[0]) {
                                $userid = $id[1];
                            }
                            if (isset($userid)) {
                                $kickmod = $MadelineProto->responses['kickhim']['kickmod'];
                                if (!is_admin_mod(
                                    $update,
                                    $MadelineProto,
                                    $userid,
                                    $kickmod,
                                    true
                                )
                                ) {
                                    $username = $id[2];
                                    $mention = html_mention($username, $userid);
                                    try {
                                        $kick = $uMadelineProto->
                                        channels->kickFromChannel(
                                            ['channel' => $peer,
                                            'user_id' => $userid,
                                            'kicked' => true]
                                        );
                                        $kickback = $uMadelineProto->
                                        channels->kickFromChannel(
                                            ['channel' => $peer,
                                            'user_id' => $userid,
                                            'kicked' => false]
                                        );
                                        $str = $MadelineProto->responses['kickhim']['kicked'];
                                        $repl = array(
                                            "mention" => $mention,
                                            "title" => $title
                                        );
                                        $message = $MadelineProto->engine->render($str, $repl);
                                        $default['message'] = $message;
                                    } catch (\danog\MadelineProto\RPCErrorException $e) {
                                        $str = $MadelineProto->responses['kickhim']['already'];
                                        $repl = array(
                                            "mention" => $mention
                                        );
                                        $message = $MadelineProto->engine->render($str, $repl);
                                        $default['message'] = $message;
                                    }

                                }
                            } else {
                                $str = $MadelineProto->responses['kickhim']['idk'];
                                $repl = array(
                                    "msg" => $msg
                                );
                                $message = $MadelineProto->engine->render($str, $repl);
                                $default['message'] = $message;
                            }
                        } else {
                            $message = $MadelineProto->responses['kickhim']['help'];
                            $default['message'] = $message;
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
}

function kickme($update, $MadelineProto)
{
    $uMadelineProto = $MadelineProto->API->uMadelineProto;
    if (bot_present($update, $MadelineProto)) {
        if (is_supergroup($update, $MadelineProto)) {
            $msg_id = $update['update']['message']['id'];
            $chat = parse_chat_data($update, $MadelineProto);
            $peer = $chat['peer'];
            $ch_id = $chat['id'];
            $title = htmlentities($chat['title']);
            $default = array(
                'peer' => $peer,
                'reply_to_msg_id' => $msg_id,
                'parse_mode' => 'html',
            );
            $userid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
            if (is_moderated($ch_id)) {
                if (is_bot_admin($update, $MadelineProto)) {
                    if (!from_admin_mod($update, $MadelineProto)) {
                        $id = catch_id($update, $MadelineProto, $userid);
                        $username = $id[2];
                        $mention = html_mention($username, $userid);
                        try {
                            $kick = $uMadelineProto->channels->kickFromChannel(
                                ['channel' => $peer,
                                'user_id' => $userid,
                                'kicked' => true]
                            );
                            $kickback = $uMadelineProto->
                            channels->kickFromChannel(
                                ['channel' => $peer,
                                'user_id' => $userid,
                                'kicked' => false]
                            );
                            $str = $MadelineProto->responses['kickme']['kicked'];
                            $repl = array(
                                "mention" => $mention,
                                "title" => $title
                            );
                            $message = $MadelineProto->engine->render($str, $repl);
                            $default['message'] = $message;
                        } catch (\danog\MadelineProto\RPCErrorException $e) {
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
                \danog\MadelineProto\Logger::log($kickback);
            }
            if (isset($sentMessage)) {
                \danog\MadelineProto\Logger::log($sentMessage);
            }
        }
    }
}

function getbanlist($update, $MadelineProto)
{
    $uMadelineProto = $MadelineProto->API->uMadelineProto;
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
                'parse_mode' => 'html',
            );
            if (is_moderated($ch_id)) {
                check_json_array('banlist.json', $ch_id);
                $file = file_get_contents("banlist.json");
                $banlist = json_decode($file, true);
                if (array_key_exists($ch_id, $banlist)) {
                    foreach ($banlist[$ch_id] as $i => $key) {
                        $id = catch_id($update, $MadelineProto, $key);
                        if ($id[0]) {
                            $username = $id[2];
                            $mention = $username;
                            if (!isset($message)) {
                                $str = $MadelineProto->responses['getbanlist']['header'];
                                $repl = array(
                                    "title" => $title
                                );
                                $message = $MadelineProto->engine->render($str, $repl);
                                $message = $message."[x] $mention - $key\r\n";
                            } else {
                                $message = $message."[x] $mention - $key\r\n";
                            }
                        }
                    }
                }
                if (!isset($message)) {
                    $str = $MadelineProto->responses['getbanlist']['none'];
                    $repl = array(
                        "title" => $title
                    );
                    $message = $MadelineProto->engine->render($str, $repl);
                    $default['message'] = $message;
                    $sentMessage = $MadelineProto->messages->sendMessage(
                        $default
                    );
                }
                if (!isset($sentMessage)) {
                    $default['message'] = $message;
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

function unbanall($update, $MadelineProto, $msg = "")
{
    $uMadelineProto = $MadelineProto->API->uMadelineProto;
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
                'parse_mode' => 'html',
            );
            if (is_moderated($ch_id)) {
                if (is_bot_admin($update, $MadelineProto)) {
                    if (from_master($update, $MadelineProto)) {
                        if (!empty($msg) or array_key_exists('reply_to_msg_id', $update['update']['message'])) {
                            $id = catch_id($update, $MadelineProto, $msg);
                            if ($id[0]) {
                                $userid = $id[1];
                            }
                            if (isset($userid)) {
                                $username = $id[2];
                                $mention = html_mention($username, $userid);
                                check_json_array('gbanlist.json', false, false);
                                $file = file_get_contents("gbanlist.json");
                                $gbanlist = json_decode($file, true);
                                if (array_key_exists($userid, $gbanlist)) {
                                    unset($gbanlist[$userid]);
                                    file_put_contents(
                                        'gbanlist.json',
                                        json_encode($gbanlist)
                                    );
                                    $str = $MadelineProto->responses['unbanall']['unbanned'];
                                    $repl = array(
                                        "mention" => $mention
                                    );
                                    $message = $MadelineProto->engine->render($str, $repl);
                                    $default['message'] = $message;
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
                                    $str = $MadelineProto->responses['unbanall']['already'];
                                    $repl = array(
                                        "mention" => $mention
                                    );
                                    $message = $MadelineProto->engine->render($str, $repl);
                                    $default['message'] = $message;
                                }
                            } else {
                                $str = $MadelineProto->responses['unbanall']['idk'];
                                $repl = array(
                                    "msg" => $msg
                                );
                                $message = $MadelineProto->engine->render($str, $repl);
                                $default['message'] = $message;
                            }
                        } else {
                            $message = $MadelineProto->responses['unbanall']['help'];
                            $default['message'] = $message;
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
            if (isset($userid)) {
                unban_from_moderated($MadelineProto, $userid, [$ch_id]);
            }
        }
    }
}

function banall($update, $MadelineProto, $msg = "", $reason = "", $send = true, $confident = false)
{
    $uMadelineProto = $MadelineProto->API->uMadelineProto;
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
            $fromid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
            if (is_moderated($ch_id)) {
                if (is_bot_admin($update, $MadelineProto)) {
                    if (from_master($update, $MadelineProto)) {
                        if (!empty($msg) or array_key_exists('reply_to_msg_id', $update['update']['message'])) {
                            $id = catch_id($update, $MadelineProto, $msg);
                            if ($id[0]) {
                                $userid = $id[1];
                            }
                            if (isset($userid)) {
                                $banmod = $MadelineProto->responses['banall']['banmod'];
                                if (!is_admin_mod(
                                    $update,
                                    $MadelineProto,
                                    $userid,
                                    $banmod,
                                    $send
                                )
                                ) {
                                    $username = $id[2];
                                    $fromuser = catch_id($update, $MadelineProto, $fromid)[2];
                                    $mention = html_mention($username, $userid);
                                    $mention2 = html_mention($fromuser, $fromid);
                                    check_json_array('gbanlist.json', false, false);
                                    $file = file_get_contents("gbanlist.json");
                                    $gbanlist = json_decode($file, true);
                                    check_json_array('reasons.json', false, false);
                                    $file = file_get_contents("reasons.json");
                                    $reasons = json_decode($file, true);
                                    if (!array_key_exists($userid, $gbanlist)) {
                                        $gbanlist[$userid] = $username;
                                        file_put_contents(
                                            'gbanlist.json',
                                            json_encode($gbanlist)
                                        );
                                        if ($reason) {
                                            if (preg_match('/"([^"]+)"/', $reason, $m)) {
                                                $reasons[$userid] = $m[1];
                                                $str = $MadelineProto->responses['banall']['banned_all'];
                                                $repl = array(
                                                    "mention2" => $mention2,
                                                    "mention" => $mention,
                                                    "reason" => $m[1]
                                                );
                                                $message = $MadelineProto->engine->render($str, $repl);
                                                $default['message'] = $message;
                                                file_put_contents(
                                                    'reasons.json',
                                                    json_encode($reasons)
                                                );
                                            } else {
                                                $message = $MadelineProto->responses['banall']['help'];
                                                $default['message'] = $message;
                                            }
                                        } else {
                                            $str = $MadelineProto->responses['banall']['banned'];
                                            $repl = array(
                                                "mention" => $mention
                                            );
                                            $message = $MadelineProto->engine->render($str, $repl);
                                            $default['message'] = $message;
                                        }
                                        try {
                                            $kick = $uMadelineProto->
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
                                        $str = $MadelineProto->responses['banall']['already'];
                                        $repl = array(
                                            "mention" => $mention
                                        );
                                        $message = $MadelineProto->engine->render($str, $repl);
                                        $default['message'] = $message;
                                        $all = false;
                                    }
                                } else {
                                    return;
                                }
                            } else {
                                $str = $MadelineProto->responses['banall']['idk'];
                                $repl = array(
                                    "msg" => $msg
                                );
                                $message = $MadelineProto->engine->render($str, $repl);
                                $default['message'] = $message;
                                $all = false;
                            }
                        } else {
                            $message = $MadelineProto->responses['banall']['help'];
                            $default['message'] = $message;
                            $all = false;
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
            if (!isset($all)) {
                $all = true;
            }
            if (!isset($MadelineProto->timestamp)) {
                $MadelineProto->timestamp = [];
            }
            if (!isset($MadelineProto->timestamp['banall'])) {
                $MadelineProto->timestamp['banall'] = time();
            }
            $diff = time() - $MadelineProto->timestamp['banall'];
            if ($diff > 1800) {
                if ($send && $all && isset($message)) {
                    send_to_moderated($MadelineProto, $message, [$ch_id]);
                }
                if (isset($userid)) {
                    ban_from_moderated($MadelineProto, $userid, [$ch_id]);
                }
                if ($confident) {
                    ban_from_moderated($MadelineProto, $msg, [$ch_id]);
                }
            } else {
                if ($msg != "" && isset($userid)) {
                    $timetowait = 1800 - $diff;
                    $msg_id = $update['update']['message']['id'];
                    $chat = parse_chat_data($update, $MadelineProto);
                    $peer = $chat['peer'];
                    $ch_id = $chat['id'];
                    $default = array(
                        'peer' => $peer,
                        'reply_to_msg_id' => $msg_id,
                        'message' => "Please wait $timetowait seconds before using !banall. The user has been added to the gbanlist"
                    );
                    $sentMessage = $MadelineProto->messages->sendMessage(
                        $default
                    );
                    \danog\MadelineProto\Logger::log($sentMessage);
                }
            }
        }
    }
}

function getgbanlist($update, $MadelineProto)
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
                check_json_array('gbanlist.json', false, false);
                $file = file_get_contents("gbanlist.json");
                $gbanlist = json_decode($file, true);
                check_json_array('reasons.json', false, false);
                $file = file_get_contents("reasons.json");
                $reasons = json_decode($file, true);
                foreach ($gbanlist as $i => $key) {
                    $id = $i;
                    $username = $key;
                    $mention = $username;
                    if (!isset($message)) {
                        $str = $MadelineProto->responses['getgbanlist']['header'];
                        $repl = array(
                            "title" => $title
                        );
                        $message = $MadelineProto->engine->render($str, $repl);
                        if (array_key_exists($id, $reasons)) {
                            $reason = $reasons[$id];
                            $message = $message."[x] $mention - $id\n<code>Reason: ".htmlentities($reason)."</code>\n";
                        } else {
                            $message = $message."[x] $mention - $id\n";
                        }
                    } else {
                        if (array_key_exists($id, $reasons)) {
                            $reason = $reasons[$id];
                            $message = $message."[x] $mention - $id\n<code>Reason: ".htmlentities($reason)."</code>\n";
                        } else {
                            $message = $message."[x] $mention - $id\n";
                        }
                    }
                }
                if (!isset($message)) {
                    $message = $MadelineProto->responses['getgbanlist']['none'];
                    $default['message'] = $message;
                    $sentMessage = $MadelineProto->messages->sendMessage(
                        $default
                    );
                } else {
                    if (mb_strlen($message) > 4000) {
                        $message = split_to_chunks($message);
                    }
                }
                $default['message'] = $message;
                if (is_array($message)) {
                    foreach ($message as $value) {
                        $default['message'] = $value;
                        $sentMessage = $MadelineProto->messages->sendMessage(
                            $default
                        );
                        \danog\MadelineProto\Logger::log($sentMessage);
                    }
                } else {
                    if (!isset($sentMessage)) {
                        $default['message'] = $message;
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
