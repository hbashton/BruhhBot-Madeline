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




function promoteme($update, $MadelineProto, $msg = '')
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $mods = $MadelineProto->responses['promoteme']['mods'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = htmlentities($chat['title']);
        $ch_id = $chat['id'];
        $fromid = cache_from_user_info($update, $MadelineProto);
        if (!isset($fromid['bot_api_id'])) {
            return;
        }
        $fromid = $fromid['bot_api_id'];
        $from_name = catch_id($update, $MadelineProto, $fromid)[2];
        $default = [
            'peer'            => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode'      => 'html',
            ];
        if (is_moderated($ch_id)) {
            if (from_admin($update, $MadelineProto, $mods, true)) {
                if (!empty($msg) or array_key_exists('reply_to_msg_id', $update['update']['message'])) {
                    $id = catch_id($update, $MadelineProto, $msg);
                    if ($id[0]) {
                        $userid = $id[1];
                        $username = $id[2];
                        $mention = html_mention($username, $userid);
                        $channelAdminRights = ['_' => 'channelAdminRights', 'change_info' => true, 'delete_messages' => true, 'ban_users' => true, 'invite_users' => true, 'pin_messages' => true, 'add_admins' => true];
                        try {
                        $editAdmin = $MadelineProto->
                        channels->editAdmin(
                            ['channel' => $peer,
                            'user_id' => $userid,
                            'admin_rights' => $channelAdminRights]
                        );
                        \danog\MadelineProto\Logger::log($editAdmin);
                        } catch (Exception $e) {
                            var_dump($e->getMessage());
                            var_dump($e->rpc);
                        }
                        $str = $MadelineProto->responses['promoteme']['success'];
                        $repl = [
                            'mention' => $mention,
                            'title'   => $title,
                        ];
                        $message = $MadelineProto->engine->render($str, $repl);
                        $default['message'] = $message;
                        $alert = "<code>$from_name promoted $username to a moderator in $title</code>";
                    } else {
                        $str = $MadelineProto->responses['promoteme']['idk'];
                        $repl = [
                            'msg' => $msg,
                        ];
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
        if (isset($alert)) {
            alert_moderators($MadelineProto, $ch_id, $alert);
        }
    }
}

function demoteme($update, $MadelineProto, $msg = '')
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $mods = "Wow. Mr. I'm not admin over here is trying to DEMOTE people.";
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = htmlentities($chat['title']);
        $ch_id = $chat['id'];
        $fromid = cache_from_user_info($update, $MadelineProto);
        if (!isset($fromid['bot_api_id'])) {
            return;
        }
        $fromid = $fromid['bot_api_id'];
        $from_name = catch_id($update, $MadelineProto, $fromid)[2];
        $default = [
            'peer'            => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode'      => 'html',
            ];
        if (from_admin($update, $MadelineProto, $mods, true)) {
            if (!empty($msg) or array_key_exists('reply_to_msg_id', $update['update']['message'])) {
                $id = catch_id($update, $MadelineProto, $msg);
                if ($id[0]) {
                    $userid = $id[1];
                    $username = $id[2];
                    $mention = html_mention($username, $userid);
                    $channelAdminRights = ['_' => 'channelAdminRights', 'change_info' => false, 'delete_messages' => false, 'ban_users' => false, 'invite_users' => false, 'pin_messages' => false, 'add_admins' => false];
                    $editAdmin = $MadelineProto->
                    channels->editAdmin(
                        ['channel' => $peer,
                        'user_id' => $userid,
                        'admin_rights' => $channelAdminRights ]
                    );
                    $str = $MadelineProto->responses['demoteme']['success'];
                    $repl = [
                        'mention' => $mention,
                        'title'   => $title,
                    ];
                    $message = $MadelineProto->engine->render($str, $repl);
                    $default['message'] = $message;
                    $alert = "<code>$from_name demoted $username in $title</code>";
                } else {
                    $str = $MadelineProto->responses['demoteme']['idk'];
                    $repl = [
                        'msg' => $msg,
                    ];
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
        if (isset($alert)) {
            alert_moderators($MadelineProto, $ch_id, $alert);
        }
    }
}
