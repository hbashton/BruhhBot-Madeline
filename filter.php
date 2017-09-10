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

function add_filter($update, $MadelineProto, $msg, $name, $user = false)
{
    if (is_supergroup($update, $MadelineProto)) {
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $ch_id = $chat['id'];
        $msg_id = $update['update']['message']['id'];
        $default = [
            'peer'            => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode'      => 'html',
            ];
        $mods = $MadelineProto->responses['add_filter']['mods'];
        if (from_admin_mod($update, $MadelineProto, $mods, true)
        ) {
            if ($name) {
                if ($name == 'from') {
                   if ($msg) {
                        filterfrom($update, $MadelineProto, $msg);
                        return;
                    } else {
                        filterfrom($update, $MadelineProto, false);
                        return;
                    }
                }
            }
            if ($name && $msg) {
                $name = htmlentities(cb($name));
                $msg = base64_encode($msg);
                $codename = "<code>$name</code>";
                check_json_array('filters.json', $ch_id);
                $file = file_get_contents('filters.json');
                $filters = json_decode($file, true);
                if (isset($filters[$ch_id])) {
                    if (!array_key_exists('from', $filters[$ch_id])) {
                        $filters[$ch_id]['from'] = [];
                    }
                    if (array_key_exists($name, $filters[$ch_id]['from'])) {
                        unset($filters[$ch_id]['from'][$name]);
                    }
                    $filters[$ch_id][$name] = $msg;
                    file_put_contents('filters.json', json_encode($filters));
                    $str = $MadelineProto->responses['add_filter']['success'];
                    $repl = [
                        'name' => $name,
                    ];
                    $message = $MadelineProto->engine->render($str, $repl);
                    $default['message'] = $message;
                } else {
                    $filters[$ch_id] = [];
                    $filters[$ch_id]['from'] = [];
                    $filters[$ch_id][$name] = $msg;
                    file_put_contents('filters.json', json_encode($filters));
                    $str = $MadelineProto->responses['add_filter']['success'];
                    $repl = [
                        'name' => $name,
                    ];
                    $message = $MadelineProto->engine->render($str, $repl);
                    $default['message'] = $message;
                }
            } else {
                $message = $MadelineProto->responses['add_filter']['help'];
                $default['message'] = $message;
            }
            if (isset($default['message']) && !$user) {
                $sentMessage = $MadelineProto->messages->sendMessage(
                    $default
                );
            }
            if (isset($sentMessage)) {
                \danog\MadelineProto\Logger::log($sentMessage);
            }
        }
    }
}

function filterfrom($update, $MadelineProto, $name)
{
    $chat = parse_chat_data($update, $MadelineProto);
    $peer = $chat['peer'];
    $ch_id = $chat['id'];
    $replyto = $update['update']['message']['id'];
    $mods = $MadelineProto->responses['filterfrom']['mods'];
    $default = [
        'peer'            => $peer,
        'reply_to_msg_id' => $replyto,
        'parse_mode'      => 'html',
    ];
    if (from_admin_mod($update, $MadelineProto, $mods, true)) {
        if ($name !== 'from') {
            if (array_key_exists('reply_to_msg_id', $update['update']['message'])) {
                $name = cb($name);
                $msg_id = $update['update']['message']['reply_to_msg_id'];
                check_json_array('filters.json', $ch_id);
                $file = file_get_contents('filters.json');
                $filters = json_decode($file, true);
                if (isset($filters[$ch_id])) {
                    if (!array_key_exists('from', $filters[$ch_id])) {
                        $filters[$ch_id]['from'] = [];
                    }
                    $bot_api_id = $MadelineProto->bot_api_id;
                    $file = file_get_contents('settings.json');
                    $settings = json_decode($file, true);
                    if (isset($settings['save_group'])) {
                        try {
                            $forwardMessage = $MadelineProto->messages->forwardMessages(
                                ['from_peer' => $ch_id, 'id' => [$msg_id], 'to_peer' => $settings['save_group']]
                            );
                        } catch (Exception $e) {
                            var_dump($e->getMessage());
                        }
                        foreach ($forwardMessage['updates'] as $i) {
                            if ($i['_'] == 'updateMessageID') {
                                $fwd_id = $i['id'];
                                $fwd_chat = $settings['save_group'];
                            }
                        }
                    } else {
                        $default['message'] = 'The creator of this bot has not set up a group for me to save replied to messages in yet.';
                        $sentMessage = $MadelineProto->messages->sendMessage(
                            $default
                        );
                        \danog\MadelineProto\Logger::log($sentMessage);
                        return;
                    }
                    $filters[$ch_id]['from'][$name] = [];
                    $filters[$ch_id]['from'][$name]['chat'] = $fwd_chat;
                    $filters[$ch_id]['from'][$name]['msgid'] = $fwd_id;
                    if (array_key_exists($name, $filters[$ch_id])) {
                        unset($filters[$ch_id][$name]);
                    }
                    file_put_contents('filters.json', json_encode($filters));
                    $str = $MadelineProto->responses['filterfrom']['success'];
                    $repl = [
                        'name' => $name,
                    ];
                    $message = $MadelineProto->engine->render($str, $repl);
                    $default['message'] = $message;
                } else {
                    $filters[$ch_id] = [];
                    $filters[$ch_id]['from'] = [];
                    $filters[$ch_id]['from'][$name] = [];
                    $filters[$ch_id]['from'][$name]['chat'] = $ch_id;
                    $filters[$ch_id]['from'][$name]['msgid'] = $msg_id;
                    file_put_contents('filters.json', json_encode($filters));
                    $str = $MadelineProto->responses['filterfrom']['success'];
                    $repl = [
                        'name' => $name,
                    ];
                    $message = $MadelineProto->engine->render($str, $repl);
                    $default['message'] = $message;
                }
            } else {
                $message = $MadelineProto->responses['filterfrom']['help'];
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
}

function check_for_filter($update, $MadelineProto, $msg)
{
    if (is_supergroup($update, $MadelineProto)) {
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $ch_id = $chat['id'];
        if (!$update['update']['message']['out']) {
            $msg_id = $update['update']['message']['id'];
            $default = [
                'peer'            => $peer,
                'reply_to_msg_id' => $msg_id,
                'parse_mode'      => 'markdown',
                ];
            if (isset($update['update']['message']['reply_to_msg_id'])) {
                $default['reply_to_msg_id'] = $update['update']['message']['reply_to_msg_id'];
            }
            check_json_array('filters.json', $ch_id);
            $file = file_get_contents('filters.json');
            $filters = json_decode($file, true);
            if (isset($filters[$ch_id])) {
                $contains = contains($msg, array_keys($filters[$ch_id]));
                if (!$contains[0]) {
                    if (array_key_exists('from', $filters[$ch_id])) {
                        $contains = contains($msg, array_keys($filters[$ch_id]['from']));
                        if (!$contains[0]) {
                            return;
                        }
                    }
                }
                if (isset($contains[1])) {
                    $filter = $contains[1];
                } else {
                    return;
                }
                if ($filter !== 'from') {
                    foreach ($filters[$ch_id] as $i => $ii) {
                        if (!is_array($i)) {
                            if ($i == $filter) {
                                if (base64_encode(base64_decode($filters[$ch_id][$i])) === $filters[$ch_id][$i]) {
                                    $message = base64_decode($filters[$ch_id][$i]);
                                } else {
                                    $message = $filters[$ch_id][$i];
                                }
                                $default['message'] = $message;
                            }
                        }
                    }
                }
                if (!isset($message)) {
                    if (array_key_exists('from', $filters[$ch_id])) {
                        foreach ($filters[$ch_id]['from'] as $i => $ii) {
                            if ($i == $filter) {
                                $replyid = $ii['msgid'];
                                $replychat = $ii['chat'];
                                break;
                            }
                        }
                    }
                }
                if (isset($message)) {
                    try {
                        $sentMessage = $MadelineProto->messages->sendMessage(
                            $default
                        );
                    } catch (Exception $e) {
                        var_dump($e->getMessage());
                        $default['message'] = 'HTML of this message formatted incorrectly.';
                        $sentMessage = $MadelineProto->messages->sendMessage(
                            $default
                        );
                    }
                }
                if (isset($replyid)) {
                    try {
                        $sentMessage = $MadelineProto->messages->forwardMessages(
                            ['from_peer' => $replychat, 'id' => [$replyid], 'to_peer' => $peer]
                        );
                    } catch (Exception $e) {
                    }
                }
            }
            if (isset($sentMessage)) {
                \danog\MadelineProto\Logger::log($sentMessage);
            }
        }
    }
}

function clear_filter($update, $MadelineProto, $msg, $user = false)
{
    if (is_supergroup($update, $MadelineProto)) {
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $ch_id = $chat['id'];
        if ($msg !== 'from') {
            $msg_id = $update['update']['message']['id'];
            $default = [
                'peer'            => $peer,
                'reply_to_msg_id' => $msg_id,
                'parse_mode'      => 'html',
                ];
            $mods = 'Why the hell would we let a normal user clear filters?';
            if (from_admin_mod($update, $MadelineProto, $mods, true)
            ) {
                if ($msg) {
                    $msg = htmlentities(cb($msg));
                    check_json_array('filters.json', $ch_id);
                    $file = file_get_contents('filters.json');
                    $filters = json_decode($file, true);
                    if (isset($filters[$ch_id])) {
                        if (!array_key_exists('from', $filters[$ch_id])) {
                            $filters[$ch_id]['from'] = [];
                        }
                        if (array_key_exists($msg, $filters[$ch_id]['from'])) {
                            unset($filters[$ch_id]['from'][$msg]);
                            $message = "Filter <code>$msg</code> was successfully cleared";
                            $default['message'] = $message;
                            file_put_contents('filters.json', json_encode($filters));
                        } elseif (array_key_exists($msg, $filters[$ch_id])) {
                            unset($filters[$ch_id][$msg]);
                            $message = "Filter <code>$msg</code> was successfully cleared";
                            $default['message'] = $message;
                            file_put_contents('filters.json', json_encode($filters));
                        } else {
                            $message = "<code>$msg</code> is not a filter";
                            $default['message'] = $message;
                        }
                    } else {
                        $message = "<code>$msg</code> is not a filter";
                        $default['message'] = $message;
                    }
                } else {
                    $message = 'Use <code>/filter clear name</code> to clear a filter';
                    $default['message'] = $message;
                }
                if (isset($default['message']) && !$user) {
                    $sentMessage = $MadelineProto->messages->sendMessage(
                        $default
                    );
                }
                if (isset($sentMessage)) {
                    \danog\MadelineProto\Logger::log($sentMessage);
                }
            }
        }
    }
}

function get_filters($update, $MadelineProto)
{
    if (is_supergroup($update, $MadelineProto)) {
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = htmlentities($chat['title']);
        $ch_id = $chat['id'];
        $msg_id = $update['update']['message']['id'];
        $default = [
            'peer'            => $peer,
            'reply_to_msg_id' => $msg_id,
            ];
        check_json_array('filters.json', $ch_id);
        $file = file_get_contents('filters.json');
        $filters = json_decode($file, true);
        if (isset($filters[$ch_id])) {
            foreach ($filters[$ch_id] as $i => $ii) {
                if ($i !== 'from') {
                    if (!isset($message)) {
                        $name = cb($i);
                        $message = "Filters in $title: \n";
                        $offset = mb_strlen($message);
                        $entity = create_style('bold', 0, $message);
                        $message .= '[x] '.$name."\n";
                        $length = $offset + strlen($name);
                    } else {
                        $name = cb($i);
                        $message .= '[x] '.$name."\n";
                        $length = $length + strlen($name);
                    }
                }
            }
            if (array_key_exists('from', $filters[$ch_id])) {
                foreach ($filters[$ch_id]['from'] as $i => $ii) {
                    if (!isset($message)) {
                        $name = cb($i);
                        $message = "Filters in $title: \n";
                        $offset = mb_strlen($message);
                        $entity = create_style('bold', 0, $message);
                        $message .= '[x] '.$name."\n";
                        $length = $offset + strlen($name);
                    } else {
                        $name = cb($i);
                        $message .= '[x] '.$name."\n";
                        $length = $length + strlen($name);
                    }
                }
            }
            if (isset($message)) {
                $length = mb_strlen($message) - $offset;
                $default['entities'] = $entity;
                $default['message'] = $message;
                $sentMessage = $MadelineProto->messages->sendMessage(
                    $default
                );
            } else {
                $default['message'] = "There are no filters in $title";
                $offset = mb_strlen($default['message']) - mb_strlen($title);
                $default['entitites'] = create_style('bold', $offset, mb_strlen($title));
                $sentMessage = $MadelineProto->messages->sendMessage(
                    $default
                );
            }
        }
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}