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



function start_message($update, $MadelineProto)
{
    if (is_peeruser($update, $MadelineProto)) {
        $peer = cache_get_info(
            $update,
            $MadelineProto,
            $update['update']['message']['from_id']
        )['bot_api_id'];
        $msg_id = $update['update']['message']['id'];
        try {
            if ($update['update']['message']['message'] != '/start') {
                $query = preg_replace("/\/start/", '', $update['update']['message']['message']);
                if (preg_match_all('/settings-/', $query, $out)) {
                    $chat = preg_replace('/ settings-/', '', $query);
                    settings_menu_deeplink($update, $MadelineProto, $chat);
                }
                if (preg_match_all('/rules-/', $query, $out)) {
                    $chat = preg_replace('/ rules-/', '', $query);
                    get_chat_rules_deeplink($update, $MadelineProto, $chat);
                }

                return;
            }
        } catch (Exception $e) {
        }
        $default = [
            'peer'            => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode'      => 'html',
            ];
        $botname = getenv('BOT_USERNAME');
        $default['message'] = "Hi! I'm a bot made for managing supergroups. To use my functionality, you'll need to add me to your group, and my helper $botname must be there as well (as an admin!). To explore my commands, use /help. To get started using me in a group, you can either add me and my helper, or you can message me !join <code>[invite link|@username]</code> to get $botname to join on their own, then proceed to add me. Once you have both of us in your group, use /add to begin using me.";
        if (isset($default['message'])) {
            try {
                $sentMessage = $MadelineProto->messages->sendMessage(
                    $default
                );
            } catch (Exception $e) {
            }
        }
    }
}

function help_message($update, $MadelineProto)
{
    if (is_peeruser($update, $MadelineProto)) {
        $peer = cache_get_info(
            $update,
            $MadelineProto,
            $update['update']['message']['from_id']
        )['bot_api_id'];
        $msg_id = $update['update']['message']['id'];
        $default = [
            'peer'            => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode'      => 'html',
            'message'         => 'You can navigate the help menu to see each command and how it\'s used. All commands can be started with !, /, or #',
            ];
        $file = file_get_contents('start_help.json');
        $startj = json_decode($file, true);
        $button_list = [];
        foreach ($startj['menus'] as $menu => $desc) {
            $button_list[] =
                ['_' => 'keyboardButtonCallback', 'text' => $menu, 'data' => json_encode([
                    'q' => 'help2',
                    'v' => "$menu",
                    'u' => $peer, ])];
        }
        $menu = build_keyboard_callback($button_list, 2);
        $replyInlineMarkup = ['_' => 'replyInlineMarkup', 'rows' => $menu];
        $default['reply_markup'] = $replyInlineMarkup;
    }
    try {
        $sentMessage = $MadelineProto->messages->sendMessage(
            $default
        );
        \danog\MadelineProto\Logger::log($sentMessage);
    } catch (Exception $e) {
    }
}
