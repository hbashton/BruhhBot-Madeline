<?php

function whofile($update, $MadelineProto) {
    
}function adminlist($update, $MadelineProto)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $mods = "Only mods can use me to set this chat's photo!";
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            );
        $fromid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
        $message = "Admins for $title"."\r\n";
        $messageEntityBold = ['_' => 'messageEntityBold', 'offset' => 11,
        'length' => strlen($title) ];
        $admins = cache_get_chat_info($update, $MadelineProto);
        foreach ($admins['participants'] as $key) {
            if (array_key_exists('user', $key)) {
                $id = $key['user']['id'];
            } else {
                if (array_key_exists('bot', $key)) {
                    $id = $key['bot']['id'];
                }
            }
            $adminname = catch_id($update, $MadelineProto, $id)[2];
            if (!isset($entity_)) {
                $offset = strlen($message);
                $entity_ = [['_' => 'inputMessageEntityMentionName', 'offset' =>
                $offset, 'length' => strlen($adminname), 'user_id' =>
                $id]];
                $length = $offset + strlen($adminname) + 2;
                $message = $message.$adminname."\r\n";
            } else {
                $entity_[] = ['_' =>
                'inputMessageEntityMentionName', 'offset' => $length,
                'length' => strlen($adminname), 'user_id' => $id];
                $length = $length + 2 + strlen($adminname);
                $message = $message.$adminname."\r\n";
            }
        }
        $entity = $entity_;
        $entity[] = $messageEntityBold;
        unset($entity_);
        $sentMessage = $MadelineProto->messages->sendMessage(
            ['peer' => $peer, 'reply_to_msg_id' =>
            $msg_id, 'message' => $message,
            'entities' => $entity]
        );
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}