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




function muteme($update, $MadelineProto, $msg = '', $send = true)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $mods = $MadelineProto->responses['muteme']['mods'];
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
        if (is_moderated($ch_id) && is_bot_admin($update, $MadelineProto) && from_admin_mod($update, $MadelineProto, $mods, true)) {
            if (!empty($msg) or array_key_exists('reply_to_msg_id', $update['update']['message'])) {
                $id = catch_id($update, $MadelineProto, $msg);
                if ($id[0]) {
                    $userid = $id[1];
                }
                if (isset($userid)) {
                    $mutemod = $MadelineProto->responses['muteme']['mutemod'];
                    if (!is_admin_mod(
                        $update,
                        $MadelineProto,
                        $userid,
                        $mutemod,
                        true
                    )
                    ) {
                        $username = $id[2];
                        $mention = html_mention($username, $userid);
                        $channelBannedRights = ['_' => 'channelBannedRights', 'view_messages' => false, 'send_messages' => true, 'send_media' => true, 'send_stickers' => true, 'send_gifs' => true, 'send_games' => true, 'send_inline' => true, 'embed_links' => true, 'until_date' => 999999999];
                        $kick = $MadelineProto->
                        channels->editBanned(
                            ['channel' => $peer,
                            'user_id' => $userid,
                            'banned_rights' => $channelBannedRights ]
                        );
                        \danog\MadelineProto\Logger::log($kick);
                        $str = $MadelineProto->responses['muteme']['success'];
                        $repl = [
                            'mention' => $mention,
                        ];
                        $message = $MadelineProto->engine->render($str, $repl);
                        $default['message'] = $message;
                        $alert = "<code>$from_name muted $username in $title</code>";
                    }
                } else {
                    $str = $MadelineProto->responses['muteme']['idk'];
                    $repl = [
                        'msg' => $msg,
                    ];
                    $message = $MadelineProto->engine->render($str, $repl);
                    $default['message'] = $message;
                }
            } else {
                $message = $MadelineProto->responses['muteme']['help'];
                $default['message'] = $message;
            }
        }
        if (isset($default['message']) && $send) {
            $sentMessage = $MadelineProto->messages->sendMessage($default);
        }
        if (isset($sentMessage) && $send) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
        if (isset($alert)) {
            alert_moderators($MadelineProto, $ch_id, $alert);
        }
    }
}

function unmuteme($update, $MadelineProto, $msg = '')
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $mods = $MadelineProto->responses['unmuteme']['mods'];
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
        if (is_moderated($ch_id) && is_bot_admin($update, $MadelineProto) && from_admin_mod($update, $MadelineProto, $mods, true)) {
            if (!empty($msg) or array_key_exists('reply_to_msg_id', $update['update']['message'])) {
                $id = catch_id($update, $MadelineProto, $msg);
                if ($id[0]) {
                    $userid = $id[1];
                }
                if (isset($userid)) {
                    $username = $id[2];
                    $mention = html_mention($username, $userid);
                    $channelBannedRights = ['_' => 'channelBannedRights', 'view_messages' => false, 'send_messages' => false, 'send_media' => false, 'send_stickers' => false, 'send_gifs' => false, 'send_games' => false, 'send_inline' => false, 'embed_links' => false, 'until_date' => 999999999];
                    $kick = $MadelineProto->
                    channels->editBanned(
                        ['channel' => $peer,
                        'user_id' => $userid,
                        'banned_rights' => $channelBannedRights ]
                    );
                    $str = $MadelineProto->responses['unmuteme']['success'];
                    $repl = [
                        'mention' => $mention,
                    ];
                    $message = $MadelineProto->engine->render($str, $repl);
                    $default['message'] = $message;
                    $alert = "<code>$from_name unmuted $username in $title</code>";
                }
            } else {
                $message = $MadelineProto->responses['unmuteme']['help'];
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
        if (isset($alert)) {
            alert_moderators($MadelineProto, $ch_id, $alert);
        }
    }
}