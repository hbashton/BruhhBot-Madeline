<?php

function start_message($update, $MadelineProto)
{
    
    if (is_peeruser($update, $MadelineProto)) {
        $peer = cache_get_info(
            $update,
            $MadelineProto,
            $update['update']['message']['from_id']
        )['bot_api_id'];
        $msg_id = $update['update']['message']['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode' => 'html',
            );
        $message_ = file_get_contents('start_help.html');
        if (strlen($message_) > 4000) {
            $half = intval(strlen($message_) / 2);
            $message = array();
            if (strpos($message_, "\n", $half) !== false) {
                $message[] = substr($message_, 0, strpos($message_, "\n", $half)+1);
                $message[] = substr($message_, strpos($message_, "\n", $half)+1);
            } else {
                $message[] = substr($message_, 0, $half) . '...';
                $message[] = substr($message_, $half);
            }
        } else {
            $message = $message_;
        }
        $default['message'] = $message;
        if (isset($default['message'])) {
            if (is_array($message)) {
                foreach ($message as $value) {
                    $default['message'] = $value;
                    $sentMessage = $MadelineProto->messages->sendMessage(
                        $default
                    );
                }
            } else {
                $sentMessage = $MadelineProto->messages->sendMessage(
                    $default
                );
            }
        }
    }
}