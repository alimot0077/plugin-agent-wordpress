<?php
if (!defined('ABSPATH')) {
    exit;
}

class Aria_Social_Media_Integration {
    /**
     * ارسال پیام به شبکه‌های اجتماعی (تلگرام در حال حاضر)
     */
    public function send_message($network, $text) {
        if ($network === 'telegram') {
            $token = aria_get_option('telegram_token');
            $chat_id = aria_get_option('telegram_chat_id');

            if (!$token || !$chat_id) {
                return false;
            }

            $endpoint = 'https://api.telegram.org/bot' . $token . '/sendMessage';
            wp_remote_post($endpoint, array('body' => array('chat_id' => $chat_id, 'text' => $text)));
            return true;
        }

        return false;
    }
}
