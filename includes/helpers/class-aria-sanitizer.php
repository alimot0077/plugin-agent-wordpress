<?php
if (!defined('ABSPATH')) {
    exit;
}

class Aria_Sanitizer {
    /**
     * پاکسازی داده های ورودی
     */
    public static function sanitize($data) {
        return array_map('sanitize_text_field', (array) $data);
    }
}
