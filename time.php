<?php
/**
    along with BruhhBot. If not, see <http://www.gnu.org/licenses/>.
 */
/* server timezone */
define('CONST_SERVER_TIMEZONE', 'UTC');

/* server dateformat */
define('CONST_SERVER_DATEFORMAT', 'l, j - H:i:s');

function gettime($update, $MadelineProto, $area)
{
    if (is_peeruser($update, $MadelineProto)) {
        $peer = cache_get_info(
            $update,
            $MadelineProto,
            $update['update']['message']['from_id']
        )['bot_api_id'];
        $ch_id = $peer;
        $cont = true;
    }
    if (is_supergroup($update, $MadelineProto)) {
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $ch_id = $chat['id'];
        $cont = true;
    }
    if ($cont) {
        $msg_id = $update['update']['message']['id'];
        $default = [
            'peer'            => $peer,
            'reply_to_msg_id' => $msg_id,
            ];
        $response = Requests::get(
            'https://maps.googleapis.com/maps/api/geocode/json?address='
            .str_replace(' ', '%20', $area)
        );
        $status = $response->status_code;
        // var_dump($status);
        $headers = ['Accept' => 'application/json'];
        $responsej = json_decode($response->body, true);
        // var_dump(json_decode($response->body, true));
        if ($responsej['status'] == 'OK') {
            // var_dump($responsej['results'][0]['geometry']['location']);
            $lat = $responsej['results'][0]['geometry']['location']['lat'];
            $lng = $responsej['results'][0]['geometry']['location']['lng'];
            $timestamp = time();
            $api_response = Requests::get(
                'https://maps.googleapis.com/maps/api/timezone/json?location='.
                "$lat,$lng&timestamp=$timestamp"
            );
            $api_responsej = json_decode($api_response->body, true);
            $ctime = now($api_responsej['timeZoneId']);
            $timezone = $api_responsej['timeZoneId'];
            $str = $MadelineProto->responses['gettime']['success'];
            $repl = [
                'timezone' => $timezone,
                'ctime'    => $ctime,
            ];
            $message = $MadelineProto->engine->render($str, $repl);
            $message = str_replace('_', ' ', $message);
            $default['message'] = $message;
        } else {
            $str = $MadelineProto->responses['gettime']['fail'];
            $repl = [
                'area' => $area,
            ];
            $message = $MadelineProto->engine->render($str, $repl);
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
function now($str_user_timezone,
    $str_server_timezone = CONST_SERVER_TIMEZONE,
    $str_server_dateformat = CONST_SERVER_DATEFORMAT
) {

    // set timezone to user timezone
    date_default_timezone_set($str_user_timezone);

    $date = new DateTime('now');
    $date->setTimezone(new DateTimeZone($str_user_timezone));
    $str_server_now = $date->format($str_server_dateformat);

    // return timezone to server default
    date_default_timezone_set($str_server_timezone);

    return $str_server_now;
}
