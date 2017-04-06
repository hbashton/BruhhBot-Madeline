<?php

function bot_present($update, $MadelineProto, $silent = false)
{
    try {
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        if (array_key_exists($peer, $MadelineProto->is_bot_present)) {
            $diff = time() - $MadelineProto->is_bot_present[$peer]["timestamp"];
            if ($diff < 600) return $MadelineProto->is_bot_present[$peer]["return"];
        }
        $uMadelineProto = $MadelineProto->uMadelineProto;
        $uMadelineProto->messages->setTyping(['peer' => $peer, 'action' => ['_' => 'sendMessageTypingAction']]);
        $MadelineProto->is_bot_present[$peer] = ["timestamp" => time(), "return" => true];
        return true;
    } catch (Exception $e) {
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
        $MadelineProto->is_bot_present[$peer] = ["timestamp" => time(), "return" => true];
        return false;
    }
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
    $MadelineProto->is_bot_present[$peer] = ["timestamp" => time(), "return" => true];
    return false;
}
