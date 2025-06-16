<?php
if (!defined('ABSPATH')) {
    exit;
}

class Aria_Helpers {
    /**
     * نمونه متد کمکی برای خروجی امن
     */
    public static function esc($text) {
        return esc_html($text);
    }
}
