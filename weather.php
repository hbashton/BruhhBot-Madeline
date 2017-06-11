<?php
/**
 * Copyright (C) 2016-2017 Hunter Ashton
 * This file is part of BruhhBot.
 * BruhhBot is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * BruhhBot is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with BruhhBot. If not, see <http://www.gnu.org/licenses/>.
 */

/* server timezone */
define('CONST_SERVER_TIMEZONE', 'UTC');

/* server dateformat */
define('CONST_SERVER_DATEFORMAT', 'l, j - H:i:s');

function getweather($update, $MadelineProto, $area)
{
    if (is_peeruser($update, $MadelineProto)) {
        $peer = cache_get_info(
            $update,
            $MadelineProto,
            $update['update']['message']['from_id']
        )['bot_api_id'];
        $ch_id = $peer;
        $cont = true;
        $peerUSER = true;
    }
    if (is_supergroup($update, $MadelineProto)) {
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $ch_id = $chat['id'];
        $cont = true;
        $peerUSER = false;
    }
    if ($cont) {
        $msg_id = $update['update']['message']['id'];
        $default = [
            'peer'            => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode'      => 'html',
            ];
        $cloudkey = getenv('WEATHER_KEY');
        $emoji = 'Spatie\Emoji\Emoji';
        $response = Requests::get(
            'https://maps.googleapis.com/maps/api/geocode/json?address='
            .str_replace(' ', '%20', $area)
        );
        $status = $response->status_code;
        // var_dump($status);
        $headers = ['Accept' => 'application/json'];
        $responsej = json_decode($response->body, true);
        if ($responsej['status'] == 'OK') {
            $lat = $responsej['results'][0]['geometry']['location']['lat'];
            $lng = $responsej['results'][0]['geometry']['location']['lng'];
            $addr = $responsej['results'][0]['formatted_address'];
            $units = 'us';
            $api_response = Requests::get(
                "https://api.darksky.net/forecast/$cloudkey/$lat,$lng?units=us"
            );
            $weather = json_decode($api_response->body, true);
            $temp = $weather['currently']['temperature'];
            $tempf = (string) "$temp °F";
            $tempc = round(($temp - 32) / 1.8, 1);
            $tempc = (string) "$tempc °C";
            $atemp = $weather['currently']['apparentTemperature'];
            $atempf = (string) "$atemp °F";
            $atempc = round(($atemp - 32) / 1.8, 1);
            $atempc = (string) "$atempc °C";
            $desc = strtolower($weather['currently']['summary']);
            $forecast = $weather['daily']['summary'];
            $icon = $weather['currently']['icon'];
            switch ($icon) {
            case 'clear-day':
                $icon = "\xe2\x98\x80\xef\xb8\x8f";
                break;
            case 'clear-night':
                $icon = "\xf0\x9f\x8c\x83";
                break;
            case 'rain':
                $icon = "\xe2\x98\x94\xef\xb8\x8f";
                break;
            case 'snow':
                $icon = "\xf0\x9f\x8c\xa8";
                break;
            case 'sleet':
                $icon = "\xf0\x9f\x8c\xa7";
                break;
            case 'wind':
                $icon = "\xf0\x9f\x8c\xac";
                break;
            case 'fog':
                $icon = "\xf0\x9f\x8c\xab";
                break;
            case 'cloudy':
                $icon = "\xe2\x98\x81\xef\xb8\x8f";
                break;
            case 'partly-cloudy-day':
                $icon = "\xf0\x9f\x8c\xa4";
                break;
            case 'partly-cloudy-night':
                $icon = "\xe2\x98\x81\xef\xb8\x8f";
                break;
            default:
                $icon = '';
                break;
            }
            $message = "The current temperature in <b>$addr</b> is $tempf/$tempc (feels like $atempf/$atempc). It's currently $desc $icon.\n<b>Forecast</b>: $forecast";
            $default['message'] = $message;
        } else {
            $message = 'What the actual hell is "'.$area.'"';
            $default['message'] = $message;
        }
        if (isset($default['message'])) {
            $sentMessage = $MadelineProto->messages->sendMessage(
                $default
            );
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}
