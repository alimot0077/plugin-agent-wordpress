<?php
if (!defined('ABSPATH')) {
    exit;
}

class Aria_CRM_Integration {
    /**
     * ذخیره سرنخ ساده در فایل CSV
     */
    public function add_lead($data) {
        $upload = wp_upload_dir();
        $file = $upload['basedir'] . '/aria-chatbot/leads.csv';

        if (!file_exists($upload['basedir'] . '/aria-chatbot')) {
            wp_mkdir_p($upload['basedir'] . '/aria-chatbot');
        }

        $line = sprintf("\"%s\",\"%s\",\"%s\"\n", date('Y-m-d H:i:s'), $data['name'] ?? '', $data['email'] ?? '');
        file_put_contents($file, $line, FILE_APPEND);

        return true;
    }
}
