<?php
/**
 * کلاس عملکرد و بهینه سازی پشتیبان هوشمند آریا
 *
 * @package Aria_Chatbot
 */
if (!defined('ABSPATH')) {
    exit;
}

class Aria_Performance {
    /**
     * دریافت داده‌های عملکرد
     */
    public function get_performance_data() {
        return array(
            'php_version'   => PHP_VERSION,
            'memory_limit'  => ini_get('memory_limit'),
            'memory_usage'  => size_format(memory_get_usage(true)),
            'load_average'  => function_exists('sys_getloadavg') ? implode(' ', sys_getloadavg()) : 'n/a',
            'db_queries'    => get_num_queries(),
            'execution_time' => timer_stop(0, 3),
        );
    }
}
