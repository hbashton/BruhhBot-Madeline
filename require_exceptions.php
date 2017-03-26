<?php


require_once __DIR__.'/MadelineProto/src/danog/MadelineProto/SecurityException.php';
require_once __DIR__.'/MadelineProto/src/danog/MadelineProto/RPCErrorException.php';
require_once __DIR__.'/MadelineProto/src/danog/MadelineProto/ResponseException.php';
require_once __DIR__.'/MadelineProto/src/danog/MadelineProto/TL/Conversion/Exception.php';
require_once __DIR__.'/MadelineProto/src/danog/MadelineProto/TL/Exception.php';
require_once __DIR__.'/MadelineProto/src/danog/MadelineProto/NothingInTheSocketException.php';
require_once __DIR__.'/MadelineProto/src/danog/MadelineProto/Exception.php';
foreach (glob(__DIR__.'/vendor/nicmart/string-template/src/StringTemplate/*') as $f) { require_once($f); }
require_once 'vendor/rmccue/requests/library/Requests.php';
require_once 'vendor/spatie/emoji/src/Emoji.php';