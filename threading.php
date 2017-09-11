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



function NewMessage($update, $MadelineProto)
{
    require 'require_exceptions.php';
    if (array_key_exists('message', $update['update']['message'])) {
        if ($update['update']['message']['message'] !== '') {
            $first_char = substr(
                $update['update']['message']['message'][0], 0, 1
            );
            if (preg_match_all('/[\!\#\/]/', $first_char, $matches)) {
                $msg = substr(
                    $update['update']['message']['message'], 1
                );
                $msg_id = $update['update']['message']['id'];
                $fromid = $update['update']['message']['from_id'];
                if ($fromid == $MadelineProto->bot_api_id) {
                    return;
                }
                $default = [
                    'peer'            => $fromid,
                    'reply_to_msg_id' => $msg_id,
                    'parse_mode'      => 'html',
                ];
                check_json_array('gbanlist.json', false, false);
                $file = file_get_contents('gbanlist.json');
                $gbanlist = json_decode($file, true);
                if (array_key_exists($fromid, $gbanlist)) {
                    return;
                }
                $msg_id = $update['update']['message']['id'];
                $botuser = strtolower(getenv('BOT_API_USERNAME'));
                $msg = substr(
                    $update['update']['message']['message'], 1
                );
                $msg_arr = explode(' ', trim($msg));
                $msg = preg_replace("/$botuser/", '', strtolower($msg_arr[0]));
                try {
                    switch (strtolower($msg)) {
                    case 'start':
                        start_message($update, $MadelineProto);
                        break;

                    case 'help':
                        help_message($update, $MadelineProto);
                        break;

                    case 'time':
                        unset($msg_arr[0]);
                        $msg = implode(' ', $msg_arr);
                        gettime($update, $MadelineProto, $msg);
                        break;

                    case 'weather':
                        unset($msg_arr[0]);
                        $msg = implode(' ', $msg_arr);
                        getweather($update, $MadelineProto, $msg);
                        break;

                    case 'id':
                        unset($msg_arr[0]);
                        $msg = implode(' ', $msg_arr);
                        idme($update, $MadelineProto, $msg);
                        break;

                    case 'stats':
                        unset($msg_arr[0]);
                        $msg = implode(' ', $msg_arr);
                        get_user_stats($update, $MadelineProto, $msg);
                        break;

                    case 'save':
                        if (isset($msg_arr[1])) {
                            $name = $msg_arr[1];
                            unset($msg_arr[1]);
                        } else {
                            $name = false;
                        }
                        unset($msg_arr[0]);
                        $msg = implode(' ', $msg_arr);
                        $name_ = strtolower($name);
                        if ($name_ == 'clear') {
                            save_clear($update, $MadelineProto, $msg);
                        } else {
                            saveme($update, $MadelineProto, $msg, $name);
                        }
                        break;

                    case 'saved':
                        saved_get($update, $MadelineProto);
                        break;
                    }
                } catch (Exception $e) {
                }
            }
        }
        if (array_key_exists('fwd_from', $update['update']['message'])) {
            if (array_key_exists('from_id', $update['update']['message']['fwd_from'])) {
                $fwd_id = $update['update']['message']['fwd_from']['from_id'];
                get_user_stats($update, $MadelineProto, $fwd_id);
            }
        }
    }
}

function NewChannelMessage($update, $MadelineProto)
{
    require 'require_exceptions.php';
    $fromid = cache_from_user_info($update, $MadelineProto);
    if (!isset($fromid['bot_api_id'])) {
        return;
    }
    $fromid = $fromid['bot_api_id'];
    if ($fromid == $MadelineProto->bot_api_id) {
        return;
    }
    if (array_key_exists('message', $update['update']['message'])
        && is_string($update['update']['message']['message'])
    ) {
        if (is_supergroup($update, $MadelineProto)) {
            $chat = parse_chat_data($update, $MadelineProto);
            $msg_id = $update['update']['message']['id'];
            $peer = $chat['peer'];
            $ch_id = $chat['id'];
            if (is_moderated($ch_id) && is_supergroup($update, $MadelineProto)) {
                $fromid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
                if (isset($update['update']['message']['via_bot_id'])) {
                    $from_users = [$fromid, $update['update']['message']['via_bot_id']];
                } else {
                    $from_users = [$fromid];
                }
            }
            $chat = parse_chat_data($update, $MadelineProto);
            $peer = $chat['peer'];
            $ch_id = $chat['id'];
            check_json_array('banlist.json', $ch_id);
            $file = file_get_contents('banlist.json');
            $banlist = json_decode($file, true);
            $msg_id = $update['update']['message']['id'];
            if (is_bot_admin($update, $MadelineProto) && !from_admin_mod($update, $MadelineProto)) {
                $default = [
                    'peer'            => $peer,
                    'reply_to_msg_id' => $msg_id,
                ];
                check_json_array('gbanlist.json', false, false);
                $file = file_get_contents('gbanlist.json');
                $gbanlist = json_decode($file, true);
                if (array_key_exists($fromid, $gbanlist) && !is_admin($update, $MadelineProto, $fromid)) {
                    try {
                        $message = "I really don't like them!";
                        $default['message'] = $message;
                        $delete = $MadelineProto->
                        channels->deleteMessages(
                            ['channel' => $peer,
                            'id'       => [$msg_id], ]
                        );
                        $channelBannedRights = ['_' => 'channelBannedRights', 'view_messages' => true, 'until_date' => 999999999];
                        $kick = $MadelineProto->
                        channels->editBanned(
                            ['channel' => $peer,
                            'user_id' => $fromid,
                            'banned_rights' => $channelBannedRights ]
                        );
                        $sentMessage = $MadelineProto->
                        messages->sendMessage(
                            $default
                        );
                        if (isset($kick)) {
                            \danog\MadelineProto\Logger::log($kick);
                        }
                        \danog\MadelineProto\Logger::log(
                            $sentMessage
                        );
                    } catch (Exception $e) {
                    }
                }
                if (isset($banlist[$ch_id])) {
                    if (in_array($fromid, $banlist[$ch_id]) && !is_admin($update, $MadelineProto, $fromid)) {
                        try {
                            $message = 'NO! They are NOT allowed here!';
                            $default['message'] = $message;
                            $delete = $MadelineProto->
                            channels->deleteMessages(
                                ['channel' => $peer,
                                'id'       => [$msg_id], ]
                            );
                            $channelBannedRights = ['_' => 'channelBannedRights', 'view_messages' => true, 'until_date' => 999999999];
                            $kick = $MadelineProto->
                            channels->editBanned(
                                ['channel' => $peer,
                                'user_id' => $fromid,
                                'banned_rights' => $channelBannedRights ]
                            );
                            $sentMessage = $MadelineProto->
                            messages->sendMessage(
                                $default
                            );
                            if (isset($kick)) {
                                \danog\MadelineProto\Logger::log($kick);
                            }
                            \danog\MadelineProto\Logger::log(
                                $sentMessage
                            );
                        } catch (Exception $e) {
                        }
                    }
                }
            }
            if (isset($MadelineProto->from_user_chat_photo)) {
                set_chat_photo($update, $MadelineProto, false);
            }
            if (isset($MadelineProto->wait_for_whoban)) {
                whoban($update, $MadelineProto);
            }
            if (isset($MadelineProto->wait_for_whobanall)) {
                whobanall($update, $MadelineProto);
            }
            if (strlen($update['update']['message']['message']) !== 0) {
                $first_char = substr(
                    $update['update']['message']['message'][0],
                    0, 1
                );
                $msg = $update['update']['message']['message'];
                $msg_arr = explode(' ', trim($msg));
                if ($msg_arr[0] !== "/filter") {
                    check_for_filter($update, $MadelineProto, $msg);
                }
                if (preg_match_all('/#/', $first_char, $matches)) {
                    $msg = substr(
                            $update['update']['message']['message'], 1
                        );
                    $msg_arr = explode(' ', trim($msg));
                    getme($update, $MadelineProto, $msg_arr[0]);
                }
                if (preg_match_all('/[\!\#\/]/', $first_char, $matches)) {
                    $botuser = strtolower(getenv('BOT_API_USERNAME'));
                    $msg = substr(
                        $update['update']['message']['message'], 1
                    );
                    $msg_arr = explode(' ', trim($msg));
                    $msg = preg_replace("/$botuser/", '', strtolower($msg_arr[0]));
                    $msg_id = $update['update']['message']['id'];
                    try {
                        switch ($msg) {
                        case 'time':
                            unset($msg_arr[0]);
                            $msg = implode(' ', $msg_arr);
                            gettime($update, $MadelineProto, $msg);
                            break;

                        case 'weather':
                            unset($msg_arr[0]);
                            $msg = implode(' ', $msg_arr);
                            getweather($update, $MadelineProto, $msg);
                            break;

                        case 'add':
                            add_group($update, $MadelineProto);
                            break;

                        case 'rm':
                            rm_group($update, $MadelineProto);
                            break;

                        case 'adminlist':
                            adminlist($update, $MadelineProto);
                            break;

                        case 'kick':
                            unset($msg_arr[0]);
                            $msg = implode(' ', $msg_arr);
                            kickhim($update, $MadelineProto, $msg);
                            break;

                        case 'kickme':
                            kickme($update, $MadelineProto);
                            break;

                        case 'del':
                            delmessage($update, $MadelineProto);
                            break;

                        case 'purge':
                            purgemessage($update, $MadelineProto);
                            break;

                        case 'ban':
                            unset($msg_arr[0]);
                            $msg = implode(' ', $msg_arr);
                            banme($update, $MadelineProto, $msg);
                            break;

                        case 'banall':
                            unset($msg_arr[0]);
                            if (!empty($msg_arr)) {
                                $last = key(array_slice($msg_arr, -1, 1, true));
                                if ($msg_arr[$last] == 'silent') {
                                    $silent = false;
                                    unset($msg_arr[$last]);
                                } else {
                                    $silent = true;
                                }
                            } else {
                                $silent = true;
                            }
                            if (!empty($msg_arr)) {
                                if (isset($msg_arr[1])) {
                                    $msg = $msg_arr[1];
                                    unset($msg_arr[1]);
                                } else {
                                    $msg = '';
                                }
                            }
                            if ($msg == 'banall') {
                                $msg = '';
                            }
                            $reason = implode(' ', $msg_arr);
                            banall($update, $MadelineProto, $msg, $reason, $silent);
                            break;

                        case 'mute':
                            unset($msg_arr[0]);
                            $msg = implode(' ', $msg_arr);
                            $msg_ = strtolower($msg);
                            muteme($update, $MadelineProto, $msg);
                            break;

                        case 'unmute':
                            unset($msg_arr[0]);
                            $msg = implode(' ', $msg_arr);
                            $msg_ = strtolower($msg);
                            unmuteme($update, $MadelineProto, $msg);
                            break;

                        case 'settings':
                            settings_menu($update, $MadelineProto);
                            break;

                        case 'setflood':
                            unset($msg_arr[0]);
                            $msg = implode(' ', $msg_arr);
                            setflood($update, $MadelineProto, $msg);
                            break;

                        case 'welcome':
                            unset($msg_arr[0]);
                            $msg = implode(' ', $msg_arr);
                            set_chat_welcome($update, $MadelineProto, $msg);
                            break;

                        case 'banlist':
                            getbanlist($update, $MadelineProto);
                            break;

                        case 'gbanlist':
                            getgbanlist($update, $MadelineProto);
                            break;

                        case 'who':
                            wholist($update, $MadelineProto);
                            break;

                        case 'whofile':
                            whofile($update, $MadelineProto);
                            break;

                        case 'whoban':
                            whoban($update, $MadelineProto);
                            break;

                        case 'whobanall':
                            whobanall($update, $MadelineProto);
                            break;

                        case 'unban':
                            unset($msg_arr[0]);
                            $msg = implode(' ', $msg_arr);
                            unbanme($update, $MadelineProto, $msg);
                            break;

                        case 'unbanall':
                            unset($msg_arr[0]);
                            $msg = implode(' ', $msg_arr);
                            unbanall($update, $MadelineProto, $msg);
                            break;

                        case 'stats':
                            unset($msg_arr[0]);
                            $msg = implode(' ', $msg_arr);
                            get_user_stats($update, $MadelineProto, $msg);
                            break;

                        case 'promote':
                            unset($msg_arr[0]);
                            $msg = implode(' ', $msg_arr);
                            promoteme($update, $MadelineProto, $msg);
                            break;

                        case 'demote':
                            unset($msg_arr[0]);
                            $msg = implode(' ', $msg_arr);
                            demoteme($update, $MadelineProto, $msg);
                            break;

                        case 'save':
                            if (isset($msg_arr[1])) {
                                $name = $msg_arr[1];
                                unset($msg_arr[1]);
                            } else {
                                $name = false;
                            }
                            unset($msg_arr[0]);
                            $msg = implode(' ', $msg_arr);
                            $name_ = strtolower($name);
                            if ($name_ == 'clear') {
                                save_clear($update, $MadelineProto, $msg);
                            } else {
                                saveme($update, $MadelineProto, $msg, $name);
                            }
                            break;

                        case 'filter':
                            if (isset($msg_arr[1])) {
                                $name = $msg_arr[1];
                                unset($msg_arr[1]);
                            } else {
                                $name = false;
                            }
                            unset($msg_arr[0]);
                            $msg = implode(' ', $msg_arr);
                            $name_ = strtolower($name);
                            if ($name_ == 'clear') {
                                clear_filter($update, $MadelineProto, $msg);
                            } else {
                                add_filter($update, $MadelineProto, $msg, $name);
                            }
                            break;

                        case 'saved':
                            saved_get($update, $MadelineProto);
                            break;

                        case 'list':
                            get_filters($update, $MadelineProto);
                            break;

                        case 'pin':
                            if (isset($msg_arr[1])) {
                                $msg = $msg_arr[1];
                                $msg_ = strtolower($msg);
                                if ($msg_ = 'silent') {
                                    $silent = true;
                                } else {
                                    $silent = false;
                                }
                            } else {
                                $silent = false;
                            }
                            pinmessage($update, $MadelineProto, $silent);
                            break;

                        case 'id':
                            unset($msg_arr[0]);
                            $msg = implode(' ', $msg_arr);
                            idme($update, $MadelineProto, $msg);
                            break;

                        case 'setphoto':
                            set_chat_photo($update, $MadelineProto);
                            break;

                        case 'setname':
                            unset($msg_arr[0]);
                            $msg = implode(' ', $msg_arr);
                            set_chat_title($update, $MadelineProto, $msg);
                            break;

                        case 'setabout':
                            unset($msg_arr[0]);
                            $msg = implode(' ', $msg_arr);
                            set_chat_about($update, $MadelineProto, $msg);
                            break;

                        case 'setrules':
                            unset($msg_arr[0]);
                            $msg = implode(' ', $msg_arr);
                            set_chat_rules($update, $MadelineProto, $msg);
                            break;

                        case 'rules':
                            unset($msg_arr[0]);
                            $msg = implode(' ', $msg_arr);
                            get_chat_rules($update, $MadelineProto);
                            break;

                        case 'newlink':
                            export_new_invite($update, $MadelineProto);
                            break;

                        case 'lock':
                            if (isset($msg_arr[1])) {
                                $name = strtolower($msg_arr[1]);
                                unset($msg_arr[1]);
                            } else {
                                $name = '';
                            }
                            unset($msg_arr[0]);
                            lockme($update, $MadelineProto, $name);
                            break;

                        case 'unlock':
                            if (isset($msg_arr[1])) {
                                $name = strtolower($msg_arr[1]);
                                unset($msg_arr[1]);
                            } else {
                                $name = '';
                            }
                            unset($msg_arr[0]);
                            unlockme($update, $MadelineProto, $name);
                            break;

                        case 'savedumphere':
                            add_save_group($update, $MadelineProto);
                            break;
                        }
                    } catch (Exception $e) {
                    }
                }
            }
        }
    }
}

function NewChannelMessageAction($update, $MadelineProto)
{
    require 'require_exceptions.php';
    switch ($update['update']['message']['action']['_']) {
    case 'messageActionPinMessage':
        if (!$update['update']['message']['out']) {
            pinalert($update, $MadelineProto);
        }
        break;
    case 'messageActionChatAddUser':
        NewChatAddUser($update, $MadelineProto);
        break;
    case 'messageActionChatJoinedByLink':
        NewChatJoinedByLink($update, $MadelineProto);
        break;
    case 'messageActionChatDeleteUser':
        NewChatDeleteUser($update, $MadelineProto);
        break;
    }
}

function NewChatAddUser($update, $MadelineProto)
{
    $fromid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
    $user_id = $update['update']['message']['action']['users'][0];
    $chat = parse_chat_data($update, $MadelineProto);
    $peer = $chat['peer'];
    if (is_supergroup($update, $MadelineProto)) {
        $id = catch_id(
            $update,
            $MadelineProto,
            $user_id
        );
        if ($id[0]) {
            $username = $id[2];
            $firstname = $id[3];
        }
        $msg_id = $update['update']['message']['id'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = htmlentities($chat['title']);
        $ch_id = $chat['id'];
        $default = [
            'peer'            => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode'      => 'html',
            ];
        $mention = $id[1];
        if (is_moderated($ch_id)) {
            check_json_array('gbanlist.json', false, false);
            $file = file_get_contents('gbanlist.json');
            $gbanlist = json_decode($file, true);
            if (array_key_exists($mention, $gbanlist)) {
                try {
                    $message = "I really don't like them!";
                    $default['message'] = $message;
                    $channelBannedRights = ['_' => 'channelBannedRights', 'view_messages' => true, 'until_date' => 999999999];
                    $kick = $MadelineProto->
                    channels->editBanned(
                        ['channel' => $peer,
                        'user_id' => $mention,
                        'banned_rights' => $channelBannedRights ]
                    );
                    $sentMessage = $MadelineProto->
                    messages->sendMessage(
                        $default
                    );
                    if (isset($kick)) {
                        \danog\MadelineProto\Logger::log($kick);
                    }
                    \danog\MadelineProto\Logger::log(
                        $sentMessage
                    );
                } catch (Exception $e) {
                }
            }
            check_json_array('banlist.json', $ch_id);
            $file = file_get_contents('banlist.json');
            $banlist = json_decode($file, true);
            if (isset($banlist[$ch_id])) {
                if (in_array($mention, $banlist[$ch_id])) {
                    try {
                        $message = 'NO! They are NOT allowed here!';
                        $default['message'] = $message;
                        $channelBannedRights = ['_' => 'channelBannedRights', 'view_messages' => true, 'until_date' => 999999999];
                        $kick = $MadelineProto->
                        channels->editBanned(
                            ['channel' => $peer,
                            'user_id' => $mention,
                            'banned_rights' => $channelBannedRights ]
                        );
                        $sentMessage = $MadelineProto->
                        messages->sendMessage(
                            $default
                        );
                        if (isset($kick)) {
                            \danog\MadelineProto\Logger::log($kick);
                        }
                        \danog\MadelineProto\Logger::log(
                            $sentMessage
                        );
                    } catch (Exception $e) {
                    }
                }
            }
            $bot_api_id = $MadelineProto->bot_api_id;
            if ($mention !== $bot_api_id && empty($default['message'])) {
                check_json_array('settings.json', $ch_id);
                $file = file_get_contents('settings.json');
                $settings = json_decode($file, true);
                if (isset($settings[$ch_id])) {
                    if (!isset($settings[$ch_id]['welcome'])) {
                        $settings[$ch_id]['welcome'] = true;
                    }
                    if ($settings[$ch_id]['welcome']) {
                        $mention2 = html_mention($username, $mention);
                        if (isset($settings[$ch_id]['custom_welcome'])) {
                            $str = $settings[$ch_id]['custom_welcome'];
                            $repl = [
                                'name'     => $firstname,
                                'username' => $username,
                                'mention'  => $mention2,
                                'id'       => $mention,
                                'title'    => $title,
                            ];
                            $message = $MadelineProto->engine->render($str, $repl);
                        } else {
                            $message = "Hi $mention2, welcome to <b>$title</b>";
                        }
                        if (isset($settings[$ch_id]['show_rules_welcome'])) {
                            if ($settings[$ch_id]['show_rules_welcome']) {
                                $botusername = preg_replace('/@/', '', getenv('BOT_API_USERNAME'));
                                $url = "https://telegram.me/$botusername?start=rules-$ch_id";
                                $message = "[Rules](buttonurl:$url) $message";
                            }
                        }
                        $default['message'] = $message;
                        $default['no_webpage'] = true;
                        $default['parse_mode'] = 'markdown';
                        try {
                            $sentMessage = $MadelineProto->
                            messages->sendMessage($default);
                            \danog\MadelineProto\Logger::log(
                                $sentMessage
                            );
                        } catch (Exception $e) {
                        }
                    }
                }
            }
        }
    }
}

function NewChatJoinedByLink($update, $MadelineProto)
{
    $user_id = $update['update']['message']['from_id'];
    $chat = parse_chat_data($update, $MadelineProto);
    $peer = $chat['peer'];
    if (is_supergroup($update, $MadelineProto)) {
        $id = catch_id(
            $update,
            $MadelineProto,
            $user_id
        );
        if ($id[0]) {
            $username = $id[2];
            $mention = $id[1];
            $firstname = $id[3];
        }
        $msg_id = $update['update']['message']['id'];
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
            check_json_array('banlist.json', $ch_id);
            $file = file_get_contents('banlist.json');
            $banlist = json_decode($file, true);
            check_json_array('gbanlist.json', false, false);
            $file = file_get_contents('gbanlist.json');
            $gbanlist = json_decode($file, true);
            if (array_key_exists($mention, $gbanlist)) {
                try {
                    $message = "I really don't like them!";
                    $default['message'] = $message;
                    $channelBannedRights = ['_' => 'channelBannedRights', 'view_messages' => true, 'until_date' => 999999999];
                    $kick = $MadelineProto->
                    channels->editBanned(
                        ['channel' => $peer,
                        'user_id' => $mention,
                        'banned_rights' => $channelBannedRights ]
                    );
                    $sentMessage = $MadelineProto->
                    messages->sendMessage(
                        $default
                    );
                    if (isset($kick)) {
                        \danog\MadelineProto\Logger::log($kick);
                    }
                    \danog\MadelineProto\Logger::log(
                        $sentMessage
                    );
                } catch (Exception $e) {
                }
            }
            if (isset($banlist[$ch_id])) {
                if (in_array($mention, $banlist[$ch_id])) {
                    try {
                        $message = 'NO! They are NOT allowed here!';
                        $default['message'] = $message;
                        $channelBannedRights = ['_' => 'channelBannedRights', 'view_messages' => true, 'until_date' => 999999999];
                        $kick = $MadelineProto->
                        channels->editBanned(
                            ['channel' => $peer,
                            'user_id' => $mention,
                            'banned_rights' => $channelBannedRights ]
                        );
                        $sentMessage = $MadelineProto->
                        messages->sendMessage(
                            $default
                        );
                        if (isset($kick)) {
                            \danog\MadelineProto\Logger::log($kick);
                        }
                        \danog\MadelineProto\Logger::log(
                            $sentMessage
                        );
                    } catch (Exception $e) {
                    }
                }
            }
            if (empty($default['message'])) {
                check_json_array('settings.json', $ch_id);
                $file = file_get_contents('settings.json');
                $settings = json_decode($file, true);
                if (isset($settings[$ch_id])) {
                    if (!isset($settings[$ch_id]['welcome'])) {
                        $settings[$ch_id]['welcome'] = true;
                    }
                    if ($settings[$ch_id]['welcome']) {
                        $mention2 = html_mention($username, $mention);
                        if (isset($settings[$ch_id]['custom_welcome'])) {
                            $str = $settings[$ch_id]['custom_welcome'];
                            $repl = [
                                'name'     => $firstname,
                                'username' => $username,
                                'mention'  => $mention2,
                                'id'       => $mention,
                                'title'    => $title,
                            ];
                            $message = $MadelineProto->engine->render($str, $repl);
                        } else {
                            $message = "Hi $mention2, welcome to <b>$title</b>";
                        }
                        if (isset($settings[$ch_id]['show_rules_welcome'])) {
                            if ($settings[$ch_id]['show_rules_welcome']) {
                                $botusername = preg_replace('/@/', '', getenv('BOT_API_USERNAME'));
                                $url = "https://telegram.me/$botusername?start=rules-$ch_id";
                                $message = "[Rules](buttonurl:$url) $message";
                            }
                        }
                        $default['message'] = $message;
                        $default['no_webpage'] = true;
                        $default['parse_mode'] = 'markdown';
                        try {
                            $sentMessage = $MadelineProto->
                            messages->sendMessage($default);
                            \danog\MadelineProto\Logger::log(
                                $sentMessage
                            );
                        } catch (Exception $e) {
                        }
                    }
                }
            }
        }
    }
}

function NewChatDeleteUser($update, $MadelineProto)
{
    $chat = parse_chat_data($update, $MadelineProto);
    $peer = $chat['peer'];
    $user_id = $update['update']['message']['action']['user_id'];
    if (is_supergroup($update, $MadelineProto)) {
        $id = catch_id(
            $update,
            $MadelineProto,
            $user_id
        );
        if ($id[0]) {
            $username = $id[2];
            $mention = $id[1];
        }
        $msg_id = $update['update']['message']['id'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = htmlentities($chat['title']);
        $ch_id = $chat['id'];
        if (is_moderated($ch_id)) {
            if (empty($default['message'])) {
                $userid = $update['update']['message']['action']['user_id'];
                $entity = create_mention(16, $username, $mention);
                $default = [
                'peer'            => $peer,
                'reply_to_msg_id' => $msg_id,
                'entities'        => $entity,
                ];
                $id = catch_id($update, $MadelineProto, $userid);
                if ($id[0]) {
                    $username = $id[2];
                    $mention = $id[1];
                }
                try {
                    $message = "Nice knowing ya $username";
                    $default['message'] = $message;
                    $sentMessage = $MadelineProto->
                    messages->sendMessage(
                        $default
                    );
                    \danog\MadelineProto\Logger::log(
                        $sentMessage
                    );
                } catch (Exception $e) {
                }
            }
        }
    }
}

function BotAPIUpdates($updates, $MadelineProto)
{
    require 'require_exceptions.php';
    foreach ($updates as $update) {
        switch ($update['update']['_']) {
        case 'updateNewMessage':
            if (is_peeruser($update, $MadelineProto)) {
                NewMessage($update, $MadelineProto);
            }
        break;
        case 'updateNewChannelMessage':
            if (is_supergroup($update, $MadelineProto)) {
                check_locked($update, $MadelineProto);
                check_flood($update, $MadelineProto);
                NewChannelMessage($update, $MadelineProto);
                if (array_key_exists('action', $update['update']['message'])) {
                    NewChannelMessageAction($update, $MadelineProto);
                }
            }
        break;
        case 'updateBotCallbackQuery':
            if (is_supergroup($update, $MadelineProto) or is_peeruser($update, $MadelineProto)) {
                BotCallbackQuery($update, $MadelineProto);
            }
        }
    }
    \danog\MadelineProto\Serialization::serialize('bot.madeline', $MadelineProto).PHP_EOL;
}
