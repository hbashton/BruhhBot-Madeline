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




function create_new_supergroup($update, $MadelineProto, $msg)
{
    if (is_peeruser($update, $MadelineProto)) {
        $userid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
        if (is_master($MadelineProto, $userid)) {
            $uMadelineProto = $MadelineProto->uMadelineProto;
            $msg_id = $update['update']['message']['id'];
            $default = [
            'peer'            => $userid,
            'reply_to_msg_id' => $msg_id,
            'parse_mode'      => 'html',
            ];
            if (preg_match_all('/"([^"]+)"/', $msg, $m)) {
                if (isset($m[1])) {
                    if (isset($m[1][0])) {
                        $title = $m[1][0];
                    } else {
                        $title = false;
                    }
                    if (isset($m[1][1])) {
                        $about = $m[1][1];
                    } else {
                        $about = false;
                    }
                }
                if ($title && $about) {
                    $channelRoleEditor = ['_' => 'channelRoleEditor'];
                    $newgroup = $uMadelineProto->channels->createChannel(
                        ['broadcast' => true,
                        'megagroup'  => true,
                        'title'      => $title,
                        'about'      => $about, ]
                    );
                    $master = cache_get_info(
                        $update,
                        $MadelineProto,
                        getenv('MASTER_USERNAME')
                    )['bot_api_id'];
                    $bot_api_id = $MadelineProto->bot_api_id;
                    $channel_id = -100 .$newgroup['updates'][1]['channel_id'];
                    $invite_master = $uMadelineProto->channels->inviteToChannel(
                        ['channel' => $channel_id,
                        'users'    => [$master, $bot_api_id], ]
                    );
                    \danog\MadelineProto\Logger::log($invite_master);
                    $editadmin = $uMadelineProto->channels->editAdmin(
                        ['channel' => $channel_id,
                        'user_id'  => $master,
                        'role'     => $channelRoleEditor, ]
                    );
                    $editadmin = $uMadelineProto->channels->editAdmin(
                        ['channel' => $channel_id,
                        'user_id'  => $bot_api_id,
                        'role'     => $channelRoleEditor, ]
                    );
                } else {
                    $message = $MadelineProto->responses['create_new_supergroup']['missing_info'];
                    $default['message'] = $message;
                    $sentMessage = $MadelineProto->messages->sendMessage(
                        $default
                    );
                    if (isset($sentMessage)) {
                        \danog\MadelineProto\Logger::log($sentMessage);
                    }
                }
            } else {
                $message = $MadelineProto->responses['create_new_supergroup']['missing_info'];
                $default['message'] = $message;
                $sentMessage = $MadelineProto->messages->sendMessage(
                    $default
                );
                if (isset($sentMessage)) {
                    \danog\MadelineProto\Logger::log($sentMessage);
                }
            }
        }
    }
}

function export_new_invite($update, $MadelineProto)
{
    if (bot_present($update, $MadelineProto)) {
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
                            $uMadelineProto = $MadelineProto->uMadelineProto;
                            $exportInvite = $uMadelineProto->channels->exportInvite(
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
}

function public_toggle($update, $MadelineProto, $msg)
{
    if (bot_present($update, $MadelineProto)) {
        if (is_supergroup($update, $MadelineProto)) {
            $msg_id = $update['update']['message']['id'];
            $mods = $MadelineProto->responses['public_toggle']['mods'];
            $chat = parse_chat_data($update, $MadelineProto);
            $peer = $chat['peer'];
            $ch_id = $chat['id'];
            $default = [
                'peer'            => $peer,
                'reply_to_msg_id' => $msg_id,
                'parse_mode'      => 'html',
                ];
            $arr = ['on', 'off'];
            if (is_moderated($ch_id)) {
                if (is_bot_admin($update, $MadelineProto)) {
                    if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                        if (!empty($msg) && in_array($msg, $arr)) {
                            try {
                                $uMadelineProto = $MadelineProto->uMadelineProto;
                                if ($msg == 'on') {
                                    $uMadelineProto->channels->toggleInvites(
                                        ['channel' => $peer, 'enabled' => true]
                                    );
                                    $message = $MadelineProto->responses['public_toggle']['on'];
                                    $default['message'] = $message;
                                }
                                if ($msg == 'off') {
                                    $uMadelineProto->channels->toggleInvites(
                                        ['channel' => $peer, 'enabled' => false]
                                    );
                                    $message = $MadelineProto->responses['public_toggle']['off'];
                                    $default['message'] = $message;
                                }
                            } catch (Exception $e) {
                                $message = $MadelineProto->responses['public_toggle']['exception'];
                                $default['message'] = $message;
                            }
                        } else {
                            $message = $MadelineProto->responses['public_toggle']['help'];
                            $default['message'] = $message;
                        }
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
}

function invite_user($update, $MadelineProto, $msg)
{
    if (bot_present($update, $MadelineProto)) {
        if (is_supergroup($update, $MadelineProto)) {
            $msg_id = $update['update']['message']['id'];
            $mods = $MadelineProto->responses['invite_user']['mods'];
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
                if (is_bot_admin($update, $MadelineProto)) {
                    if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                        if ($msg) {
                            $id = catch_id($update, $MadelineProto, $msg);
                            if ($id[0]) {
                                $userid = $id[1];
                                $username = $id[2];
                            }
                            $mention = html_mention($username, $userid);
                            if (isset($userid)) {
                                try {
                                    $uMadelineProto = $MadelineProto->uMadelineProto;
                                    $inviteuser = $uMadelineProto->channels->inviteToChannel(
                                        ['channel' => $peer, 'users' => [$userid]]
                                    );
                                    $alert = "<code>$from_name invited $username to $title</code>";
                                } catch (Exception $e) {
                                    $str = $MadelineProto->responses['invite_user']['exception'];
                                    $repl = [
                                        'mention' => $mention,
                                    ];
                                    $message = $MadelineProto->engine->render($str, $repl);
                                    $default['message'] = $message;
                                }
                            } else {
                                $str = $MadelineProto->responses['invite_user']['idk'];
                                $repl = [
                                    'msg' => $msg,
                                ];
                                $message = $MadelineProto->engine->render($str, $repl);
                                $default['message'] = $message;
                            }
                        }
                    } else {
                        $message = $MadelineProto->responses['invite_user']['help'];
                        $default['message'] = $message;
                    }
                }
            }
            if (isset($default['message'])) {
                $sentMessage = $MadelineProto->messages->sendMessage(
                    $default
                );
            }
            if (isset($inviteuser)) {
                \danog\MadelineProto\Logger::log($inviteuser);
                alert_moderators($MadelineProto, $ch_id, $alert);
            }
            if (isset($sentMessage)) {
                \danog\MadelineProto\Logger::log($sentMessage);
            }
        }
    }
}

function import_chat_invite($update, $MadelineProto, $msg)
{
    if (is_peeruser($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $userid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
        $default = [
            'peer'            => $userid,
            'reply_to_msg_id' => $msg_id,
            'parse_mode'      => 'html',
            ];
        if ($msg && preg_match_all('/https:\/\/t.me\/joinchat\//', $msg, $matches)) {
            try {
                $uMadelineProto = $MadelineProto->uMadelineProto;
                $msg = preg_replace('/https:\/\/t.me\/joinchat\//', '', $msg);
                $importchat = $uMadelineProto->messages->importChatInvite(['hash' => $msg]);
                $message = getenv('BOT_USERNAME').' has successfully joined the chat';
                $default['message'] = $message;
            } catch (Exception $e) {
                $message = $MadelineProto->responses['import_chat_invite']['exception'];
                $default['message'] = $message;
            }
        } else {
            $str = $MadelineProto->responses['import_chat_invite']['help'];
            $repl = [
                'botname' => getenv('BOT_USERNAME'),
            ];
            $message = $MadelineProto->engine->render($str, $repl);
            $default['message'] = $message;
        }
        if ($msg && preg_match_all('/@/', $msg, $matches)) {
            try {
                $uMadelineProto = $MadelineProto->uMadelineProto;
                $importchat = $uMadelineProto->channels->joinChannel(['channel' => $msg]);
                $message = getenv('BOT_USERNAME').' has successfully joined the chat';
                $default['message'] = $message;
            } catch (Exception $e) {
                var_dump($e->getMessage());
                $message = $uMadelineProto->responses['import_chat_invite']['exception'];
                $default['message'] = $message;
            }
        }
        if (isset($default['message'])) {
            $sentMessage = $MadelineProto->messages->sendMessage(
                $default
            );
        }
        if (isset($importchat)) {
            \danog\MadelineProto\Logger::log($inviteuser);
        }
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}

function welcome_toggle($update, $MadelineProto)
{
    if (bot_present($update, $MadelineProto)) {
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
}
