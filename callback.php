<?php

class BotCallbackQuery extends Threaded
{
    private $update;
    private $MadelineProto;
    public function __construct($update, $MadelineProto)
    {
        $this->update = $update;
        $this->MadelineProto = $MadelineProto;
    }
    public function run()
    {
        require_once 'require_exceptions.php';
        $update = $this->update;
        $MadelineProto = $this->MadelineProto;
        if (array_key_exists("data", $update['update'])) {
            $parsed_query = parse_query($update, $MadelineProto);
            switch ($parsed_query['data']['q']) {
                case 'welcome':
                    welcome_callback($update, $MadelineProto);
                break;
            }
        }
    }
}
