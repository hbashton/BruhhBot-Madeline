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
                    try {
                        $default['message'] = $value;
                        $sentMessage = $MadelineProto->messages->sendMessage(
                            $default
                        );
                    } catch (Exception $e) {}
                }
            } else {
                try {
                    $sentMessage = $MadelineProto->messages->sendMessage(
                        $default
                    );
                } catch (Exception $e) {}
            }
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
             if ($rowcount < 2 && $rowcount > 0) {
                 $end = false;
                 $rowcount = 0;
                 $buttons[] =
                    ['_' => 'keyboardButtonCallback', 'text' => $menu, 'data' => json_encode(array(
                        "q" => "help2",
                        "v" => "$menu",
                        "u" =>  $peer))];
                $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons ];
                $rows[] = $row;
             } else {
                 $end = true;
                 $rowcount++;
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
