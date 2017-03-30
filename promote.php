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
function promoteme($update, $MadelineProto, $msg = "")
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $mods = $MadelineProto->responses['promoteme']['mods'];
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
            if (from_admin($update, $MadelineProto, $mods, true)) {
                if (!empty($msg) or array_key_exists('reply_to_msg_id', $update['update']['message'])) {
                    $id = catch_id($update, $MadelineProto, $msg);
                    if ($id[0]) {
                        $userid = $id[1];
                        $username = $id[2];
                        $mention = html_mention($username, $userid);
                        check_json_array('promoted.json', $ch_id);
                        $file = file_get_contents("promoted.json");
                        $promoted = json_decode($file, true);
                        if (array_key_exists($ch_id, $promoted)) {
                            if (!in_array($userid, $promoted[$ch_id])) {
                                array_push($promoted[$ch_id], $userid);
                                file_put_contents('promoted.json', json_encode($promoted));
                                $str = $MadelineProto->responses['promoteme']['success'];
                                $repl = array(
                                    "mention" => $mention,
                                    "title" => $title
                                );
                                $message = $MadelineProto->engine->render($str, $repl);
                                $default['message'] = $message;
                            } else {
                                $str = $MadelineProto->responses['promoteme']['already'];
                                $repl = array(
                                    "mention" => $mention,
                                    "title" => $title
                                );
                                $message = $MadelineProto->engine->render($str, $repl);
                                $default['message'] = $message;
                            }
                        } else {
                            $promoted[$ch_id] = [];
                            array_push($promoted[$ch_id], $userid);
                            file_put_contents('promoted.json', json_encode($promoted));
                            $str = $MadelineProto->responses['promoteme']['success'];
                            $repl = array(
                                "mention" => $mention,
                                "title" => $title
                            );
                            $message = $MadelineProto->engine->render($str, $repl);
                            $default['message'] = $message;
                        }
                    } else {
                        $str = $MadelineProto->responses['promoteme']['idk'];
                        $repl = array(
                            "msg" => $msg
                        );
                        $message = $MadelineProto->engine->render($str, $repl);
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

function demoteme($update, $MadelineProto, $msg = "")
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $mods = "Wow. Mr. I'm not admin over here is trying to DEMOTE people.";
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = htmlentities($chat['title']);
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode' => 'html'
            );
        if (from_admin($update, $MadelineProto, $mods, true)) {
            if (!empty($msg) or array_key_exists('reply_to_msg_id', $update['update']['message'])) {
                $id = catch_id($update, $MadelineProto, $msg);
                if ($id[0]) {
                    $userid = $id[1];
                    $username = $id[2];
                    $mention = html_mention($username, $userid);
                    check_json_array('promoted.json', $ch_id);
                    $file = file_get_contents("promoted.json");
                    $promoted = json_decode($file, true);
                    if (array_key_exists($ch_id, $promoted)) {
                        if (in_array($userid, $promoted[$ch_id])) {
                            if (($key = array_search(
                                $userid,
                                $promoted[$ch_id]
                            )) !== false
                            ) {
                                unset($promoted[$ch_id][$key]);
                            }
                            file_put_contents('promoted.json', json_encode($promoted));
                            $str = $MadelineProto->responses['demoteme']['success'];
                            $repl = array(
                                "mention" => $mention,
                                "title" => $title
                            );
                            $message = $MadelineProto->engine->render($str, $repl);
                            $default['message'] = $message;
                        } else {
                            $str = $MadelineProto->responses['demoteme']['fail'];
                            $repl = array(
                                "mention" => $mention,
                                "title" => $title
                            );
                            $message = $MadelineProto->engine->render($str, $repl);
                            $default['message'] = $message;
                        }
                    } else {
                        $str = $MadelineProto->responses['demoteme']['success'];
                        $repl = array(
                            "mention" => $mention,
                            "title" => $title
                        );
                        $message = $MadelineProto->engine->render($str, $repl);
                        $default['message'] = $message;
                    }
                } else {
                    $str = $MadelineProto->responses['demoteme']['idk'];
                    $repl = array(
                        "msg" => $msg
                    );
                    $message = $MadelineProto->engine->render($str, $repl);
                    $default['message'] = $message;
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
