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
                case 'group_settings':
                    group_settings($update, $MadelineProto);
                break;

                case 'moderators':
                    moderators_menu_callback($update, $MadelineProto);
                break;

                case 'rules_menu':
                    rules_menu($update, $MadelineProto);
                break;

                case 'rules_show':
                    rules_show_callback($update, $MadelineProto);
                break;

                case 'moderators_menu':
                    moderators_menu($update, $MadelineProto);
                break;

                case 'alert_me_menu':
                    alert_me_menu($update, $MadelineProto);
                break;

                case 'alert_me_cb':
                    alert_me_callback($update, $MadelineProto);
                break;

                case 'user_settings':
                    user_settings_menu($update, $MadelineProto);
                break;

                case 'welcome':
                    welcome_callback($update, $MadelineProto);
                break;

                case 'welcome_menu':
                    welcome_menu($update, $MadelineProto);
                break;

                case 'increase_flood':
                    increment_flood($update, $MadelineProto, true);
                break;

                case 'hint':
                    alert_hint($update, $MadelineProto);
                break;

                case 'decrease_flood':
                    increment_flood($update, $MadelineProto, false);
                break;

                case 'lock':
                    lock_callback($update, $MadelineProto);
                break;

                case 'locked':
                    locked_menu($update, $MadelineProto);
                break;

                case 'help2':
                    help2_callback($update, $MadelineProto);
                break;

                case 'help3':
                    help3_callback($update, $MadelineProto);
                break;

                case 'back_to_help':
                    help_menu_callback($update, $MadelineProto);
                break;

                case 'back_to_settings':
                    settings_menu_callback($update, $MadelineProto);
                break;
            }
        }
    }
}
