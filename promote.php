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
function promoteme($update, $MadelineProto, $msg)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $mods = "Only the best get to promote people. You're not the best";
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            );
        if (is_moderated($ch_id)) {
            if (from_admin($update, $MadelineProto, $mods, true)) {
                $id = catch_id($update, $MadelineProto, $msg);
                if ($id[0]) {
                    $userid = $id[1];
                    $username = $id[2];
                    $mention = create_mention(5, $username, $userid);
                    check_json_array('promoted.json', $ch_id);
                    $file = file_get_contents("promoted.json");
                    $promoted = json_decode($file, true);
                    if (array_key_exists($ch_id, $promoted)) {
                        if (!in_array($userid, $promoted[$ch_id])) {
                            array_push($promoted[$ch_id], $userid);
                            file_put_contents('promoted.json', json_encode($promoted));
                            $message = "User $username is now a moderator of $title";
                            $len = strlen($message) - strlen($title);
                            $entity = create_style('bold', $len, $title, false);
                            $mention[] = $entity;
                            $default['message'] = $message;
                            $default['entities'] = $mention;
                        } else {
                            $message = "User $username is already a moderator of $title";
                            $len = strlen($message) - strlen($title);
                            $entity = create_style('bold', $len, $title, false);
                            $mention[] = $entity;
                            $default['message'] = $message;
                            $default['entities'] = $mention;
                        }
                    } else {
                        $promoted[$ch_id] = [];
                        array_push($promoted[$ch_id], $userid);
                        file_put_contents('promoted.json', json_encode($promoted));
                        $message = "User $username is now a moderator of $title";
                        $len = strlen($message) - strlen($title);
                        $entity = create_style('bold', $len, $title, false);
                        $mention[] = $entity;
                        $default['message'] = $message;
                        $default['entities'] = $mention;
                    }
                } else {
                    $message = "I don't know of anyone called ".$msg;
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

function demoteme($update, $MadelineProto, $msg)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $mods = "Wow. Mr. I'm not admin over here is trying to DEMOTE people.";
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            );
        if (from_admin($update, $MadelineProto, $mods, true)) {
            $id = catch_id($update, $MadelineProto, $msg);
            if ($id[0]) {
                $userid = $id[1];
                $username = $id[2];
                $mention = create_mention(5, $username, $userid);
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
                        $message = "User $username is NO LONGER a moderator of ".
                        $title;
                        $len = strlen($message) - strlen($title);
                        $entity = create_style('bold', $len, $title, false);
                        $mention[] = $entity;
                        $default['message'] = $message;
                        $default['entities'] = $mention;
                    } else {
                        $message = "User $username is not currently a moderator of ".
                        $title;
                        $len = strlen($message) - strlen($title);
                        $entity = create_style('bold', $len, $title, false);
                        $mention[] = $entity;
                        $default['message'] = $message;
                        $default['entities'] = $mention;
                    }
                } else {
                    $message = "User $username is not currently a moderator of ".
                    $title;
                    $len = strlen($message) - strlen($title);
                    $entity = create_style('bold', $len, $title, false);
                    $mention[] = $entity;
                    $default['message'] = $message;
                    $default['entities'] = $mention;
                }
            } else {
                $message = "I don't know of anyone called ".$msg;
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
