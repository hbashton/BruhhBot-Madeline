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


function idme($update, $MadelineProto, $msg = '')
{
    if (is_peeruser($update, $MadelineProto)) {
        $peer = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
        $str = $MadelineProto->responses['idme']['peeruser'];
        $repl = [
            'peer' => $peer,
        ];
        $noid = $MadelineProto->engine->render($str, $repl);
        $cont = true;
    }
    if (is_supergroup($update, $MadelineProto)) {
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = htmlentities($chat['title']);
        $ch_id = $chat['id'];
        $str = $MadelineProto->responses['idme']['supergroup'];
        $repl = [
            'title' => $title,
            'ch_id' => $ch_id,
        ];
        $noid = $MadelineProto->engine->render($str, $repl);
        $cont = true;
    }
    $msg_id = $update['update']['message']['id'];
    if (isset($cont)) {
        $default = [
        'peer'            => $peer,
        'reply_to_msg_id' => $msg_id,
        'parse_mode'      => 'html',
        ];
        if (!empty($msg) or array_key_exists('reply_to_msg_id', $update['update']['message'])) {
            $id = catch_id($update, $MadelineProto, $msg);
            if ($id[0]) {
                $username = $id[2];
                $userid = $id[1];
                $mention = html_mention($username, $userid);
                $str = $MadelineProto->responses['idme']['idmessage'];
                $repl = [
                    'mention' => $mention,
                    'userid'  => $userid,
                ];
                $message = $MadelineProto->engine->render($str, $repl);
                $default['message'] = $message;
            }
            if (!isset($message)) {
                $str = $MadelineProto->responses['idme']['idk'];
                $repl = [
                    'msg' => $msg,
                ];
                $message = $MadelineProto->engine->render($str, $repl);
                $default['message'] = $message;
            }
        } else {
            $message = $noid;
            $default['message'] = $message;
        }
        if (isset($default['message'])) {
            $sentMessage = $MadelineProto->messages->sendMessage(
                $default
            );
        }
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}

function adminlist($update, $MadelineProto)
{
    if (is_supergroup($update, $MadelineProto)) {
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
        $admins = cache_get_chat_info($update, $MadelineProto);
        foreach ($admins['participants'] as $key) {
            if (array_key_exists('user', $key)) {
                $id = $key['user']['id'];
            } else {
                if (array_key_exists('bot', $key)) {
                    $id = $key['bot']['id'];
                }
            }
            $username = catch_id($update, $MadelineProto, $id);
            if (!isset($username[2])) {
                continue;
            }
            $username = $username[2];
            if (array_key_exists('role', $key)) {
                if ($key['role'] == 'moderator'
                    or $key['role'] == 'creator'
                    or $key['role'] == 'editor'
                ) {
                    $mod = true;
                } else {
                    $mod = false;
                }
            } else {
                $mod = false;
            }
            if ($mod) {
                $mention = html_mention($username, $id);
                if (!isset($message)) {
                    $str = $MadelineProto->responses['adminlist']['header'];
                    $repl = [
                        'title' => $title,
                    ];
                    $message = $MadelineProto->engine->render($str, $repl);
                    $message = $message."$mention - $id\r\n";
                } else {
                    $message = $message."$mention - $id\r\n";
                }
            }
        }
        $default['message'] = $message;
        $sentMessage = $MadelineProto->messages->sendMessage(
            $default
        );
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}

function pinmessage($update, $MadelineProto, $silent, $user = false)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $mods = $MadelineProto->responses['pinmessage']['mods'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $ch_id = $chat['id'];
        $title = htmlentities($chat['title']);
        $peer2 = cache_get_info(
            $update,
            $MadelineProto,
            getenv('MASTER_USERNAME')
        )['bot_api_id'];
        $tg_id = str_replace('-100', '', $ch_id);
        $fromid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
        $username = catch_id($update, $MadelineProto, $fromid)[2];
        $mention = html_mention($username, $fromid);
        $default = [
            'peer'            => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode'      => 'html',
        ];
        $default2 = [
            'peer'       => $peer2,
            'parse_mode' => 'html',
        ];
        if (is_moderated($ch_id)) {
            if (is_bot_admin($update, $MadelineProto, true)) {
                if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                    if (array_key_exists(
                        'reply_to_msg_id',
                        $update['update']['message']
                    )
                    ) {
                        try {
                            $pin_id = $update['update']['message']['reply_to_msg_id'];
                            $pin = $MadelineProto->
                            channels->updatePinnedMessage(
                                ['silent' => $silent,
                                'channel' => $peer,
                                'id'      => $pin_id, ]
                            );
                            $message = $MadelineProto->responses['pinmessage']['success'];
                            $default['message'] = $message;
                            \danog\MadelineProto\Logger::log($pin);
                            $message2 = "User $mention pinned a message in <b>$title</b> - $tg_id";
                            if (!$user) {
                                alert_moderators($MadelineProto, $ch_id, $message2);
                                alert_moderators_forward($MadelineProto, $ch_id, $pin_id);
                            }
                        } catch (Exception $e) {
                        }
                    } else {
                        $message = $MadelineProto->responses['pinmessage']['help'];
                        $default['message'] = $message;
                    }
                }
                if (isset($default['message']) && !$user) {
                    $sentMessage = $MadelineProto->messages->sendMessage(
                        $default
                    );
                    \danog\MadelineProto\Logger::log($sentMessage);
                }
            }
        }
    }
}

function delmessage($update, $MadelineProto)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $ch_id = $chat['id'];
        $default = [
            'peer'            => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode'      => 'html',
        ];
        if (is_moderated($ch_id)) {
            if (is_bot_admin($update, $MadelineProto, true)) {
                if (from_admin_mod($update, $MadelineProto)) {
                    if (array_key_exists(
                        'reply_to_msg_id',
                        $update['update']['message']
                    )
                    ) {
                        try {
                            $del_id = $update['update']['message']['reply_to_msg_id'];
                            $delete = $MadelineProto->channels->deleteMessages(
                                ['channel' => $peer,
                                'id'       => [$del_id, $msg_id], ]
                            );
                            \danog\MadelineProto\Logger::log($delete);

                            return;
                        } catch (Exception $e) {
                            var_dump($e->getMessage());
                        }
                    } else {
                        $message = $MadelineProto->responses['delmessage']['help'];
                        $default['message'] = $message;
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
}

function purgemessage($update, $MadelineProto)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $ch_id = $chat['id'];
        $default = [
            'peer'            => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode'      => 'html',
        ];
        $mods = $MadelineProto->responses['purgemessage']['mods'];

        if (is_moderated($ch_id)) {
            if (is_bot_admin($update, $MadelineProto, true)) {
                if (from_admin_mod($update, $MadelineProto)) {
                    if (array_key_exists(
                        'reply_to_msg_id',
                        $update['update']['message']
                    )
                    ) {
                        try {
                            $del_id = $update['update']['message']['reply_to_msg_id'];
                            $default['message'] = "Deleted all messages after $del_id..";
                            $delete = $MadelineProto->channels->deleteMessages(
                                ['channel' => $peer,
                                'id'       => range($del_id, $msg_id), ]
                            );
                            \danog\MadelineProto\Logger::log($delete);

                            return;
                        } catch (Exception $e) {
                            $error = $e->getMessage;
                            $default['message'] = "Purge failed. Error:\n$error";
                        }
                    } else {
                        $message = $MadelineProto->responses['purgemessage']['help'];
                        $default['message'] = $message;
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

function leave_setting($update, $MadelineProto, $msg)
{
    if (is_peeruser($update, $MadelineProto)) {
        $userid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
        if (is_master($MadelineProto, $userid)) {
            $msg_id = $update['update']['message']['id'];
            $default = [
            'peer'            => $userid,
            'reply_to_msg_id' => $msg_id,
            'parse_mode'      => 'html',
            ];
            $arr = ['on', 'off'];
            if ($msg) {
                if (in_array($msg, $arr)) {
                    check_json_array('leave.json', false, false);
                    $file = file_get_contents('leave.json');
                    $leave = json_decode($file, true);
                    if ($msg == 'on') {
                        if (in_array('on', $leave)) {
                            $message = $MadelineProto->responses['leave_setting']['already_on'];
                        } else {
                            if (isset($leave[0])) {
                                unset($leave[0]);
                            }
                            $leave[0] = 'on';
                            $message = $MadelineProto->responses['leave_setting']['on'];
                            $default['message'] = $message;
                        }
                    } else {
                        if (in_array('off', $leave)) {
                            $message = $MadelineProto->responses['leave_setting']['already_off'];
                        } else {
                            if (isset($leave[0])) {
                                unset($leave[0]);
                            }
                            $leave[0] = 'off';
                            $message = $MadelineProto->responses['leave_setting']['off'];
                            $default['message'] = $message;
                        }
                    }
                } else {
                    $message = $MadelineProto->responses['leave_setting']['help'];
                    $default['message'] = $message;
                }
            } else {
                $message = $MadelineProto->responses['leave_setting']['help'];
                $default['message'] = $message;
            }
        } else {
            $message = $MadelineProto->responses['leave_setting']['fuck_off'];
            $default['message'] = $message;
        }
        if (isset($default['message'])) {
            $sentMessage = $MadelineProto->messages->sendMessage(
                $default
            );
        }
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}
function pinalert($update, $MadelineProto)
{
    try {
        $chat = parse_chat_data($update, $MadelineProto);
        $ch_id = $chat['id'];
        if (!is_moderated($ch_id)) {
            return;
        }
        $chatpeer = $chat['peer'];
        $title = htmlentities($chat['title']);
        $msgid = $update['update']['message']['id'];
        $pin_id = $update['update']['message']['reply_to_msg_id'];
        $tg_id = str_replace('-100', '', $ch_id);
        $fromid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
        if ($fromid == $MadelineProto->get_info(getenv('BOT_USERNAME'))['bot_api_id']) {
            return;
        }
        $username = catch_id($update, $MadelineProto, $fromid)[2];
        $mention = html_mention($username, $fromid);
        $message = "User $mention pinned a message in <b>$title</b> - $tg_id";
        alert_moderators($MadelineProto, $ch_id, $message);
        alert_moderators_forward($MadelineProto, $ch_id, $pin_id);
    } catch (Exception $e) {
    }
}

function get_chat_rules($update, $MadelineProto)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $fromid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
        $title = htmlentities($chat['title']);
        $ch_id = $chat['id'];
        $default = [
            'peer'            => $fromid,
            'reply_to_msg_id' => $msg_id,
            'parse_mode'      => 'markdown',
            ];
        if (is_moderated($ch_id)) {
            check_json_array('settings.json', $ch_id);
            $file = file_get_contents('settings.json');
            $settings = json_decode($file, true);
            if (!isset($settings[$ch_id]['rules'])) {
                $settings[$ch_id]['rules'] = '';
            }
            if ($settings[$ch_id]['rules'] != '') {
                $default['message'] = "Rules for $title:\n".$settings[$ch_id]['rules'];
                $bold = create_style('bold', 10, $title);
            } else {
                $default['message'] = "There are no rules for $title";
                $bold = create_style('bold', 23, $title);
            }
            $default['entities'] = $bold;
        }
        if (isset($default['message'])) {
            try {
                $sentMessage = $MadelineProto->messages->sendMessage(
                    $default
                );
            } catch (Exception $e) {
                var_dump($e->getMessage());
                if ($e->getMessage() == 'USER_IS_BLOCKED' or $e->getMessage() == 'PEER_ID_INVALID' or $e->getMessage() == 'The provided peer id is invalid') {
                    if (isset($default['entities'])) {
                        unset($default['entities']);
                    }
                    $default['peer'] = $peer;
                    $botusername = preg_replace('/@/', '', getenv('BOT_API_USERNAME'));
                    $url = "https://telegram.me/$botusername?start=rules-$ch_id";
                    $keyboardButtonUrl = ['_' => 'keyboardButtonUrl', 'text' => 'Get the rules!', 'url' => $url];
                    $buttons = [$keyboardButtonUrl];
                    $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons];
                    $rows = [$row];
                    $replyInlineMarkup = ['_' => 'replyInlineMarkup', 'rows' => $rows];
                    $default['reply_markup'] = $replyInlineMarkup;
                    $default['message'] = "Please start a chat with me so I can send you the rules for $title";
                    $sentMessage = $MadelineProto->messages->sendMessage(
                            $default
                        );
                } else {
                    $default['message'] = 'Rules HTML formatted incorrectly';
                    $default['peer'] = $ch_id;
                    $sentMessage = $MadelineProto->messages->sendMessage(
                            $default
                        );
                    \danog\MadelineProto\Logger::log($sentMessage);
                }
            }
        }
    }
}

function get_chat_rules_deeplink($update, $MadelineProto, $ch_id)
{
    $msg_id = $update['update']['message']['id'];
    $chat = cache_get_info($update, $MadelineProto, $ch_id, true);
    $fromid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
    $title = htmlentities($chat['title']);
    $default = [
        'peer'       => $fromid,
        'parse_mode' => 'markdown',
        ];
    check_json_array('settings.json', $ch_id);
    $file = file_get_contents('settings.json');
    $settings = json_decode($file, true);
    if (!isset($settings[$ch_id]['rules'])) {
        $settings[$ch_id]['rules'] = '';
    }
    if ($settings[$ch_id]['rules'] != '') {
        $default['message'] = "Rules for $title:\n".$settings[$ch_id]['rules'];
        $bold = create_style('bold', 10, $title);
    } else {
        $default['message'] = "There are no rules for $title";
        $bold = create_style('bold', 23, $title);
    }
    $default['entities'] = $bold;
    if (isset($default['message'])) {
        try {
            $sentMessage = $MadelineProto->messages->sendMessage(
                $default
            );
        } catch (Exception $e) {
            try {
                $default['message'] = $default['message'];
                $sentMessage = $MadelineProto->messages->sendMessage(
                    $default
                );
                $settings[$ch_id]['rules'] = $default['message'];
            } catch (Exception $e) {
                $default['message'] = 'Rules HTML formatted incorrectly.';
                $sentMessage = $MadelineProto->messages->sendMessage(
                    $default
                );
            }
        }
    }
}
