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
function catch_id($update, $MadelineProto, $user)
{
    $msg_id = $update['update']['message']['id'];
    if (array_key_exists('entities', $update['update']['message'])) {
        foreach ($update['update']['message']['entities'] as $key) {
            if (array_key_exists('user_id', $key)) {
                try {
                    $userid = $key['user_id'];
                    $user_ = cache_get_info($update, $MadelineProto, $userid);
                    if (array_key_exists(
                        'username', $user_['User']
                    )
                    ) {
                        $username = $MadelineProto->utf8ize($user_['User']['username']);
                    } elseif (array_key_exists(
                        'first_name', $user_['User']
                    )
                    ) {
                        $username = $MadelineProto->utf8ize($user_['User']['first_name']);
                    } else {
                        $username = "no-name-user";
                    }
                    $userid = $user_['bot_api_id'];
                    break;
                } catch (Exception $e) {
                    return array(false);
                }
            }
        }
    }
    if (!isset($userid) && !empty($user)) {
        $user_ = cache_get_info($update, $MadelineProto, $user);
        if ($user_) {
            try {
                if (array_key_exists(
                    'username', $user_['User']
                )
                ) {
                    $username = $MadelineProto->utf8ize($user_['User']['username']);
                } elseif (array_key_exists(
                    'first_name', $user_['User']
                )
                ) {
                    $username = $MadelineProto->utf8ize($user_['User']['first_name']);
                } else {
                    $username = "no-name-user";
                }
                $userid = $user_['bot_api_id'];
                $return = array(true, $userid, $username);
            } catch (Exception $e) {
                return array(false);
            }
        }
    }
    if (!isset($userid) && is_supergroup($update, $MadelineProto) && array_key_exists('reply_to_msg_id', $update['update']['message'])) {
        try {
            $msg_id = $update['update']['message']['reply_to_msg_id'];
            $chat = parse_chat_data($update, $MadelineProto);
            $peer = $chat['peer'];
            $message = $MadelineProto->channels->getMessages(['channel' => $peer, 'id' => [$msg_id]]);
            if (is_array($message)) {
                if (array_key_exists('messages', $message)) {
                    $userid = $message['messages'][0]['from_id'];
                    $user_ = cache_get_info($update, $MadelineProto, $userid);
                    if ($user_) {
                        if (array_key_exists(
                            'username', $user_['User']
                        )
                        ) {
                            $username = $MadelineProto->utf8ize($user_['User']['username']);
                        } elseif (array_key_exists(
                            'first_name', $user_['User']
                        )
                        ) {
                            $username = $MadelineProto->utf8ize($user_['User']['first_name']);
                        } else {
                            $username = "no-name-user";
                        }
                        $userid = $user_['bot_api_id'];
                        $return = array(true, $userid, $username);
                    }
                }
            }
        } catch (Exception $e) {
            return array(false);
        }
    }
    if (isset($userid)) {
        return array(true, $userid, $username);
    } else {
        return array(false);
    }
}
