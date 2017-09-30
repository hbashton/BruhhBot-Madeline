<?php
/**
 * Copyright (C) 2016-2017 Hunter Ashton
 * This file is part of BruhhBot.
 * BruhhBot is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * BruhhBot is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with BruhhBot. If not, see <http://www.gnu.org/licenses/>.
 */



function get_user_stats($update, $MadelineProto, $user)
{
    $msg_id = $update['update']['message']['id'];
    if (is_peeruser($update, $MadelineProto)) {
        $peer = cache_get_info(
            $update,
            $MadelineProto,
            $update['update']['message']['from_id']
        )['bot_api_id'];
        $cont = true;
    }
    if (is_supergroup($update, $MadelineProto)) {
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $cont = true;
    }
    if (!$update['update']['message']['out'] && isset($cont)) {
        $default = [
            'peer'            => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode'      => 'html',
        ];
        if ($user !== '' or array_key_exists('reply_to_msg_id', $update['update']['message'])) {
            $msg_id = $update['update']['message']['id'];
            $catch = catch_id($update, $MadelineProto, $user);
            if ($catch[0]) {
                $id = $catch[1];
                $user_data = user_specific_data($update, $MadelineProto, $id);
                if (!$user_data) return;
                $id = $user_data['id'];
                $firstname = htmlentities($user_data['firstname']);
                if (array_key_exists('lastname', $user_data)) {
                    $lastname = htmlentities($user_data['lastname']);
                }
                if (array_key_exists('username', $user_data)) {
                    $username = $user_data['username'];
                }
                if (array_key_exists('banned', $user_data)) {
                    $banned = $user_data['banned'];
                }
                if (array_key_exists('gbanned', $user_data)) {
                    $gbanned = $user_data['gbanned'];
                }
                $message = "<b>User info</b>:\r\nFirst Name: $firstname\r\n";
                if (isset($lastname)) {
                    $message = $message."Last Name: $lastname \r\n";
                }
                $message = $message."ID: $id\r\n";
                if (isset($username)) {
                    $message = $message."Username: $username \r\n";
                }
                if (isset($banned)) {
                    foreach ($banned as $key => $value) {
                        if ($value !== []) {
                            $title = htmlentities($value['title']);
                            $chatid = $value['id'];
                            if (!isset($ban)) {
                                $ban = "\r\n<b>Banned from:</b>\r\n$title [$chatid]\r\n";
                            } else {
                                $ban = $ban."$title - $chatid\r\n";
                            }
                        }
                    }
                    $message = $message.$ban;
                } else {
                    $message = $message."\r\nNot banned from any of my chats.\r\n";
                }
                if (isset($gbanned)) {
                    $message = $message."\r\nGlobally banned: <b>Yes</b>";
                    check_json_array('reasons.json', false, false);
                    $file = file_get_contents('reasons.json');
                    $reasons = json_decode($file, true);
                    if (array_key_exists($id, $reasons)) {
                        $reason = $reasons[$id];
                        $message = $message."\nReason: $reason";
                    }
                } else {
                    $message = $message."\r\nGlobally banned: <b>No</b>";
                }
            } else {
                $message = "I don't know anyone by the name of $user";
            }
        } else {
            $message = 'Use <code>/stats @username</code> to get some info about a user';
        }
        if (isset($message)) {
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
