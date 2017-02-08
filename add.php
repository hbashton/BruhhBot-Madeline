<?php

function add_group($update, $MadelineProto)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $mods = "Can you ask an admin to use this command?";
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            );
        if (from_admin($update, $MadelineProto, $mods, true)) {
            check_json_array('chatlist.json', $ch_id, false);
            $file = file_get_contents("chatlist.json");
            $chatlist = json_decode($file, true);
            if (!in_array($ch_id, $chatlist)) {
                array_push($chatlist, $ch_id);
                file_put_contents('chatlist.json', json_encode($chatlist));
                $message = "$title has been added to my records!".
                " You may now use my full functionality";
                $entity = [['_' => 'messageEntityBold',
                'offset' => 0,
                'length' => strlen($title) ]];
                $default['message'] = $message;
                $default['entities'] = $entity;
            } else {
                $message = "$title is already in my records :)";
                $entity = [['_' => 'messageEntityBold',
                'offset' => 0,
                'length' => strlen($title) ]];
                $default['message'] = $message;
                $default['entities'] = $entity;
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
}

function rm_group($update, $MadelineProto)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $mods = "Can you ask an admin to use this command?";
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            );
        if (from_admin($update, $MadelineProto, $mods, true)) {
            check_json_array('chatlist.json', $ch_id, false);
            $file = file_get_contents("chatlist.json");
            $chatlist = json_decode($file, true);
            if (in_array($ch_id, $chatlist)) {
                if (($key = array_search(
                    $ch_id,
                    $chatlist
                )) !== false
                ) {
                    unset($chatlist[$key]);
                }
                file_put_contents('chatlist.json', json_encode($chatlist));
                $message = "$title has been removed from my records";
                $entity = [['_' => 'messageEntityBold',
                'offset' => 0,
                'length' => strlen($title) ]];
                $default['message'] = $message;
                $default['entities'] = $entity;
            } else {
                $message = "$title is not currently in my records.";
                $entity = [['_' => 'messageEntityBold',
                'offset' => 0,
                'length' => strlen($title) ]];
                $default['message'] = $message;
                $default['entities'] = $entity;
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
}
