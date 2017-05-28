<?php

function bot_present($update, $MadelineProto, $silent = false, $peer = false, $user = false)
{
    try {
        if (!$peer) {
            $chat = parse_chat_data($update, $MadelineProto);
            $peer = $chat['peer'];
        }
        if (isset($MadelineProto->is_bot_present[$peer])) {
            $diff = time() - $MadelineProto->is_bot_present[$peer]['timestamp'];
            if ($diff < 600) {
                if (!$MadelineProto->is_bot_present[$peer]['return']) {
                    if (!$silent) {
                        $peer = $MadelineProto->get_info($update['update']['message']['to_id'])['InputPeer'];
                        $msg_id = $update['update']['message']['id'];
                        $str = $MadelineProto->responses['bot_present']['not'];
                        $repl = [
                            'botname' => getenv('BOT_USERNAME'),
                        ];
                        $message = $MadelineProto->engine->render($str, $repl);
                        $sentMessage = $MadelineProto->messages->sendMessage(
                            ['peer' => $peer, 'reply_to_msg_id' => $msg_id, 'message' => $message]
                        );
                        \danog\MadelineProto\Logger::log($sentMessage);
                    }

                    return false;
                } else {
                    return true;
                }
            }
        }
        if (!$user) {
            $admins = cache_get_chat_info($update, $MadelineProto);
            $peer = $admins['id'];
            $bot_id = $MadelineProto->bot_id;
            foreach ($admins['participants'] as $key) {
                if (array_key_exists('user', $key)) {
                    $id = $key['user']['id'];
                } else {
                    if (array_key_exists('bot', $key)) {
                        $id = $key['bot']['id'];
                    }
                }
                if ($id == $bot_id) {
                    $MadelineProto->is_bot_present[$peer] = ['timestamp' => time(), 'return' => true];

                    return true;
                }
            }
        } else {
            $admins = cache_get_chat_info($update, $MadelineProto);
            $peer = $admins['id'];
            $bot_id = $MadelineProto->bot_api_id;
            foreach ($admins['participants'] as $key) {
                if (array_key_exists('user', $key)) {
                    $id = $key['user']['id'];
                } else {
                    if (array_key_exists('bot', $key)) {
                        $id = $key['bot']['id'];
                    }
                }
                if ($id == $bot_id) {
                    $MadelineProto->is_bot_present[$peer] = ['timestamp' => time(), 'return' => true];

                    return true;
                }
            }
        }
        if (!$silent) {
            $peer = $MadelineProto->get_info($update['update']['message']['to_id'])['InputPeer'];
            $msg_id = $update['update']['message']['id'];
            $str = $MadelineProto->responses['bot_present']['not'];
            $repl = [
                'botname' => getenv('BOT_USERNAME'),
            ];
            $message = $MadelineProto->engine->render($str, $repl);
            $sentMessage = $MadelineProto->messages->sendMessage(
                ['peer' => $peer, 'reply_to_msg_id' => $msg_id, 'message' => $message]
            );
            \danog\MadelineProto\Logger::log($sentMessage);
        }
        $MadelineProto->is_bot_present[$peer] = ['timestamp' => time(), 'return' => false];

        return false;
    } catch (Exception $e) {
        $MadelineProto->is_bot_present[$peer] = ['timestamp' => time(), 'return' => false];

        return false;
    }
    $MadelineProto->is_bot_present[$peer] = ['timestamp' => time(), 'return' => false];

    return false;
}
