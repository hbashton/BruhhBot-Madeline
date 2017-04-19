<?php

function welcome_callback($update, $MadelineProto)
{
    $parsed_query = parse_query($update, $MadelineProto);
    $peer = $parsed_query['peer'];
    $id = $parsed_query['msg_id'];
    $ch_id = $parsed_query['data']['c'];
    $userid = $parsed_query['user_id'];
    $default = array(
        'peer' => $parsed_query['peer'],
        'id' => $parsed_query['msg_id'],
        'parse_mode' => 'html',
        'message' => "If you ask me to, I will greet new people!"
    );
    if ($parsed_query['data']['u'] != $parsed_query['user_id'] or !is_admin_mod($update, $MadelineProto, $parsed_query['user_id'], false, false, $parsed_query['data']['c'])) return;
    if (is_moderated($ch_id)) {
        if ($parsed_query['data']['v'] == "on") {
            check_json_array('settings.json', $default['peer']);
            $file = file_get_contents("settings.json");
            $settings = json_decode($file, true);
            $settings[$default['peer']]["welcome"] = true;
            $text = "Welcome new users \xE2\x9C\x85";
        } else {
            $text = "Welcome new users";
        }
        $welcomeon = ['_' => 'keyboardButtonCallback', 'text' => "$text", 'data' => json_encode(array(
        "q" => "welcome",
        "v" => "on",
        "u" =>  $userid,
        "c" =>  $ch_id))]; 
        if ($parsed_query['data']['v'] == "off") {
            check_json_array('settings.json', $default['peer']);
            $file = file_get_contents("settings.json");
            $settings = json_decode($file, true);
            $settings[$default['peer']]["welcome"] = false;
            $text = "Don't welcome new users \xE2\x9C\x85";
        } else {
            $text = "Don't welcome new users";
        }
        $welcomeoff = ['_' => 'keyboardButtonCallback', 'text' => "$text", 'data' => json_encode(array(
        "q" => "welcome",
        "v" => "off",
        "u" =>  $userid,
        "c" =>  $ch_id))];
        $row1 = ['_' => 'keyboardButtonRow', 'buttons' => [$welcomeon], ];
        $row2 = ['_' => 'keyboardButtonRow', 'buttons' => [$welcomeoff], ];
        $back = ['_' => 'keyboardButtonCallback', 'text' => "\xf0\x9f\x94\x99", 'data' => json_encode(array(
    "q" => "back_to_settings", // query
    "u" =>  $userid,           // user
    "c" =>  $ch_id ))];        // chat
        $row3 = ['_' => 'keyboardButtonRow', 'buttons' => [$back] ];
        $replyInlineMarkup = ['_' => 'replyInlineMarkup', 'rows' => [$row1, $row2, $row3], ];
        $default['reply_markup'] = $replyInlineMarkup;
        file_put_contents('settings.json', json_encode($settings));
        try {
            $editedMessage = $MadelineProto->messages->editMessage(
                $default
            );
            \danog\MadelineProto\Logger::log($editedMessage);
        } catch (Exception $e) {}
    }
}

function lock_callback($update, $MadelineProto)
{
    $parsed_query = parse_query($update, $MadelineProto);
    $peer = $parsed_query['peer'];
    $id = $parsed_query['msg_id'];
    $ch_id = $parsed_query['data']['c'];
    $userid = $parsed_query['user_id'];
    $message = $MadelineProto->channels->getMessages(['channel' => $peer, 'id' => [$id]]);
    if (!is_array($message)) return;
    $message = $message['messages'][0]['message'];
    $default = array(
        'peer' => $parsed_query['peer'],
        'id' => $parsed_query['msg_id'],
        'parse_mode' => 'html',
        'message' => $message
    );
    if ($parsed_query['data']['u'] != $parsed_query['user_id'] or !is_admin_mod($update, $MadelineProto, $parsed_query['user_id'], false, false, $parsed_query['data']['c'])) return;
    $val = $parsed_query['data']['v'];
    $file = file_get_contents("locked.json");
    $locked = json_decode($file, true);
    if (!isset($locked[$ch_id])) {
        $locked[$ch_id] = [];
    }
    if (preg_match_all('/-on/', $val, $matches)) {
        var_dump(true);
        $val = preg_replace("/-on/", "", $val);
        $newtext = "\xE2\x9D\x8C";
        $newonoff = "off";
        array_push($locked[$ch_id], $val);
    } 
    if (preg_match_all('/-off/', $val, $matches)) {
        $val = preg_replace("/-off/", "", $val);
        $newtext = "\xE2\x9C\x85";
        $newonoff = "on";
        if (($key = array_search(
            $val,
            $locked[$ch_id]
        )) !== false
        ) {
            unset($locked[$ch_id][$key]);
        }
    }
    $rows = [];
    $coniguration = file_get_contents("configuration.json");
    $cfg = json_decode($coniguration, true);
    foreach ($cfg['settings_template'] as $key => $value) {
        // check mark \xE2\x9C\x85
        // cross mark \xE2\x9D\x8C
        if (in_array($key, $locked[$ch_id])) {
            $text = "\xE2\x9D\x8C";
            $onoff = "off";
        } else {
            $text = "\xE2\x9C\x85";
            $onoff = "on";
        }
        $buttons = [
            ['_' => 'keyboardButtonCallback', 'text' => $value, 'data' => json_encode(array(
                "q" => "hint",       // query
                "v" => "$key",       // value
                "u" =>  $userid))],
            ['_' => 'keyboardButtonCallback', 'text' => $text, 'data' => json_encode(array(
                "q" => "lock",       // query
                "v" => "$key-$onoff",// value
                "u" =>  $userid,     // user
                "c" =>  $ch_id ))]   // chat
        ];
        $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons ];
        $rows[] = $row;
    }
    $buttons = [
            ['_' => 'keyboardButtonCallback', 'text' => "\xe2\xac\x85\xef\xb8\x8f", 'data' => json_encode(array(
                "q" => "decrease_flood",   // query
                "u" =>  $userid,           // user
                "c" =>  $ch_id ))],        // chat
            ['_' => 'keyboardButtonCallback', 'text' => (string) $locked[$ch_id]['floodlimit'], 'data' => json_encode(array(
                "q" => "hint",             // query
                "u" =>  $userid,           // user
                "v" => "flood"))],
            ['_' => 'keyboardButtonCallback', 'text' => "\xe2\x9e\xa1\xef\xb8\x8f", 'data' => json_encode(array(
                "q" => "increase_flood",   // query
                "u" =>  $userid,           // user
                "c" =>  $ch_id ))],        // chat
        ];
    $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons ];
    $rows[] = $row;
    $buttons = [
        ['_' => 'keyboardButtonCallback', 'text' => "\xf0\x9f\x94\x99", 'data' => json_encode(array(
            "q" => "back_to_settings", // query
            "u" =>  $userid,           // user
            "c" =>  $ch_id ))]         // chat
    ];
    $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons ];
    $rows[] = $row;
    $replyInlineMarkup = ['_' => 'replyInlineMarkup', 'rows' => $rows, ];
    $default['reply_markup'] = $replyInlineMarkup;
    file_put_contents('locked.json', json_encode($locked));
    try {
        $editedMessage = $MadelineProto->messages->editMessage(
            $default
        );
        \danog\MadelineProto\Logger::log($editedMessage);
    } catch (Exception $e) {}
}

function increment_flood($update, $MadelineProto, $up = false)
{
    $parsed_query = parse_query($update, $MadelineProto);
    $peer = $parsed_query['peer'];
    $id = $parsed_query['msg_id'];
    $ch_id = $parsed_query['data']['c'];
    $userid = $parsed_query['user_id'];
    $message = $MadelineProto->channels->getMessages(['channel' => $peer, 'id' => [$id]]);
    if (!is_array($message)) return;
    $message = $message['messages'][0]['message'];
    $default = array(
        'peer' => $parsed_query['peer'],
        'id' => $parsed_query['msg_id'],
        'parse_mode' => 'html',
        'message' => $message
    );
    if ($parsed_query['data']['u'] != $parsed_query['user_id'] or !is_admin_mod($update, $MadelineProto, $parsed_query['user_id'], false, false, $parsed_query['data']['c'])) return;
    $file = file_get_contents("locked.json");
    $locked = json_decode($file, true);
    if ($up) {
        $locked[$ch_id]['floodlimit'] += 1;
    } else {
        $locked[$ch_id]['floodlimit'] -= 1;
    }
    if ($locked[$ch_id]['floodlimit'] <= 1) {
        try {
            $callbackAnswer = $MadelineProto->messages->setBotCallbackAnswer(['alert'  => true, 'query_id' => $parsed_query['query_id'], 'message' => "You can't make the floodlimit 1 or below", 'cache_time' => 3, ]);
            \danog\MadelineProto\Logger::log($callbackAnswer);
        } catch (Exception $e) {}
        return;
    }
    $rows = [];
    $coniguration = file_get_contents("configuration.json");
    $cfg = json_decode($coniguration, true);
    foreach ($cfg['settings_template'] as $key => $value) {
        // check mark \xE2\x9C\x85
        // cross mark \xE2\x9D\x8C
        if (in_array($key, $locked[$ch_id])) {
            $text = "\xE2\x9D\x8C";
            $onoff = "off";
        } else {
            $text = "\xE2\x9C\x85";
            $onoff = "on";
        }
        $buttons = [
            ['_' => 'keyboardButtonCallback', 'text' => $value, 'data' => json_encode(array(
                "q" => "hint",       // query
                "v" => "$key",       // value
                "u" =>  $userid ))],
            ['_' => 'keyboardButtonCallback', 'text' => $text, 'data' => json_encode(array(
                "q" => "lock",       // query
                "v" => "$key-$onoff",// value
                "u" =>  $userid,     // user
                "c" =>  $ch_id ))]   // chat
        ];
        $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons ];
        $rows[] = $row;
    }
    $buttons = [
            ['_' => 'keyboardButtonCallback', 'text' => "\xe2\xac\x85\xef\xb8\x8f", 'data' => json_encode(array(
                "q" => "decrease_flood",   // query
                "u" =>  $userid,           // user
                "c" =>  $ch_id ))],        // chat
            ['_' => 'keyboardButtonCallback', 'text' => (string) $locked[$ch_id]['floodlimit'], 'data' => json_encode(array(
                "q" => "hint",             // query
                "u" =>  $userid,           // user
                "v" => "flood" ))],        // chat
            ['_' => 'keyboardButtonCallback', 'text' => "\xe2\x9e\xa1\xef\xb8\x8f", 'data' => json_encode(array(
                "q" => "increase_flood",   // query
                "u" =>  $userid,           // user
                "c" =>  $ch_id ))],        // chat
        ];
    $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons ];
    $rows[] = $row;
    $buttons = [
        ['_' => 'keyboardButtonCallback', 'text' => "\xf0\x9f\x94\x99", 'data' => json_encode(array(
            "q" => "back_to_settings", // query
            "u" =>  $userid,           // user
            "c" =>  $ch_id ))]         // chat
    ];
    $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons ];
    $rows[] = $row;
    $replyInlineMarkup = ['_' => 'replyInlineMarkup', 'rows' => $rows, ];
    $default['reply_markup'] = $replyInlineMarkup;
    file_put_contents('locked.json', json_encode($locked));
    try {
        $editedMessage = $MadelineProto->messages->editMessage(
            $default
        );
        \danog\MadelineProto\Logger::log($editedMessage);
    } catch (Exception $e) {}
}

function alert_hint($update, $MadelineProto, $up = false)
{
    $parsed_query = parse_query($update, $MadelineProto);
    $peer = $parsed_query['peer'];
    $id = $parsed_query['msg_id'];
    $userid = $parsed_query['user_id'];
    $v = $parsed_query['data']['v'];
    $message = $MadelineProto->channels->getMessages(['channel' => $peer, 'id' => [$id]]);
    if (!is_array($message)) return;
    $message = $message['messages'][0]['message'];
    $default = array(
        'peer' => $parsed_query['peer'],
        'id' => $parsed_query['msg_id'],
        'parse_mode' => 'html',
        'message' => $message
    );
    try {
        $callbackAnswer = $MadelineProto->messages->setBotCallbackAnswer(['alert'  => true, 'query_id' => $parsed_query['query_id'], 'message' => $MadelineProto->hints[$v], 'cache_time' => 3, ]);
        \danog\MadelineProto\Logger::log($callbackAnswer);
    } catch (Exception $e) {}
}

function settings_menu_callback($update, $MadelineProto)
{
    $parsed_query = parse_query($update, $MadelineProto);
    $peer = $parsed_query['peer'];
    $id = $parsed_query['msg_id'];
    $ch_id = $parsed_query['data']['c'];
    $userid = $parsed_query['user_id'];
    $default = array(
        'peer' => $parsed_query['peer'],
        'id' => $parsed_query['msg_id'],
        'parse_mode' => 'html',
        'message' => "Here's the settings menu! Feel free to explore"
    );
    if ($parsed_query['data']['u'] != $parsed_query['user_id'] or !is_admin_mod($update, $MadelineProto, $parsed_query['user_id'], false, false, $parsed_query['data']['c'])) return;
    if (is_moderated($ch_id)) {
        $rows = [];
         $buttons = [
        ['_' => 'keyboardButtonCallback', 'text' => "Locked", 'data' => json_encode(array(
            "q" => "locked",     // query
            "u" =>  $userid,     // user
            "c" =>  $ch_id ))],  // chat
        ['_' => 'keyboardButtonCallback', 'text' => "Welcome", 'data' => json_encode(array(
            "q" => "welcome_menu",       // query
            "u" =>  $userid,     // user
            "c" =>  $ch_id ))]   // chat
        ];
        $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons ];
        $rows[] = $row;
        $replyInlineMarkup = ['_' => 'replyInlineMarkup', 'rows' => $rows, ];
        $default['reply_markup'] = $replyInlineMarkup;
    }
    if (isset($default['message'])) {
        try {
            $sentMessage = $MadelineProto->messages->editMessage(
                $default
            );
            \danog\MadelineProto\Logger::log($sentMessage);
        } catch (Exception $e) {}
    }
}

function help2_callback($update, $MadelineProto)
{
    $parsed_query = parse_query($update, $MadelineProto);
    $peer = $parsed_query['peer'];
    $id = $parsed_query['msg_id'];
    $userid = $parsed_query['user_id'];
    $v = $parsed_query['data']['v'];
    $default = array(
        'peer' => $parsed_query['peer'],
        'parse_mode' => 'html'
    );
    $rows = [];
    $rowcount = 0;
    $file = file_get_contents("start_help.json");
    $startj = json_decode($file, true);
    $default['message'] = $startj['menus'][$v];
    foreach ($startj['commands_help'][$v] as $command => $desc) {
         if ($rowcount < 2 && $rowcount > 0) {
             $end = false;
             $rowcount = 0;
             $buttons[] =
                ['_' => 'keyboardButtonCallback', 'text' => $command, 'data' => json_encode(array(
                    "q" => "help3",
                    "v" => "$command",
                    "e" => "$v",
                    "u" =>  $peer))];
            $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons ];
            $rows[] = $row;
         } else {
             $end = true;
             $rowcount++;
             $buttons = [
                ['_' => 'keyboardButtonCallback', 'text' => $command, 'data' => json_encode(array(
                    "q" => "help3",
                    "v" => "$command",
                    "e" => "$v",
                    "u" =>  $peer))]
            ];
        }
    }
    if ($end) {
        $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons ];
        $rows[] = $row;
    }
    $buttons = [
        ['_' => 'keyboardButtonCallback', 'text' => "\xf0\x9f\x94\x99", 'data' => json_encode(array(
            "q" => "back_to_help",
            "u" =>  $peer))]
    ];
    $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons ];
    $rows[] = $row;
    $replyInlineMarkup = ['_' => 'replyInlineMarkup', 'rows' => $rows, ];
    $default['reply_markup'] = $replyInlineMarkup;
    if (isset($default['message'])) {
        try {
            $sentMessage = $MadelineProto->messages->sendMessage(
                $default
            );
            \danog\MadelineProto\Logger::log($sentMessage);
        } catch (Exception $e) {}
    }
}

function help3_callback($update, $MadelineProto)
{
    $parsed_query = parse_query($update, $MadelineProto);
    $peer = $parsed_query['peer'];
    $id = $parsed_query['msg_id'];
    $userid = $parsed_query['user_id'];
    $v = $parsed_query['data']['v'];
    $e = $parsed_query['data']['e'];
    $default = array(
        'peer' => $parsed_query['peer'],
        'parse_mode' => 'html'
    );
    $rows = [];
    $buttons = [
        ['_' => 'keyboardButtonCallback', 'text' => "\xf0\x9f\x94\x99", 'data' => json_encode(array(
            "q" => "back_to_help",
            "u" =>  $peer))]
    ];
    $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons ];
    $rows[] = $row;
    $replyInlineMarkup = ['_' => 'replyInlineMarkup', 'rows' => $rows, ];
    $file = file_get_contents("start_help.json");
    $startj = json_decode($file, true);
    $default['message'] = $startj['commands_help'][$e][$v];
    $default['reply_markup'] = $replyInlineMarkup;
    if (isset($default['message'])) {
        try {
            $sentMessage = $MadelineProto->messages->sendMessage(
                $default
        );
        \danog\MadelineProto\Logger::log($sentMessage);
    } catch (Exception $e) {}
}
}

function help_menu_callback($update, $MadelineProto)
{
    $parsed_query = parse_query($update, $MadelineProto);
    $peer = $parsed_query['peer'];
    $id = $parsed_query['msg_id'];
    $userid = $parsed_query['user_id'];
    $default = array(
        'peer' => $peer,
        'parse_mode' => 'html',
        'message' => 'You can navigate the help menu to see each command and how it\'s used. All commands can be started with !, /, or #'
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
    try {
        $sentMessage = $MadelineProto->messages->sendMessage(
            $default
        );
        \danog\MadelineProto\Logger::log($sentMessage);
    } catch (Exception $e) {}
}
