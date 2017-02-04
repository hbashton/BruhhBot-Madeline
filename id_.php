#!/usr/bin/env php
<?php

function catch_id($update, $MadelineProto, $user) {
    $first_char = substr($user, 0, 1);
    if (preg_match_all('/@/', $first_char, $matches)) {
        try {
            $MadelineProto->get_info($user);
            if (array_key_exists('username', $MadelineProto->
            get_info($user)['User'])) {
                $username = $MadelineProto->get_info($user)['User']
                ['username'];
            } else {
                $username = $MadelineProto->get_info($user)['User']
                ['first_name'];
            }
            $userid = $MadelineProto->get_info($user)['bot_api_id'];
            return array(true, $userid, $username);
        } catch (Exception $e) {
            return array(false);
        }
    } else {
        if (array_key_exists('entities', $update['update']['message'])) {
            foreach ($update['update']['message']['entities'] as $key) {
                if (array_key_exists('user_id', $key)) {
                    $userid = $key['user_id'];
                    $username = $MadelineProto->get_info($user)['User']
                    ['first_name'];
                    break;
                }
            }
        }
        if (isset($userid)) {
            return array(true, $userid, $username);
        } else {
            return array(false);
        }
    }
}
