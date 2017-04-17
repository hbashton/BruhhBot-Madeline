<?php
/*
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

function lockme($update, $MadelineProto, $msg)
{
    if (bot_present($update, $MadelineProto)) {
        if (is_supergroup($update, $MadelineProto)) {
            $msg_id = $update['update']['message']['id'];
            $mods = $MadelineProto->responses['lockme']['mods'];
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
                $coniguration = file_get_contents("configuration.json");
                $cfg = json_decode($coniguration, true);
                if (!empty($msg)) {
                    if (in_array($msg, $cfg["types"])) {
                        if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                            check_json_array('locked.json', $ch_id);
                            $file = file_get_contents("locked.json");
                            $locked = json_decode($file, true);
                            if (isset($locked[$ch_id])) {
                                if (!in_array($msg, $locked[$ch_id])) {
                                    if ($msg == "flood") {
                                        $locked[$ch_id]['floodlimit'] = 10;
                                    }
                                    array_push($locked[$ch_id], $msg);
                                    file_put_contents('locked.json', json_encode($locked));
                                    $message = $cfg["lock"][$msg];
                                    $default['message'] = $message;
                                } else {
                                    $message = $cfg["lock"]["already"][$msg];
                                    $default['message'] = $message;
                                }
                            } else {
                                $locked[$ch_id] = [];
                                if ($msg == "flood") {
                                    $locked[$ch_id]['floodlimit'] = 10;
                                }
                                array_push($locked[$ch_id], $msg);
                                file_put_contents('locked.json', json_encode($locked));
                                $message = $cfg["lock"][$msg];
                                $default['message'] = $message;
                            }
                        }
                    } else {
                        $str = $MadelineProto->responses['lockme']['invalid'];
                        $repl = array(
                            "msg" => $msg
                        );
                        $message = $MadelineProto->engine->render($str, $repl);
                        $default['message'] = $message;
                    }
                } else {
                    $message = $MadelineProto->responses['lockme']['help'];
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

function unlockme($update, $MadelineProto, $msg)
{
    if (bot_present($update, $MadelineProto)) {
        if (is_supergroup($update, $MadelineProto)) {
            $msg_id = $update['update']['message']['id'];
            $mods = $MadelineProto->responses['unlockme']['mods'];
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
                $coniguration = file_get_contents("configuration.json");
                $cfg = json_decode($coniguration, true);
                if (!empty($msg)) {
                    if (in_array($msg, $cfg["types"])) {
                        if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                            check_json_array('locked.json', $ch_id);
                            $file = file_get_contents("locked.json");
                            $locked = json_decode($file, true);
                            if (isset($locked[$ch_id])) {
                                if (in_array($msg, $locked[$ch_id])) {
                                    if (($key = array_search(
                                        $msg,
                                        $locked[$ch_id]
                                    )) !== false
                                    ) {
                                        unset($locked[$ch_id][$key]);
                                    }
                                    if ($msg == "flood") {
                                        unset($locked[$ch_id]['floodlimit']);
                                    }
                                    file_put_contents('locked.json', json_encode($locked));
                                    $message = $cfg["unlock"][$msg];
                                    $default['message'] = $message;
                                } else {
                                    $message = $cfg["unlock"]["already"][$msg];
                                    $default['message'] = $message;
                                }
                            } else {
                                $locked[$ch_id] = [];
                                if ($msg == "flood") {
                                    unset($locked[$ch_id]['floodlimit']);
                                }
                                file_put_contents('locked.json', json_encode($locked));
                                $message = $cfg["unlock"]["already"][$msg];
                                $default['message'] = $message;
                            }
                        }
                    } else {
                        $str = $MadelineProto->responses['unlockme']['invalid'];
                        $repl = array(
                            "msg" => $msg
                        );
                        $message = $MadelineProto->engine->render($str, $repl);
                        $default['message'] = $message;
                    }
                } else {
                    $str = $MadelineProto->responses['unlockme']['help'];
                    $repl = array(
                        "msg" => $msg
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

function setflood($update, $MadelineProto, $msg)
{
    if (bot_present($update, $MadelineProto)) {
        if (is_supergroup($update, $MadelineProto)) {
            $msg_id = $update['update']['message']['id'];
            $mods = $MadelineProto->responses['setflood']['mods'];
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
                if (!empty($msg)) {
                    if (is_numeric($msg)) {
                        if ($msg > 1) {
                            if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                                check_json_array('locked.json', $ch_id);
                                $file = file_get_contents("locked.json");
                                $locked = json_decode($file, true);
                                if (aisset($locked[$ch_id])) {
                                    $locked[$ch_id]['floodlimit'] = (int) $msg;
                                    file_put_contents('locked.json', json_encode($locked));
                                    $str = $MadelineProto->responses['setflood']['success'];
                                    $repl = array(
                                        "msg" => $msg
                                    );
                                    $message = $MadelineProto->engine->render($str, $repl);
                                    $default['message'] = $message;
                                } else {
                                    $locked[$ch_id] = [];
                                    $locked[$ch_id]['floodlimit'] = (int) $msg;
                                    file_put_contents('locked.json', json_encode($locked));
                                    $str = $MadelineProto->responses['setflood']['success'];
                                    $repl = array(
                                        "msg" => $msg
                                    );
                                    $message = $MadelineProto->engine->render($str, $repl);
                                    $default['message'] = $message;
                                }
                            }
                        } else {
                            $default['message'] = "Please use a number greater than 1";
                        }
                    } else {
                        $str = $MadelineProto->responses['setflood']['invalid'];
                        $repl = array(
                            "msg" => $msg
                        );
                        $message = $MadelineProto->engine->render($str, $repl);
                        $default['message'] = $message;
                    }
                } else {
                    $message = $MadelineProto->responses['setflood']['help'];
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
