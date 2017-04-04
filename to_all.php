<?php
function send_to_moderated($MadelineProto, $msg, $except = []) {
    check_json_array('chatlist.json', false, false);
    $file = file_get_contents("chatlist.json");
    $chatlist = json_decode($file, true);
    foreach ($chatlist as $peer) {
        if (!in_array($peer, $except)) {
            $default = array(
                'peer' => $peer,
                'message' => $msg,
                'parse_mode' => 'html',
            );
            try {
            $sentMessage = $MadelineProto->messages->sendMessage(
                $default
            );
            \danog\MadelineProto\Logger::log($sentMessage);
            } catch (Exception $e) {
                continue;
            }
        }
    }
}

function ban_from_moderated($MadelineProto, $userid, $except = []) {
    check_json_array('chatlist.json', false, false);
    $file = file_get_contents("chatlist.json");
    $chatlist = json_decode($file, true);
    foreach ($chatlist as $peer) {
        if (!in_array($peer, $except)) {
            try {
                $kick = $MadelineProto->
                channels->kickFromChannel(
                    ['channel' => $peer,
                    'user_id' => $userid,
                    'kicked' => true]
                );
                \danog\MadelineProto\Logger::log($kick);
            } catch (Exception $e) {
                continue;
            }
        }
    }
}

function unban_from_moderated($MadelineProto, $userid, $except = []) {
    check_json_array('chatlist.json', false, false);
    $file = file_get_contents("chatlist.json");
    $chatlist = json_decode($file, true);
    foreach ($chatlist as $peer) {
        if (!in_array($peer, $except)) {
            try {
                $kick = $MadelineProto->
                channels->kickFromChannel(
                    ['channel' => $peer,
                    'user_id' => $userid,
                    'kicked' => false]
                );
                \danog\MadelineProto\Logger::log($kick);
            } catch (Exception $e) {
                continue;
            }
        }
    }
}
