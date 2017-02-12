<?php
/**
    Copyright (C) 2016-2017 Hunter Ashton

    This file is part of BruhhBot.

    BruhhBot is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    BruhhBot is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with BruhhBot. If not, see <http://www.gnu.org/licenses/>.
 */
use Cmfcmf\OpenWeatherMap;
use Cmfcmf\OpenWeatherMap\Exception as OWMException;
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
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            );
        $cloudkey = getenv('WEATHER_KEY');
        $emoji = 'Spatie\Emoji\Emoji';
        $response = Requests::get(
            "https://maps.googleapis.com/maps/api/geocode/json?address="
            . str_replace(" ", "%20", $area)
        );
        $status = $response->status_code;
        // var_dump($status);
        $headers = array('Accept' => 'application/json');
        $responsej = json_decode($response->body, true);
        if ($responsej['status'] == 'OK') {
            $lat = $responsej['results'][0]['geometry']['location']['lat'];
            $lng = $responsej['results'][0]['geometry']['location']['lng'];

            // Language of data (try your own language here!):
            $lang = 'en';

            // Units (can be 'metric' or 'imperial' [default]):
            $units = 'imperial';

            // Create OpenWeatherMap object.
            // Don't use caching (take a look into Examples/Cache.php to see how it
            // works).
            $owm = new OpenWeatherMap($cloudkey);

            try {
                $weather = $owm->getWeather(
                    array('lat' => $lat, 'lon' => $lng), $units, $lang
                );
            } catch(OWMException $e) {
                echo 'OpenWeatherMap exception: ' .
                   $e->getMessage() . ' (Code ' . $e->getCode() . ').';
            } catch(\Exception $e) {
                echo 'General exception: ' .
                   $e->getMessage() . ' (Code ' . $e->getCode() . ').';
            }
            var_dump($weather);
            $name = $weather->city->name;
            $tempf = $weather->temperature;
            $temp = preg_replace('/ F/', "° F", $tempf);
            $tempc = (int) preg_replace('/ F/', "", $tempf);
            $tempc = round(($tempc - 32) / 1.8, 1);
            $tempc = $tempc.'° C';
            $desc = $weather->clouds->getDescription().' ('.$weather->clouds.')';
            $country = $weather->city->country;
            $desc = preg_replace('/\((.*)\)/', "", $desc);
            $icon = $weather->weather->id;
            $thunder = [900, 901, 902, 905];
            switch ($icon) {
            case in_array($icon, range(200, 299)):
            case in_array($icon, $thunder):
                $icon = $emoji::thunderCloudAndRain();
                break;
            case in_array($icon, range(300, 399)):
                $icon = $emoji::droplet();
                break;
            case in_array($icon, range(500, 599)):
                $icon = $emoji::umbrellaWithRainDrops();
                break;
            case in_array($icon, range(600, 699)):
                $icon = $emoji::snowflake().' '.$emoji::snowman();
                break;
            case in_array($icon, range(700, 799)):
                $icon = $emoji::foggy();
                break;
            case 800:
                $icon = $emoji::blackSunWithRays();
                break;
            case 801:
                $icon = $emoji::whiteSunBehindCloud();
                break;
            case in_array($icon, range(802, 804)):
                $icon = $emoji::cloud();
                break;
            case 904:
                $icon = $emoji::fire();
                break;
            }
            $message = 'The current temperature in '.$name.' ('.$country.')'.' is '.
            "\r\n".$temp."\r\n".$tempc."\r\n".'Description: '.$desc." ".$icon;
            $default['message'] = $message;
        } else {
            $message = 'What the actual hell is "' . $area . '"';
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