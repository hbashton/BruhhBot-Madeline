<?php

function bot_present($update, $MadelineProto, $silent = false)
{
    try {
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $uMadelineProto = $MadelineProto->uMadelineProto;
        $api_user = $uMadelineProto->channels->getParticipant(['channel' => $peer, 'user_id' => $MadelineProto->bot_api_id]);
        $bot_user = $uMadelineProto->channels->getParticipant(['channel' => $peer, 'user_id' => $MadelineProto->bot_id]);
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
    return false;
}
