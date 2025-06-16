<?php
/**
 * کلاس امنیت پشتیبان هوشمند آریا
 *
 * @package Aria_Chatbot
 */
if (!defined('ABSPATH')) {
    exit;
}

class Aria_Security {
    /**
     * اجرای چک‌های امنیتی پایه
     */
    public function run_security_checks() {
        $checks = array();

        $checks['api_key_set'] = !empty(aria_get_option('openai_api_key'));
        $checks['using_ssl']   = is_ssl();

        $upload_dir = wp_upload_dir();
        $checks['uploads_writable'] = wp_is_writable($upload_dir['basedir']);

        $status = ($checks['api_key_set'] && $checks['uploads_writable']);

        return array(
            'status'  => $status ? 'ok' : 'warning',
            'details' => $checks,
        );
    }
}
