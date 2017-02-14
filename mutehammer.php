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
        global $responses, $engine;
        $msg_id = $update['update']['message']['id'];
        $mods = $responses['muteme']['mods'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode' => 'html'
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
                            $mutemod = $responses['muteme']['mutemod'];
                            if (!is_admin_mod(
                                $update,
                                $MadelineProto,
                                $userid,
                                $mutemod,
                                true
                            )
                            ) {
                                if (!isset($userid)) {
                                    $str = $responses['muteme']['idk'];
                                    $repl = array(
                                        "msg" => $msg
                                    );
                                    $message = $engine->render($str, $repl);
                                    $default['message'] = $message;
                                } else {
                                    $username = $id[2];
                                    $mention = html_mention($username, $userid);
                                    check_json_array('mutelist.json', $ch_id);
                                    $file = file_get_contents("mutelist.json");
                                    $mutelist = json_decode($file, true);
                                    if (array_key_exists($ch_id, $mutelist)) {
                                        if (!in_array($userid, $mutelist[$ch_id])) {
                                            array_push($mutelist[$ch_id], $userid);
                                            file_put_contents(
                                                'mutelist.json',
                                                json_encode($mutelist)
                                            );
                                            $str = $responses['muteme']['success'];
                                            $repl = array(
                                                "mention" => $mention
                                            );
                                            $message = $engine->render($str, $repl);
                                            $default['message'] = $message;
                                        } else {
                                            $str = $responses['muteme']['already'];
                                            $repl = array(
                                                "mention" => $mention
                                            );
                                            $message = $engine->render($str, $repl);
                                            $default['message'] = $message;

                                        }
                                    } else {
                                        $mutelist[$ch_id] = [];
                                        array_push($mutelist[$ch_id], $userid);
                                        file_put_contents(
                                            'mutelist.json',
                                            json_encode($mutelist)
                                        );
                                        $str = $responses['muteme']['success'];
                                        $repl = array(
                                            "mention" => $mention
                                        );
                                        $message = $engine->render($str, $repl);
                                        $default['message'] = $message;
                                    }
                                }
                            }
                        }
                    } else {
                        $message = $responses['muteme']['help'];
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
        global $responses, $engine;
        $mods = $responses['unmuteme']['mods'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode' => 'html'
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
                            $mention = html_mention($username, $userid);
                            check_json_array('mutelist.json', $ch_id);
                            $file = file_get_contents("mutelist.json");
                            $mutelist = json_decode($file, true);
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
                                    $str = $responses['unmuteme']['success'];
                                    $repl = array(
                                        "mention" => $mention
                                    );
                                    $message = $engine->render($str, $repl);
                                    $default['message'] = $message;
                                } else {
                                    $str = $responses['unmuteme']['already'];
                                    $repl = array(
                                        "mention" => $mention
                                    );
                                    $message = $engine->render($str, $repl);
                                    $default['message'] = $message;
                                }
                            } else {
                                $str = $responses['unmuteme']['already'];
                                $repl = array(
                                    "mention" => $mention
                                );
                                $message = $engine->render($str, $repl);
                                $default['message'] = $message;
                            }
                        }
                    } else {
                        $message = $responses['unmuteme']['help'];
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
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}

function muteall($update, $MadelineProto, $send = true)
{
    if (is_supergroup($update, $MadelineProto)) {
        global $responses, $engine;
        $msg_id = $update['update']['message']['id'];
        $mods = $responses['muteall']['mods'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode' => 'html'
            );
        $userid = "all";
        if (is_moderated($ch_id)) {
            if (is_bot_admin($update, $MadelineProto)) {
                if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                    check_json_array('mutelist.json', $ch_id);
                    $file = file_get_contents("mutelist.json");
                    $mutelist = json_decode($file, true);
                    if (array_key_exists($ch_id, $mutelist)) {
                        if (!in_array($userid, $mutelist[$ch_id])) {
                            array_push($mutelist[$ch_id], $userid);
                            file_put_contents(
                                'mutelist.json',
                                 json_encode($mutelist)
                            );
                            $message = $responses['muteall']['success'];
                            $default['message'] = $message;
                        } else {
                            $message = $responses['muteall']['already'];
                            $default['message'] = $message;
                        }
                    } else {
                        $mutelist[$ch_id] = [];
                        array_push($mutelist[$ch_id], $userid);
                        file_put_contents(
                            'mutelist.json',
                             json_encode($mutelist)
                        );
                        $message = $responses['muteall']['success'];
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

function unmuteall($update, $MadelineProto)
{
    $msg_id = $update['update']['message']['id'];
    if (is_supergroup($update, $MadelineProto)) {
        global $responses, $engine;
        $mods = $responses['unmuteall']['mods'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $userid = "all";
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode' => 'html'
        );
        if (is_moderated($ch_id)) {
            if (is_bot_admin($update, $MadelineProto)) {
                if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                    check_json_array('mutelist.json', $ch_id);
                    $file = file_get_contents("mutelist.json");
                    $mutelist = json_decode($file, true);
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
                            $style[] = $responses['unmuteall']['success'];
                            $default['message'] = $message;
                        } else {
                            $message = $responses['unmuteall']['already'];
                            $default['message'] = $message;
                        }
                    } else {
                        $message = $responses['unmuteall']['already'];
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
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}


function getmutelist($update, $MadelineProto)
{
    $msg_id = $update['update']['message']['id'];
    if (is_supergroup($update, $MadelineProto)) {
        global $responses, $engine;
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode' => 'html'
        );
        var_dump($responses);
        if (is_moderated($ch_id)) {
            check_json_array('mutelist.json', $ch_id);
            $file = file_get_contents("mutelist.json");
            $mutelist = json_decode($file, true);
            if (array_key_exists($ch_id, $mutelist)) {
                if (!in_array('all', $mutelist[$ch_id])) {
                    foreach ($mutelist[$ch_id] as $i => $key) {
                        $username = catch_id($update, $MadelineProto, $key)[2];
                        $mention = html_mention($username, $key);
                        if (!isset($message)) {
                            $str = $responses['getmutelist']['header'];
                            $repl = array(
                                "title" => $title
                            );
                            $message = $engine->render($str, $repl);
                            $message = $message."$mention - $key\r\n";
                        } else {
                            $message = $message."$mention - $key\r\n";
                        }
                    }
                    $default['message'] = $message;
                } else {
                    $message = $responses['getmutelist']['dictatorship'];
                    $default['message'] = $message;
                    $sentMessage = $MadelineProto->messages->sendMessage(
                        $default
                    );
                }
            }
            if (!isset($message)) {
                $str = $responses['getmutelist']['none'];
                $repl = array(
                    "title" => $title
                );
                $message = $engine->render($str, $repl);
                $default['message'] = $message;
                $sentMessage = $MadelineProto->messages->sendMessage(
                    $default
                );
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
