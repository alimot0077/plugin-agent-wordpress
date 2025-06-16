<?php
/**
 * کلاس تحلیل آمار پشتیبان هوشمند آریا
 *
 * @package Aria_Chatbot
 */
if (!defined('ABSPATH')) {
    exit;
}

class Aria_Analytics {
    /**
     * آمار کلی داشبورد
     */
    public function get_dashboard_stats() {
        global $wpdb;
        $conv_table = Aria_Database::get_table_name('conversations');
        $sessions_table = Aria_Database::get_table_name('user_sessions');

        $stats = array();
        $stats['total_conversations'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$conv_table}");
        $stats['unique_users'] = (int) $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$sessions_table}");
        $stats['tokens_used'] = (int) $wpdb->get_var("SELECT SUM(tokens_used) FROM {$conv_table}");
        $stats['conversations_today'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$conv_table} WHERE DATE(timestamp) = %s", current_time('Y-m-d')));
        $stats['avg_response_time'] = (float) $wpdb->get_var("SELECT AVG(processing_time) FROM {$conv_table}");
        $stats['satisfaction_score'] = (float) $wpdb->get_var("SELECT AVG(CASE WHEN user_satisfaction='satisfied' THEN 5 WHEN user_satisfaction='neutral' THEN 3 WHEN user_satisfaction='dissatisfied' THEN 1 ELSE 0 END) FROM {$conv_table}");

        // داده نمودار 7 روز گذشته
        $labels = array();
        $conv_counts = array();
        for ($i = 6; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-{$i} days"));
            $labels[] = $day;
            $conv_counts[] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$conv_table} WHERE DATE(timestamp) = %s", $day));
        }
        $stats['chart_data'] = array(
            'labels' => $labels,
            'conversations' => $conv_counts,
        );

        return $stats;
    }

    /**
     * آمار برای صفحه گزارشات
     */
    public function get_comprehensive_analytics() {
        global $wpdb;
        $conv_table = Aria_Database::get_table_name('conversations');

        $totals = array(
            'conversations' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$conv_table}"),
            'messages'      => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$conv_table}"),
            'tokens'        => (int) $wpdb->get_var("SELECT SUM(tokens_used) FROM {$conv_table}"),
        );

        $labels = array();
        $data = array();
        for ($i = 29; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-{$i} days"));
            $labels[] = $day;
            $data[] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$conv_table} WHERE DATE(timestamp) = %s", $day));
        }

        return array(
            'totals' => $totals,
            'chart'  => array(
                'labels' => $labels,
                'conversations' => $data,
            ),
        );
    }

    /**
     * تولید آمار هفتگی به صورت آرایه
     */
    public function generate_weekly_report() {
        $data = $this->get_comprehensive_analytics();
        $labels = $data['chart']['labels'];
        $counts = $data['chart']['conversations'];

        $report = array();
        $days = array_slice($labels, -7);
        $conv = array_slice($counts, -7);

        foreach ($days as $index => $day) {
            $report[$day] = $conv[$index];
        }

        return $report;
    }
}
