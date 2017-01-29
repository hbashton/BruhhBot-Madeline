#!/usr/bin/env php
<?php
/* server timezone */
define('CONST_SERVER_TIMEZONE', 'UTC');

/* server dateformat */
define('CONST_SERVER_DATEFORMAT', 'l, j - H:i:s');

function getloc($area) {
    $response = Requests::get(
        "https://maps.googleapis.com/maps/api/geocode/json?address="
        . str_replace(" ", "%20", $area));
    $status = $response->status_code;
    #var_dump($status);
    $headers = array('Accept' => 'application/json');
    $responsej = json_decode($response->body, true);
    #var_dump(json_decode($response->body, true));
    if ($responsej['status'] == 'OK') {
    #var_dump($responsej['results'][0]['geometry']['location']);
    $lat = $responsej['results'][0]['geometry']['location']['lat'];
    $lng = $responsej['results'][0]['geometry']['location']['lng'];
    $timestamp = time();
    $api_response = Requests::get(
        'https://maps.googleapis.com/maps/api/timezone/json?location='
        . $lat . ',' . $lng . '&timestamp=' . $timestamp);
    $api_responsej = json_decode($api_response->body, true);
    $ctime = now($api_responsej['timeZoneId']);
    $timezone = $api_responsej['timeZoneId'];
    $return = 'The current time in ' . $timezone . ' is ' . $ctime;
    $return = str_replace("_", " ", $return);
    return($return);
    } else {
        return('What the actual hell is "' . $area . '"');
    }
}
function now($str_user_timezone,
    $str_server_timezone = CONST_SERVER_TIMEZONE,
    $str_server_dateformat = CONST_SERVER_DATEFORMAT) {

// set timezone to user timezone
    date_default_timezone_set($str_user_timezone);

    $date = new DateTime('now');
    $date->setTimezone(new DateTimeZone($str_user_timezone));
    $str_server_now = $date->format($str_server_dateformat);

// return timezone to server default
    date_default_timezone_set($str_server_timezone);

return $str_server_now;
}
