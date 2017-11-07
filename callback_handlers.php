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



function welcome_callback($update, $MadelineProto)
{
    $parsed_query = parse_query($update, $MadelineProto);
    $peer = $parsed_query['peer'];
    $id = $parsed_query['msg_id'];
    $ch_id = $parsed_query['data']['c'];
    $userid = $parsed_query['user_id'];
    $default = [
        'peer'       => $parsed_query['peer'],
        'id'         => $parsed_query['msg_id'],
        'parse_mode' => 'html',
        'message'    => 'If you ask me to, I will greet new people!',
    ];
    if (!is_admin_mod($update, $MadelineProto, $parsed_query['user_id'], false, false, $parsed_query['data']['c'])) {
        try {
            $callbackAnswer = $MadelineProto->messages->setBotCallbackAnswer(['alert'  => true, 'query_id' => $parsed_query['query_id'], 'message' => 'You cannot change the settings of this chat', 'cache_time' => 3]);
            \danog\MadelineProto\Logger::log($callbackAnswer);

            return;
        } catch (Exception $e) {
        }

        return;
    }
    if (is_moderated($ch_id)) {
        if ($parsed_query['data']['v'] == 'on') {
            check_json_array('settings.json', $ch_id);
            $file = file_get_contents('settings.json');
            $settings = json_decode($file, true);
            $settings[$ch_id]['welcome'] = true;
            $text = "Welcome new users \xE2\x9C\x85";
        } else {
            $text = 'Welcome new users';
        }
        $welcomeon = ['_' => 'keyboardButtonCallback', 'text' => "$text", 'data' => json_encode([
        'q' => 'welcome',
        'v' => 'on',
        'c' => $ch_id, ])];
        if ($parsed_query['data']['v'] == 'off') {
            check_json_array('settings.json', $ch_id);
            $file = file_get_contents('settings.json');
            $settings = json_decode($file, true);
            $settings[$ch_id]['welcome'] = false;
            $text = "Don't welcome new users \xE2\x9C\x85";
        } else {
            $text = "Don't welcome new users";
        }
        $welcomeoff = ['_' => 'keyboardButtonCallback', 'text' => "$text", 'data' => json_encode([
        'q' => 'welcome',
        'v' => 'off',
        'c' => $ch_id, ])];
        $row1 = ['_' => 'keyboardButtonRow', 'buttons' => [$welcomeon]];
        $row2 = ['_' => 'keyboardButtonRow', 'buttons' => [$welcomeoff]];
        $back = ['_' => 'keyboardButtonCallback', 'text' => "\xf0\x9f\x94\x99", 'data' => json_encode([
    'q' => 'back_to_settings', // query
    'c' => $ch_id, ])];        // chat
        $row3 = ['_' => 'keyboardButtonRow', 'buttons' => [$back]];
        $replyInlineMarkup = ['_' => 'replyInlineMarkup', 'rows' => [$row1, $row2, $row3]];
        $default['reply_markup'] = $replyInlineMarkup;
        file_put_contents('settings.json', json_encode($settings));
        try {
            $editedMessage = $MadelineProto->messages->editMessage(
                $default
            );
            \danog\MadelineProto\Logger::log($editedMessage);
        } catch (Exception $e) {
        }
    }
}

function lock_callback($update, $MadelineProto)
{
    $parsed_query = parse_query($update, $MadelineProto);
    $peer = $parsed_query['peer'];
    $id = $parsed_query['msg_id'];
    $ch_id = $parsed_query['data']['c'];
    $userid = $parsed_query['user_id'];
    $message = $MadelineProto->messages->getMessages(['channel' => $peer, 'id' => [$id]]);
    if (!is_array($message)) {
        return;
    }
    $message = $message['messages'][0]['message'];
    $default = [
        'peer'       => $parsed_query['peer'],
        'id'         => $parsed_query['msg_id'],
        'parse_mode' => 'html',
        'message'    => $message,
    ];
    if (!is_admin_mod($update, $MadelineProto, $parsed_query['user_id'], false, false, $parsed_query['data']['c'])) {
        try {
            $callbackAnswer = $MadelineProto->messages->setBotCallbackAnswer(['alert'  => true, 'query_id' => $parsed_query['query_id'], 'message' => 'You cannot change the settings of this chat', 'cache_time' => 3]);
            \danog\MadelineProto\Logger::log($callbackAnswer);

            return;
        } catch (Exception $e) {
        }

        return;
    }
    $val = $parsed_query['data']['v'];
    $file = file_get_contents('locked.json');
    $locked = json_decode($file, true);
    if (!isset($locked[$ch_id])) {
        $locked[$ch_id] = [];
    }
    if (preg_match_all('/-on/', $val, $matches)) {
        $val = preg_replace('/-on/', '', $val);
        $newtext = "\xE2\x9D\x8C";
        $newonoff = 'off';
        array_push($locked[$ch_id], $val);
    }
    if (preg_match_all('/-off/', $val, $matches)) {
        $val = preg_replace('/-off/', '', $val);
        $newtext = "\xE2\x9C\x85";
        $newonoff = 'on';
        if (($key = array_search(
            $val,
            $locked[$ch_id]
        )) !== false
        ) {
            unset($locked[$ch_id][$key]);
        }
    }
    $rows = [];
    $coniguration = file_get_contents('configuration.json');
    $cfg = json_decode($coniguration, true);
    foreach ($cfg['settings_template'] as $key => $value) {
        // check mark \xE2\x9C\x85
        // cross mark \xE2\x9D\x8C
        if (in_array($key, $locked[$ch_id])) {
            $text = "\xE2\x9D\x8C";
            $onoff = 'off';
        } else {
            $text = "\xE2\x9C\x85";
            $onoff = 'on';
        }
        $buttons = [
            ['_' => 'keyboardButtonCallback', 'text' => $value, 'data' => json_encode([
                'q' => 'hint',       // query
                'v' => "$key", ])],
            ['_' => 'keyboardButtonCallback', 'text' => $text, 'data' => json_encode([
                'q' => 'lock',       // query
                'v' => "$key-$onoff", // value
                'c' => $ch_id, ])],   // chat
        ];
        $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons];
        $rows[] = $row;
    }
    $buttons = [
            ['_' => 'keyboardButtonCallback', 'text' => "\xe2\xac\x85\xef\xb8\x8f", 'data' => json_encode([
                'q' => 'decrease_flood',   // query
                'c' => $ch_id, ])],        // chat
            ['_' => 'keyboardButtonCallback', 'text' => (string) $locked[$ch_id]['floodlimit'], 'data' => json_encode([
                'q' => 'hint',             // query
                'v' => 'flood', ])],
            ['_' => 'keyboardButtonCallback', 'text' => "\xe2\x9e\xa1\xef\xb8\x8f", 'data' => json_encode([
                'q' => 'increase_flood',   // query
                'c' => $ch_id, ])],        // chat
        ];
    $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons];
    $rows[] = $row;
    $buttons = [
        ['_' => 'keyboardButtonCallback', 'text' => "\xf0\x9f\x94\x99", 'data' => json_encode([
            'q' => 'back_to_settings', // query
            'c' => $ch_id, ])],         // chat
    ];
    $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons];
    $rows[] = $row;
    $replyInlineMarkup = ['_' => 'replyInlineMarkup', 'rows' => $rows];
    $default['reply_markup'] = $replyInlineMarkup;
    file_put_contents('locked.json', json_encode($locked));
    try {
        $editedMessage = $MadelineProto->messages->editMessage(
            $default
        );
        \danog\MadelineProto\Logger::log($editedMessage);
    } catch (Exception $e) {
    }
}

function increment_flood($update, $MadelineProto, $up = false)
{
    $parsed_query = parse_query($update, $MadelineProto);
    $peer = $parsed_query['peer'];
    $id = $parsed_query['msg_id'];
    $ch_id = $parsed_query['data']['c'];
    $userid = $parsed_query['user_id'];
    $message = $MadelineProto->messages->getMessages(['channel' => $peer, 'id' => [$id]]);
    if (!is_array($message)) {
        return;
    }
    $message = $message['messages'][0]['message'];
    $default = [
        'peer'       => $parsed_query['peer'],
        'id'         => $parsed_query['msg_id'],
        'parse_mode' => 'html',
        'message'    => $message,
    ];
    if (!is_admin_mod($update, $MadelineProto, $parsed_query['user_id'], false, false, $parsed_query['data']['c'])) {
        try {
            $callbackAnswer = $MadelineProto->messages->setBotCallbackAnswer(['alert'  => true, 'query_id' => $parsed_query['query_id'], 'message' => 'You cannot change the settings of this chat', 'cache_time' => 3]);
            \danog\MadelineProto\Logger::log($callbackAnswer);

            return;
        } catch (Exception $e) {
        }

        return;
    }
    $file = file_get_contents('locked.json');
    $locked = json_decode($file, true);
    if ($up) {
        $locked[$ch_id]['floodlimit'] += 1;
    } else {
        $locked[$ch_id]['floodlimit'] -= 1;
    }
    if ($locked[$ch_id]['floodlimit'] <= 1) {
        try {
            $callbackAnswer = $MadelineProto->messages->setBotCallbackAnswer(['alert'  => true, 'query_id' => $parsed_query['query_id'], 'message' => "You can't make the floodlimit 1 or below", 'cache_time' => 3]);
            \danog\MadelineProto\Logger::log($callbackAnswer);

            return;
        } catch (Exception $e) {
        }

        return;
    }
    $rows = [];
    $coniguration = file_get_contents('configuration.json');
    $cfg = json_decode($coniguration, true);
    foreach ($cfg['settings_template'] as $key => $value) {
        // check mark \xE2\x9C\x85
        // cross mark \xE2\x9D\x8C
        if (in_array($key, $locked[$ch_id])) {
            $text = "\xE2\x9D\x8C";
            $onoff = 'off';
        } else {
            $text = "\xE2\x9C\x85";
            $onoff = 'on';
        }
        $buttons = [
            ['_' => 'keyboardButtonCallback', 'text' => $value, 'data' => json_encode([
                'q' => 'hint',       // query
                'v' => "$key", ])],
            ['_' => 'keyboardButtonCallback', 'text' => $text, 'data' => json_encode([
                'q' => 'lock',       // query
                'v' => "$key-$onoff", // value
                'c' => $ch_id, ])],   // chat
        ];
        $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons];
        $rows[] = $row;
    }
    $buttons = [
            ['_' => 'keyboardButtonCallback', 'text' => "\xe2\xac\x85\xef\xb8\x8f", 'data' => json_encode([
                'q' => 'decrease_flood',   // query
                'c' => $ch_id, ])],        // chat
            ['_' => 'keyboardButtonCallback', 'text' => (string) $locked[$ch_id]['floodlimit'], 'data' => json_encode([
                'q' => 'hint',             // query
                'v' => 'flood', ])],        // chat
            ['_' => 'keyboardButtonCallback', 'text' => "\xe2\x9e\xa1\xef\xb8\x8f", 'data' => json_encode([
                'q' => 'increase_flood',   // query
                'c' => $ch_id, ])],        // chat
        ];
    $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons];
    $rows[] = $row;
    $buttons = [
        ['_' => 'keyboardButtonCallback', 'text' => "\xf0\x9f\x94\x99", 'data' => json_encode([
            'q' => 'back_to_settings', // query
            'c' => $ch_id, ])],         // chat
    ];
    $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons];
    $rows[] = $row;
    $replyInlineMarkup = ['_' => 'replyInlineMarkup', 'rows' => $rows];
    $default['reply_markup'] = $replyInlineMarkup;
    file_put_contents('locked.json', json_encode($locked));
    try {
        $editedMessage = $MadelineProto->messages->editMessage(
            $default
        );
        \danog\MadelineProto\Logger::log($editedMessage);
    } catch (Exception $e) {
    }
}

function alert_hint($update, $MadelineProto, $up = false)
{
    $parsed_query = parse_query($update, $MadelineProto);
    $peer = $parsed_query['peer'];
    $id = $parsed_query['msg_id'];
    $userid = $parsed_query['user_id'];
    $v = $parsed_query['data']['v'];
    $message = $MadelineProto->messages->getMessages(['channel' => $peer, 'id' => [$id]]);
    if (!is_array($message)) {
        return;
    }
    $message = $message['messages'][0]['message'];
    $default = [
        'peer'       => $parsed_query['peer'],
        'id'         => $parsed_query['msg_id'],
        'parse_mode' => 'html',
        'message'    => $message,
    ];
    try {
        $callbackAnswer = $MadelineProto->messages->setBotCallbackAnswer(['alert'  => true, 'query_id' => $parsed_query['query_id'], 'message' => $MadelineProto->hints[$v], 'cache_time' => 3]);
        \danog\MadelineProto\Logger::log($callbackAnswer);
    } catch (Exception $e) {
    }
}

function settings_menu_callback($update, $MadelineProto)
{
    $parsed_query = parse_query($update, $MadelineProto);
    $peer = $parsed_query['peer'];
    $id = $parsed_query['msg_id'];
    $ch_id = $parsed_query['data']['c'];
    $info = cache_get_info($update, $MadelineProto, $ch_id, true);
    if ($info) {
        $title = 'for '.htmlentities($info['title']);
    } else {
        $title = '';
    }
    $userid = $parsed_query['user_id'];
    $default = [
        'peer'       => $parsed_query['peer'],
        'id'         => $parsed_query['msg_id'],
        'parse_mode' => 'html',
        'message'    => "Here's the settings menu $title! Feel free to explore",
    ];
    if (!is_admin_mod($update, $MadelineProto, $parsed_query['user_id'], false, false, $parsed_query['data']['c'])) {
        try {
            $callbackAnswer = $MadelineProto->messages->setBotCallbackAnswer(['alert'  => true, 'query_id' => $parsed_query['query_id'], 'message' => 'You cannot change the settings of this chat', 'cache_time' => 3]);
            \danog\MadelineProto\Logger::log($callbackAnswer);

            return;
        } catch (Exception $e) {
        }

        return;
    }
    if (is_moderated($ch_id)) {
        $rows = [];
        $buttons = [
        ['_' => 'keyboardButtonCallback', 'text' => 'Locked', 'data' => json_encode([
            'q' => 'locked',     // query
            'c' => $ch_id, ])],  // chat
        ['_' => 'keyboardButtonCallback', 'text' => 'Group Settings', 'data' => json_encode([
            'q' => 'group_settings',       // query
            'c' => $ch_id, ])],   // chat
        ];
        $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons];
        $rows[] = $row;
        $button = [
            ['_' => 'keyboardButtonCallback', 'text' => 'User Settings', 'data' => json_encode([
                'q' => 'user_settings',
                'c' => $ch_id, ])],
        ];
        $row = ['_' => 'keyboardButtonRow', 'buttons' => $button];
        $rows[] = $row;
        if (is_chat_owner($update, $MadelineProto, $ch_id, $userid)) {
            $buttons = [
                ['_' => 'keyboardButtonCallback', 'text' => 'Moderators', 'data' => json_encode([
                    'q' => 'moderators_menu',
                    'c' => $ch_id, ])],
            ];
            $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons];
            $rows[] = $row;
        }
        $replyInlineMarkup = ['_' => 'replyInlineMarkup', 'rows' => $rows];
        $default['reply_markup'] = $replyInlineMarkup;
    }
    if (isset($default['message'])) {
        try {
            $sentMessage = $MadelineProto->messages->editMessage(
                $default
            );
            \danog\MadelineProto\Logger::log($sentMessage);
        } catch (Exception $e) {
        }
    }
}

function help2_callback($update, $MadelineProto)
{
    $parsed_query = parse_query($update, $MadelineProto);
    $peer = $parsed_query['peer'];
    $id = $parsed_query['msg_id'];
    $userid = $parsed_query['user_id'];
    $v = $parsed_query['data']['v'];
    $default = [
        'peer'       => $parsed_query['peer'],
        'parse_mode' => 'html',
    ];
    $file = file_get_contents('start_help.json');
    $startj = json_decode($file, true);
    $default['message'] = $startj['menus'][$v];
    $button_list = [];
    foreach ($startj['commands_help'][$v] as $command => $desc) {
        $button_list[] =
            ['_' => 'keyboardButtonCallback', 'text' => $command, 'data' => json_encode([
                'q' => 'help3',
                'v' => "$command",
                'e' => "$v", ])];
    }
    $header = [
        ['_' => 'keyboardButtonCallback', 'text' => "\xf0\x9f\x94\x99", 'data' => json_encode([
            'q' => 'back_to_help', ])],
    ];
    $menu = build_keyboard_callback($button_list, 2, $header);
    $replyInlineMarkup = ['_' => 'replyInlineMarkup', 'rows' => $menu];
    $default['reply_markup'] = $replyInlineMarkup;
    if (isset($default['message'])) {
        try {
            $sentMessage = $MadelineProto->messages->sendMessage(
                $default
            );
            \danog\MadelineProto\Logger::log($sentMessage);
        } catch (Exception $e) {
        }
    }
}

function help3_callback($update, $MadelineProto)
{
    $parsed_query = parse_query($update, $MadelineProto);
    $peer = $parsed_query['peer'];
    $id = $parsed_query['msg_id'];
    $userid = $parsed_query['user_id'];
    $v = $parsed_query['data']['v'];
    $e = $parsed_query['data']['e'];
    $default = [
        'peer'       => $parsed_query['peer'],
        'parse_mode' => 'html',
    ];
    $rows = [];
    $buttons = [
        ['_' => 'keyboardButtonCallback', 'text' => "\xf0\x9f\x94\x99", 'data' => json_encode([
            'q' => 'back_to_help', ])],
    ];
    $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons];
    $rows[] = $row;
    $replyInlineMarkup = ['_' => 'replyInlineMarkup', 'rows' => $rows];
    $file = file_get_contents('start_help.json');
    $startj = json_decode($file, true);
    try {
        $default['message'] = $startj['commands_help'][$e][$v];
        $default['reply_markup'] = $replyInlineMarkup;
    } catch (Exception $e) {
        $default['message'] = "Please send /help again. You have selected an old command which is no longer in use";
    }
    if (isset($default['message'])) {
        try {
            $sentMessage = $MadelineProto->messages->sendMessage(
                $default
        );
            \danog\MadelineProto\Logger::log($sentMessage);
        } catch (Exception $e) {
        }
    }
}

function help_menu_callback($update, $MadelineProto)
{
    $parsed_query = parse_query($update, $MadelineProto);
    $peer = $parsed_query['peer'];
    $id = $parsed_query['msg_id'];
    $userid = $parsed_query['user_id'];
    $default = [
        'peer'       => $peer,
        'parse_mode' => 'html',
        'message'    => 'You can navigate the help menu to see each command and how it\'s used. All commands can be started with !, /, or #',
        ];
    $file = file_get_contents('start_help.json');
    $startj = json_decode($file, true);
    $button_list = [];
    foreach ($startj['menus'] as $menu => $desc) {
        $button_list[] =
            ['_' => 'keyboardButtonCallback', 'text' => $menu, 'data' => json_encode([
                'q' => 'help2',
                'v' => "$menu", ])];
    }
    $menu = build_keyboard_callback($button_list, 2);
    $replyInlineMarkup = ['_' => 'replyInlineMarkup', 'rows' => $menu];
    $default['reply_markup'] = $replyInlineMarkup;
    try {
        $sentMessage = $MadelineProto->messages->sendMessage(
            $default
        );
        \danog\MadelineProto\Logger::log($sentMessage);
    } catch (Exception $e) {
    }
}

function moderators_menu_callback($update, $MadelineProto)
{
    $parsed_query = parse_query($update, $MadelineProto);
    $peer = $parsed_query['peer'];
    $id = $parsed_query['msg_id'];
    $ch_id = $parsed_query['data']['c'];
    $userid = $parsed_query['user_id'];
    $info = cache_get_info($update, $MadelineProto, $ch_id, true);
    if ($info) {
        $title = 'of '.htmlentities($info['title']);
    } else {
        $title = '';
    }
    $default = [
        'peer'       => $parsed_query['peer'],
        'id'         => $parsed_query['msg_id'],
        'parse_mode' => 'html',
        'message'    => 'Moderators can be restricted so that their messages are limited just like a regular user. As the owner of the group, you can configure that setting here.',
    ];
    if (!is_chat_owner($update, $MadelineProto, $parsed_query['data']['c'], $parsed_query['user_id'])) {
        try {
            $callbackAnswer = $MadelineProto->messages->setBotCallbackAnswer(['alert'  => true, 'query_id' => $parsed_query['query_id'], 'message' => 'You cannot change the moderation settings of this chat', 'cache_time' => 3]);
            \danog\MadelineProto\Logger::log($callbackAnswer);

            return;
        } catch (Exception $e) {
        }

        return;
    }
    if (is_moderated($ch_id)) {
        if ($parsed_query['data']['v'] == 'on') {
            check_json_array('settings.json', $ch_id);
            $file = file_get_contents('settings.json');
            $settings = json_decode($file, true);
            $settings[$ch_id]['restrict_mods'] = true;
            $text = "Limit moderators \xE2\x9C\x85";
            $from_name = catch_id($update, $MadelineProto, $parsed_query['user_id'])[2];
            if (isset($from_name)) {
                $alert = "<code>The owner $title, $from_name, has restricted moderators. You will no longer be able to send locked messages.</code>";
            }
        } else {
            $text = 'Limit moderators';
        }
        $limiton = ['_' => 'keyboardButtonCallback', 'text' => "$text", 'data' => json_encode([
        'q' => 'moderators',
        'v' => 'on',
        'c' => $ch_id, ])];
        if ($parsed_query['data']['v'] == 'off') {
            check_json_array('settings.json', $ch_id);
            $file = file_get_contents('settings.json');
            $settings = json_decode($file, true);
            $settings[$ch_id]['restrict_mods'] = false;
            $text = "Don't limit moderators \xE2\x9C\x85";
            if (isset($from_name)) {
                $alert = "<code>The owner $title, $from_name, has unrestricted moderators. You can send locked messages once more.</code>";
            }
        } else {
            $text = "Don't limit moderators";
        }
        $limitoff = ['_' => 'keyboardButtonCallback', 'text' => "$text", 'data' => json_encode([
        'q' => 'moderators',
        'v' => 'off',
        'c' => $ch_id, ])];
        $row1 = ['_' => 'keyboardButtonRow', 'buttons' => [$limiton]];
        $row2 = ['_' => 'keyboardButtonRow', 'buttons' => [$limitoff]];
        $back = ['_' => 'keyboardButtonCallback', 'text' => "\xf0\x9f\x94\x99", 'data' => json_encode([
    'q' => 'back_to_settings', // query
    'c' => $ch_id, ])];        // chat
        $row3 = ['_' => 'keyboardButtonRow', 'buttons' => [$back]];
        $replyInlineMarkup = ['_' => 'replyInlineMarkup', 'rows' => [$row1, $row2, $row3]];
        $default['reply_markup'] = $replyInlineMarkup;
        file_put_contents('settings.json', json_encode($settings));
        try {
            $editedMessage = $MadelineProto->messages->editMessage(
                $default
            );
            \danog\MadelineProto\Logger::log($editedMessage);
            if (isset($alert)) {
                alert_moderators($MadelineProto, $ch_id, $alert);
            }
        } catch (Exception $e) {
        }
    }
}

function user_settings_menu($update, $MadelineProto)
{
    $parsed_query = parse_query($update, $MadelineProto);
    $peer = $parsed_query['peer'];
    $id = $parsed_query['msg_id'];
    $ch_id = $parsed_query['data']['c'];
    $userid = $parsed_query['user_id'];
    $default = [
        'peer'       => $parsed_query['peer'],
        'id'         => $parsed_query['msg_id'],
        'parse_mode' => 'html',
        'message'    => 'These settings are specific to you, for this chat only.',
    ];
    if (is_moderated($ch_id)) {
        $rows = [];
        $buttons = [
        ['_' => 'keyboardButtonCallback', 'text' => 'Alerts', 'data' => json_encode([
            'q' => 'alert_me_menu',
            'c' => $ch_id, ])],
        ];
        $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons];
        $rows[] = $row;
        $back = ['_' => 'keyboardButtonCallback', 'text' => "\xf0\x9f\x94\x99", 'data' => json_encode([
    'q' => 'back_to_settings', // query
    'c' => $ch_id, ])];        // chat
        $row = ['_' => 'keyboardButtonRow', 'buttons' => [$back]];
        $rows[] = $row;
        $replyInlineMarkup = ['_' => 'replyInlineMarkup', 'rows' => $rows];
        $default['reply_markup'] = $replyInlineMarkup;
    }
    if (isset($default['message'])) {
        try {
            $sentMessage = $MadelineProto->messages->editMessage(
                $default
            );
            \danog\MadelineProto\Logger::log($sentMessage);
        } catch (Exception $e) {
        }
    }
}

function alert_me_callback($update, $MadelineProto)
{
    $parsed_query = parse_query($update, $MadelineProto);
    $peer = $parsed_query['peer'];
    $id = $parsed_query['msg_id'];
    $ch_id = $parsed_query['data']['c'];
    $userid = $parsed_query['user_id'];
    $default = [
        'peer'       => $parsed_query['peer'],
        'id'         => $parsed_query['msg_id'],
        'parse_mode' => 'html',
        'message'    => 'If you are a moderator in this group you may recieve alerts about actions taken. You can opt to recieve them, or not, here.',
    ];
    if (is_moderated($ch_id)) {
        if ($parsed_query['data']['v'] == 'on') {
            check_json_array('settings.json', $ch_id);
            $file = file_get_contents('settings.json');
            $settings = json_decode($file, true);
            if (!isset($settings[$ch_id][$userid])) {
                $settings[$ch_id][$userid] = [];
            }
            if (!isset($settings[$ch_id][$userid]['alertme'])) {
                $settings[$ch_id][$userid]['alertme'] = true;
            }
            $settings[$ch_id][$userid]['alertme'] = true;
            $text = "Alert me! \xE2\x9C\x85";
        } else {
            $text = 'Alert me.';
        }
        $on = ['_' => 'keyboardButtonCallback', 'text' => "$text", 'data' => json_encode([
        'q' => 'alert_me_cb',
        'v' => 'on',
        'c' => $ch_id, ])];
        if ($parsed_query['data']['v'] == 'off') {
            check_json_array('settings.json', $ch_id);
            $file = file_get_contents('settings.json');
            $settings = json_decode($file, true);
            if (!isset($settings[$ch_id][$userid])) {
                $settings[$ch_id][$userid] = [];
            }
            if (!isset($settings[$ch_id][$userid]['alertme'])) {
                $settings[$ch_id][$userid]['alertme'] = false;
            }
            $settings[$ch_id][$userid]['alertme'] = false;
            $text = "Don't alert me! \xE2\x9C\x85";
        } else {
            $text = "Don't alert me.";
        }
        $off = ['_' => 'keyboardButtonCallback', 'text' => "$text", 'data' => json_encode([
        'q' => 'alert_me_cb',
        'v' => 'off',
        'c' => $ch_id, ])];
        $row1 = ['_' => 'keyboardButtonRow', 'buttons' => [$on]];
        $row2 = ['_' => 'keyboardButtonRow', 'buttons' => [$off]];
        $back = ['_' => 'keyboardButtonCallback', 'text' => "\xf0\x9f\x94\x99", 'data' => json_encode([
    'q' => 'back_to_settings', // query
    'c' => $ch_id, ])];        // chat
        $row3 = ['_' => 'keyboardButtonRow', 'buttons' => [$back]];
        $replyInlineMarkup = ['_' => 'replyInlineMarkup', 'rows' => [$row1, $row2, $row3]];
        $default['reply_markup'] = $replyInlineMarkup;
        file_put_contents('settings.json', json_encode($settings));
        try {
            $editedMessage = $MadelineProto->messages->editMessage(
                $default
            );
            \danog\MadelineProto\Logger::log($editedMessage);
        } catch (Exception $e) {
        }
    }
}

function rules_show_callback($update, $MadelineProto)
{
    $parsed_query = parse_query($update, $MadelineProto);
    $peer = $parsed_query['peer'];
    $id = $parsed_query['msg_id'];
    $ch_id = $parsed_query['data']['c'];
    $userid = $parsed_query['user_id'];
    $default = [
        'peer'       => $parsed_query['peer'],
        'id'         => $parsed_query['msg_id'],
        'parse_mode' => 'html',
        'message'    => 'When I welcome someone, should I give them a link to the rules or not?',
    ];
    if (!is_admin_mod($update, $MadelineProto, $parsed_query['user_id'], false, false, $parsed_query['data']['c'])) {
        try {
            $callbackAnswer = $MadelineProto->messages->setBotCallbackAnswer(['alert'  => true, 'query_id' => $parsed_query['query_id'], 'message' => 'You cannot change the settings of this chat', 'cache_time' => 3]);
            \danog\MadelineProto\Logger::log($callbackAnswer);

            return;
        } catch (Exception $e) {
        }

        return;
    }
    if (is_moderated($ch_id)) {
        if ($parsed_query['data']['v'] == 'on') {
            check_json_array('settings.json', $ch_id);
            $file = file_get_contents('settings.json');
            $settings = json_decode($file, true);
            $settings[$ch_id]['show_rules_welcome'] = true;
            $text = "Show the rules \xE2\x9C\x85";
        } else {
            $text = 'Show the rules';
        }
        $welcomeon = ['_' => 'keyboardButtonCallback', 'text' => "$text", 'data' => json_encode([
        'q' => 'rules_show',
        'v' => 'on',
        'c' => $ch_id, ])];
        if ($parsed_query['data']['v'] == 'off') {
            check_json_array('settings.json', $ch_id);
            $file = file_get_contents('settings.json');
            $settings = json_decode($file, true);
            $settings[$ch_id]['show_rules_welcome'] = false;
            $text = "Don't show the rules \xE2\x9C\x85";
        } else {
            $text = "Don't show the rules";
        }
        $welcomeoff = ['_' => 'keyboardButtonCallback', 'text' => "$text", 'data' => json_encode([
        'q' => 'rules_show',
        'v' => 'off',
        'c' => $ch_id, ])];
        $row1 = ['_' => 'keyboardButtonRow', 'buttons' => [$welcomeon]];
        $row2 = ['_' => 'keyboardButtonRow', 'buttons' => [$welcomeoff]];
        $back = ['_' => 'keyboardButtonCallback', 'text' => "\xf0\x9f\x94\x99", 'data' => json_encode([
    'q' => 'back_to_settings', // query
    'c' => $ch_id, ])];        // chat
        $row3 = ['_' => 'keyboardButtonRow', 'buttons' => [$back]];
        $replyInlineMarkup = ['_' => 'replyInlineMarkup', 'rows' => [$row1, $row2, $row3]];
        $default['reply_markup'] = $replyInlineMarkup;
        file_put_contents('settings.json', json_encode($settings));
        try {
            $editedMessage = $MadelineProto->messages->editMessage(
                $default
            );
            \danog\MadelineProto\Logger::log($editedMessage);
        } catch (Exception $e) {
        }
    }
}
