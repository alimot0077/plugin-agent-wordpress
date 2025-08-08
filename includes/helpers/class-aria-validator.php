<?php
if (!defined('ABSPATH')) {
    exit;
}

class Aria_Validator {
    /**
     * اعتبارسنجی مقدار متنی ساده
     */
    public static function sanitize_text($text) {
        return sanitize_text_field($text);
    }
}
