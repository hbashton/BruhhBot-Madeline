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

function export_new_invite($update, $MadelineProto)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $mods = $MadelineProto->responses['export_new_invite']['mods'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = htmlentities($chat['title']);
        $ch_id = $chat['id'];
        $default = [
            'peer'            => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode'      => 'html',
            ];
        if (is_moderated($ch_id)) {
            if (is_bot_admin($update, $MadelineProto)) {
                if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                    try {
                        $exportInvite = $MadelineProto->channels->exportInvite(
                            ['channel' => $peer]
                        );
                        $link = $exportInvite['link'];
                        $str = $MadelineProto->responses['export_new_invite']['link'];
                        $repl = [
                            'link' => $link,
                        ];
                        $message = $MadelineProto->engine->render($str, $repl);
                        $default['message'] = $message;
                        $sentMessage = $MadelineProto->messages->sendMessage(
                            $default
                        );
                        \danog\MadelineProto\Logger::log($sentMessage);
                    } catch (Exception $e) {
                        $message = $MadelineProto->responses['export_new_invite']['exception'];
                        $default['message'] = $message;
                        $sentMessage = $MadelineProto->messages->sendMessage(
                            $default
                        );
                        \danog\MadelineProto\Logger::log($sentMessage);
                    }
                }
            }
        }
    }
}

function welcome_toggle($update, $MadelineProto)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $mods = $MadelineProto->responses['welcome_toggle']['mods'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $ch_id = $chat['id'];
        $userid = cache_from_user_info($update, $MadelineProto);
        if (isset($userid['bot_api_id'])) {
            $userid = $userid['bot_api_id'];
        } else {
            return;
        }
        $default = [
            'peer'            => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode'      => 'html',
            ];
        if (is_moderated($ch_id)) {
            if (is_bot_admin($update, $MadelineProto)) {
                if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                    $default['message'] = 'Would you like to welcome users when they join this group?';
                    $welcomeon = ['_' => 'keyboardButtonCallback', 'text' => 'Welcome new users', 'data' => json_encode([
                    'q'                => 'welcome', // query
                    'v'                => 'on',      // value
                    'u'                => $userid, ])]; // userid
                    $welcomeoff = ['_' => 'keyboardButtonCallback', 'text' => "Don't welcome new users", 'data' => json_encode([
                    'q' => 'welcome',
                    'v' => 'off',
                    'u' => $userid, ])];
                    $row1 = ['_' => 'keyboardButtonRow', 'buttons' => [$welcomeon]];
                    $row2 = ['_' => 'keyboardButtonRow', 'buttons' => [$welcomeoff]];
                    $replyInlineMarkup = ['_' => 'replyInlineMarkup', 'rows' => [$row1, $row2]];
                    $default['reply_markup'] = $replyInlineMarkup;
                }
            }
        }
        if (isset($default['message'])) {
            $sentMessage = $MadelineProto->messages->sendMessage(
                $default
            );
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}
