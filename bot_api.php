<?php

function bot_present($update, $MadelineProto, $silent = false, $peer = false, $user = false)
{
    try {
        if (!$peer) {
            $chat = parse_chat_data($update, $MadelineProto);
            $peer = $chat['peer'];
        }
        if (isset($MadelineProto->API->is_bot_present[$peer])) {
            $diff = time() - $MadelineProto->API->is_bot_present[$peer]["timestamp"];
            if ($diff < 600) {
                if (!$MadelineProto->API->is_bot_present[$peer]["return"]) {
                    try {
                        if (!$silent) {
                            $peer = $MadelineProto->get_info($update['update']['message']['to_id'])
                            ['InputPeer'];
                            $msg_id = $update['update']['message']['id'];
                            $str = $MadelineProto->responses['bot_present']['not'];
                            $repl = array(
                                "botname" => getenv('BOT_USERNAME')
                            );
                            $message = $MadelineProto->engine->render($str, $repl);
                            $sentMessage = $MadelineProto->messages->sendMessage(
                                ['peer' => $peer, 'reply_to_msg_id' =>
                                $msg_id, 'message' => $message]
                            );
                            \danog\MadelineProto\Logger::log($sentMessage);
                        }
                    } catch (Exception $e) {}
                    return false;
                } else {
                    return true;
                }
            }
        }
        if (!$user) {
            try {
                $admins = cache_get_chat_info($update, $MadelineProto);
                $peer = $admins['id'];
                $bot_id = $MadelineProto->API->bot_id;
                foreach ($admins['participants'] as $key) {
                    if (array_key_exists('user', $key)) {
                        $id = $key['user']['id'];
                    } else {
                        if (array_key_exists('bot', $key)) {
                            $id = $key['bot']['id'];
                        }
                    }
                    if ($id == $bot_id) {
                        $MadelineProto->API->is_bot_present[$peer] = ["timestamp" => time(), "return" => true];
                        return true;
                    }
                }
            } catch (Exception $e) {}
        } else {
            try {
                $admins = cache_get_chat_info($update, $MadelineProto);
                $peer = $admins['id'];
                $bot_id = $MadelineProto->API->bot_api_id;
                foreach ($admins['participants'] as $key) {
                    if (array_key_exists('user', $key)) {
                        $id = $key['user']['id'];
                    } else {
                        if (array_key_exists('bot', $key)) {
                            $id = $key['bot']['id'];
                        }
                    }
                    if ($id == $bot_id) {
                        $MadelineProto->API->is_bot_present[$peer] = ["timestamp" => time(), "return" => true];
                        return true;
                    }
                }
            } catch (Exception $e) {}
        }
        try {
            if (!$silent) {
                $peer = $MadelineProto->get_info($update['update']['message']['to_id'])
                ['InputPeer'];
                $msg_id = $update['update']['message']['id'];
                $str = $MadelineProto->responses['bot_present']['not'];
                $repl = array(
                    "botname" => getenv('BOT_USERNAME')
                );
                $message = $MadelineProto->engine->render($str, $repl);
                $sentMessage = $MadelineProto->messages->sendMessage(
                    ['peer' => $peer, 'reply_to_msg_id' =>
                    $msg_id, 'message' => $message]
                );
                \danog\MadelineProto\Logger::log($sentMessage);
            }
        } catch (Exception $e) {}
        $MadelineProto->API->is_bot_present[$peer] = ["timestamp" => time(), "return" => false];
        return false;
    } catch (Exception $e) {}
    $MadelineProto->API->is_bot_present[$peer] = ["timestamp" => time(), "return" => false];
    return false;
}
