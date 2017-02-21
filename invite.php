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

function create_new_supergroup($update, $MadelineProto, $msg)
{
    if (is_peeruser($update, $MadelineProto)) {
        global $responses, $engine;
        $userid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
        if (is_master($MadelineProto, $userid)) {
            $msg_id = $update['update']['message']['id'];
            $default = array(
            'peer' => $userid,
            'reply_to_msg_id' => $msg_id,
            'parse_mode' => 'html'
            );
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
                    $channelRoleModerator = ['_' => 'channelRoleModerator', ];
                    $newgroup = $MadelineProto->channels->createChannel(
                        ['broadcast' => true,
                        'megagroup' => true,
                        'title' => $title,
                        'about' => $about ]
                    );
                    $master = cache_get_info(
                        $update,
                        $MadelineProto,
                        getenv('MASTER_USERNAME')
                    )
                    ['bot_api_id'];
                    $channel_id = -100 . $newgroup['updates'][1]['channel_id'];
                    $invite_master = $MadelineProto->channels->inviteToChannel(
                        ['channel' => $channel_id,
                        'users' => [$master]]
                    );
                    \danog\MadelineProto\Logger::log($invite_master);
                    $editadmin = $MadelineProto->channels->editAdmin(
                        ['channel' => $channel_id,
                        'user_id' => $master,
                        'role' => $channelRoleModerator]
                    );
                } else {
                    $message = $responses['create_new_supergroup']['missing_info'];
                    $default['message'] = $message;
                    $sentMessage = $MadelineProto->messages->sendMessage(
                        $default
                    );
                    if (isset($sentMessage)) {
                        \danog\MadelineProto\Logger::log($sentMessage);
                    }
                }
            } else {
                $message = $responses['create_new_supergroup']['missing_info'];
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
    if (is_supergroup($update, $MadelineProto)) {
        global $responses, $engine;
        $msg_id = $update['update']['message']['id'];
        $mods = $responses['export_new_invite']['mods'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode' => 'html'
            );
        if (is_moderated($ch_id)) {
            if (is_bot_admin($update, $MadelineProto)) {
                if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                    try {
                        $exportInvite = $MadelineProto->channels->exportInvite(
                            ['channel' => $peer]
                        );
                        $link = $exportInvite['link'];
                        $str = $responses['export_new_invite']['link'];
                        $repl = array(
                            "link" => $link
                        );
                        $message = $engine->render($str, $repl);
                        $default['message'] = $message;
                        $sentMessage = $MadelineProto->messages->sendMessage(
                            $default
                        );
                        \danog\MadelineProto\Logger::log($sentMessage);
                    } catch (Exception $e) {
                        $message = $responses['export_new_invite']['exception'];
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

function public_toggle($update, $MadelineProto, $msg)
{
    if (is_supergroup($update, $MadelineProto)) {
        global $responses, $engine;
        $msg_id = $update['update']['message']['id'];
        $mods = $responses['public_toggle']['mods'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode' => 'html'
            );
        $arr = ["on", "off"];
        if (is_moderated($ch_id)) {
            if (is_bot_admin($update, $MadelineProto)) {
                if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                    if (!empty($msg) && in_array($msg, $arr)) {
                        try {
                            if ($msg == "on") {
                                $MadelineProto->channels->toggleInvites(
                                    ['channel' => $peer, 'enabled' => true ]
                                );
                                $message = $responses['public_toggle']['on'];
                                $default['message'] = $message;
                            }
                            if ($msg == "off") {
                                $MadelineProto->channels->toggleInvites(
                                    ['channel' => $peer, 'enabled' => false ]
                                );
                                $message = $responses['public_toggle']['off'];
                                $default['message'] = $message;
                            }
                        } catch (Exception $e) {
                            $message = $responses['public_toggle']['exception'];
                            $default['message'] = $message;
                        }
                    } else {
                        $message = $responses['public_toggle']['help'];
                        $default['message'] = $message;
                    }
                }
            }
        }
        if (isset($default)) {
            $sentMessage = $MadelineProto->messages->sendMessage(
                $default
            );
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}

function invite_user($update, $MadelineProto, $msg)
{
    if (is_supergroup($update, $MadelineProto)) {
        global $responses, $engine;
        $msg_id = $update['update']['message']['id'];
        $mods = $responses['invite_user']['mods'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode' => 'html'
            );
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
                                $inviteuser = $MadelineProto->channels->inviteToChannel(
                                    ['channel' => $peer, 'users' => [$userid] ]
                                );
                            } catch (Exception $e) {
                                $str = $responses['invite_user']['exception'];
                                $repl = array(
                                    "mention" => $mention
                                );
                                $message = $engine->render($str, $repl);
                                $default['message'] = $message;
                            }
                        } else {
                            $str = $responses['invite_user']['idk'];
                            $repl = array(
                                "msg" => $msg
                            );
                            $message = $engine->render($str, $repl);
                            $default['message'] = $message;
                        }
                    } else {
                        $message = $responses['invite_user']['help'];
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
        if (isset($inviteuser)) {
            \danog\MadelineProto\Logger::log($inviteuser);
        }
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}
