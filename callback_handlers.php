<?php

function welcome_callback($update, $MadelineProto)
{
    $parsed_query = parse_query($update, $MadelineProto);
    $default = array(
        'peer' => $parsed_query['peer'],
        'id' => $parsed_query['msg_id'],
        'parse_mode' => 'html'
    );
    if ($parsed_query['data']['u'] != $parsed_query['user_id']) return;
    if ($parsed_query['data']['v'] == "on") {
        check_json_array('settings.json', $default['peer']);
        $file = file_get_contents("settings.json");
        $settings = json_decode($file, true);
        $settings[$default['peer']]["welcome"] = true;
        $default['message'] = "I will now welcome all new members";
    } else {
        check_json_array('settings.json', $default['peer']);
        $file = file_get_contents("settings.json");
        $settings = json_decode($file, true);
        $settings[$default['peer']]["welcome"] = false;
        $default['message'] = "I will no longer welcome new members";
    }
    file_put_contents('settings.json', json_encode($settings));
    $editedMessage = $MadelineProto->messages->editMessage(
        $default
    );
    \danog\MadelineProto\Logger::log($editedMessage);
}
