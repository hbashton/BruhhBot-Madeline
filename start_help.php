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
        try {
            if ($update['update']['message']['message'] != "/start") {
                $query = preg_replace("/\/start/", "", $update['update']['message']['message']);
                if (preg_match_all("/settings-/", $query, $out)) {
                    $chat = preg_replace("/ settings-/", "", $query);
                    settings_menu_deeplink($update, $MadelineProto, $chat);
                }
                if (preg_match_all("/rules-/", $query, $out)) {
                    $chat = preg_replace("/ rules-/", "", $query);
                    get_chat_rules_deeplink($update, $MadelineProto, $chat);
                }
                return;
            }
        } catch (Exception $e) {}
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode' => 'html',
            );
        $botname = getenv('BOT_USERNAME');
        $default['message'] = "Hi! I'm a bot made for managing supergroups. To use my functionality, you'll need to add me to your group, and my helper $botname must be there as well (as an admin!). To explore my commands, use /help.";
        if (isset($default['message'])) {
            try {
                $sentMessage = $MadelineProto->messages->sendMessage(
                    $default
                );
            } catch (Exception $e) {}
        }
    }
}

function help_message($update, $MadelineProto)
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
            'message' => 'All commands can be started with !, /, or #'
            );
        $rows = [];
        $rowcount = 0;
        $file = file_get_contents("start_help.json");
        $startj = json_decode($file, true);
        foreach ($startj['menus'] as $menu => $desc) {
             if ($rowcount < 2) {
                 $end = false;
                 $rowcount++;
                 $buttons[] =
                    ['_' => 'keyboardButtonCallback', 'text' => $menu, 'data' => json_encode(array(
                        "q" => "help2",
                        "v" => "$menu",
                        "u" =>  $peer))];
                $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons ];
                $rows[] = $row;
             } else {
                 $end = true;
                 $rowcount = 1;
                 $buttons = [
                    ['_' => 'keyboardButtonCallback', 'text' => $menu, 'data' => json_encode(array(
                        "q" => "help2",
                        "v" => "$menu",
                        "u" =>  $peer))]
                ];
            }
        }
        if ($end) {
            $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons ];
            $rows[] = $row;
        }
        $replyInlineMarkup = ['_' => 'replyInlineMarkup', 'rows' => $rows, ];
        $default['reply_markup'] = $replyInlineMarkup;
    }
    var_dump($default);
    try {
        $sentMessage = $MadelineProto->messages->sendMessage(
            $default
        );
        \danog\MadelineProto\Logger::log($sentMessage);
    } catch (Exception $e) {}
}
