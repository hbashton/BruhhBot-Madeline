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

function create_new_supergroup($update, $MadelineProto, $title, $about)
{
    if (is_peeruser($update, $MadelineProto)) {
        $fromid = $update['update']['message']['from_id'];
        $userid = cache_get_info($update, $MadelineProto, $fromid)['bot_api_id'];
        if (is_master($MadelineProto, $userid)) {
            if (!empty($title) && !empty($about)) {
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
                    getenv('TEST_USERNAME')
                )
                ['bot_api_id'];
                var_dump($newgroup);
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
                $message = "You MUST provide a title and description for your ".
                "new group /newgroup title description";
                $entity = [['_' => 'messageEntityCode',
                            'offset' => 70,
                            'length' => 17]];
                $msg_id = $update['update']['message']['id'];
                $sentMessage = $MadelineProto->messages->sendMessage(
                    ['peer' => $userid, 'reply_to_msg_id' =>
                    $msg_id, 'message' => $message, 'entities' => $entity]
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
        $msg_id = $update['update']['message']['id'];
        $mods = "Only mods can get us a new invite link.";
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id
            );
        $fromid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
        if (is_bot_admin($update, $MadelineProto)) {
            if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                try {
                    $exportInvite = $MadelineProto->channels->exportInvite(
                        ['channel' => $peer]
                    );
                    $link = $exportInvite['link'];
                    $message = "The new chat link is $link";
                    $default['message'] = $message;
                    $sentMessage = $MadelineProto->messages->sendMessage(
                        $default
                    );
                    \danog\MadelineProto\Logger::log($sentMessage);
                } catch (Exception $e) {
                    $message = "I am not the owner of this chat. On the bright ".
                    "side, just save the message with my /save command.";
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

function public_toggle($update, $MadelineProto, $msg)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $mods = "I don't listen to you.";
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id
            );
        $arr = ["on", "off"];
        if (is_bot_admin($update, $MadelineProto)) {
            if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                if (!empty($msg) && in_array($msg, $arr)) {
                    try {
                        if ($msg == "on") {
                            $MadelineProto->channels->toggleInvites(
                                ['channel' => $peer, 'enabled' => true ]
                            );
                            $message = "This channel is now public.";
                            $default['message'] = $message;
                        }
                        if ($msg == "off") {
                            $MadelineProto->channels->toggleInvites(
                                ['channel' => $peer, 'enabled' => false ]
                            );
                            $message = "This channel is now private.";
                            $default['message'] = $message;
                        }
                    } catch (Exception $e) {
                        $message = "I couldn't change the public settings";
                        $default['message'] = $message;
                    }
                } else {
                    $message = "Use /public [on/off] to change this settings.";
                    $default['message'] = $message;
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

function invite_user($update, $MadelineProto, $msg_str)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $mods = "Only mods can use me to invite peeps to this cool crib.";
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id
            );
        if (is_bot_admin($update, $MadelineProto)) {
            if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                if ($msg_str) {
                    $id = catch_id($update, $MadelineProto, $msg_str);
                    if ($id[0]) {
                        $userid = $id[1];
                        $username = $id[2];
                    } else {
                        if (!isset($userid)) {
                            $first_char = substr($msg_str, 0, 1);
                            if (preg_match_all('/@/', $first_char, $matches)) {
                                $id = catch_id($update, $MadelineProto, $msg_str);
                                if ($id[0]) {
                                    $userid = $id[1];
                                    $username = $id[2];
                                } else {
                                    $message = "I can't find a user called ".
                                    "$msg_str. Who's that?";
                                    $default['message'] = $message;
                                }
                            } else {
                                $message = "I don't know anyone with the name ".
                                $msg_str;
                                $default['message'] = $message;
                            }
                        }
                    }
                    if (isset($userid)) {
                        $info = cache_get_info(
                            $update,
                            $MadelineProto,
                            $userid
                        );
                        try {
                            $inviteuser = $MadelineProto->channels->inviteToChannel(
                                ['channel' => $peer, 'users' => [$userid] ]
                            );
                        } catch (Exception $e) {
                            $message = "I can't add $username. Either I'm not an ".
                            "admin or their privacy settings prevent me from ".
                            "doing so.";
                            $entity = [[
                                '_' => 'inputMessageEntityMentionName',
                                'offset' => 12,
                                'length' => strlen($username),
                                'user_id' => $userid
                            ]];
                            $default['message'] = $message;
                            $default['entities'] = $entity;
                        }
                    }
                } else {
                    $message = "Use /invite @username to ".
                    "invite someone to this chat!";
                    $code = [['_' => 'messageEntityItalic', 'offset' => 12,
                    'length' => 9]];
                    $default['message'] = $message;
                    $default['entities'] = $code;
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
