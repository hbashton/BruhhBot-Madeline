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
    if ($update['update']['message']['to_id']['_'] == 'peerChannel') {
        $peer = $MadelineProto->get_info($update['update']['message']['to_id'])
        ['InputPeer'];
        $msg_id = $update['update']['message']['id'];
        $mods = "Only the best get to promote people. You're not the best";
        if (from_admin($update, $MadelineProto, $mods, true)) {
            $id = catch_id($update, $MadelineProto, $msg);
            $ch_id = -100 . $update['update']['message']['to_id']['channel_id'];
            $title = $MadelineProto->get_info(
                -100 . $update['update']['message']
                ['to_id']['channel_id']
            )['Chat']['title'];
            if ($id[0]) {
                $userid = $id[1];
                $username = $id[2];
                $mention = [['_' => 'inputMessageEntityMentionName', 'offset' =>
                5, 'length' => strlen($username), 'user_id' => $userid]];
                if (!file_exists('promoted.json')) {
                    $json_data = [];
                    $json_data[$ch_id] = [];
                    file_put_contents('promoted.json', json_encode($json_data));
                }
                $file = file_get_contents("promoted.json");
                $promoted = json_decode($file, true);
                if (array_key_exists($ch_id, $promoted)) {
                    if (!in_array($userid, $promoted[$ch_id])) {
                        array_push($promoted[$ch_id], $userid);
                        file_put_contents('promoted.json', json_encode($promoted));
                        $message = "User $username is now a moderator of $title";
                        $entity = ['_' => 'messageEntityBold',
                        'offset' => strlen($message) - strlen($title),
                        'length' => strlen($title) ];
                    } else {
                        $message = "User $username is already a moderator of $title";
                        $entity = ['_' => 'messageEntityBold',
                        'offset' => strlen($message) - strlen($title),
                        'length' => strlen($title) ];
                    }
                } else {
                    $promoted[$ch_id] = [];
                    array_push($promoted[$ch_id], $userid);
                    file_put_contents('promoted.json', json_encode($promoted));
                    $message = "User ".$username." is now a moderator of ".$title;
                    $entity = ['_' => 'messageEntityBold',
                        'offset' => strlen($message) - strlen($title),
                        'length' => strlen($title) ];
                }
            } else {
                $message = "I don't know of anyone called ".$msg;

            }
        }
        if (isset($mention)) {
            $mention[] = $entity;
            $sentMessage = $MadelineProto->messages->sendMessage(
                ['peer' => $peer, 'reply_to_msg_id' =>
                $msg_id, 'message' => $message, 'entities' => $mention]
            );
        } else {
            if (isset($message)) {
                $sentMessage = $MadelineProto->messages->sendMessage(
                    ['peer' => $peer, 'reply_to_msg_id' =>
                    $msg_id, 'message' => $message]
                );
            }
        }
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}

function demoteme($update, $MadelineProto, $msg)
{
    if ($update['update']['message']['to_id']['_'] == 'peerChannel') {
        $peer = $MadelineProto->get_info($update['update']['message']['to_id'])
        ['InputPeer'];
        $msg_id = $update['update']['message']['id'];
        $mods = "I think admins should be the ones to do this, don't you?";
        if (from_admin($update, $MadelineProto, $mods, true)) {
            $id = catch_id($update, $MadelineProto, $msg);
            $ch_id = -100 . $update['update']['message']['to_id']['channel_id'];
            $title = $MadelineProto->get_info(
                -100 . $update['update']['message']
                ['to_id']['channel_id']
            )['Chat']['title'];
            if ($id[0]) {
                $userid = $id[1];
                $username = $id[2];
                if (!file_exists('promoted.json')) {
                    $json_data = [];
                    $json_data[$ch_id] = [];
                    file_put_contents('promoted.json', json_encode($json_data));
                }
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
                    } else {
                        $message = "User $username is not a moderator of ".
                        $title;
                    }
                } else {
                    $message = "User $username is not a moderator of ".
                    $title;
                }
            } else {
                $message = "I don't know of anyone called ".$message;
            }
        }
        $mention = [['_' => 'inputMessageEntityMentionName', 'offset' =>
        5, 'length' => strlen($username), 'user_id' => $userid],  ['_' => 'messageEntityBold',
        'offset' => strlen($message) - strlen($title),
        'length' => strlen($title) ]];
        $sentMessage = $MadelineProto->messages->sendMessage(
            ['peer' => $peer, 'reply_to_msg_id' =>
            $msg_id, 'message' => $message, 'entities' => $mention]
        );
        \danog\MadelineProto\Logger::log($sentMessage);
    }
}
