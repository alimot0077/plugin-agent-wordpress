<?php
/**
 * کلاس مدیریت دیتابیس پشتیبان هوشمند آریا
 * 
 * @package Aria_Chatbot
 * @author علی مطلقیان
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Aria_Database {
    
    /**
     * نسخه دیتابیس
     */
    const DB_VERSION = '2.0.0';
    
    /**
     * نام جداول
     */
    private static $tables = array(
        'conversations' => 'aria_conversations',
        'memory' => 'aria_memory',
        'behavior_patterns' => 'aria_behavior_patterns',
        'knowledge_base' => 'aria_knowledge_base',
        'analytics' => 'aria_analytics',
        'personality_evaluations' => 'aria_personality_evaluations',
        'voice_samples' => 'aria_voice_samples',
        'user_sessions' => 'aria_user_sessions',
        'api_usage' => 'aria_api_usage',
        'feedback' => 'aria_feedback'
    );
    
    /**
     * سازنده کلاس
     */
    public function __construct() {
        $this->init();
    }
    
    /**
     * راه‌اندازی اولیه
     */
    private function init() {
        // بررسی نسخه دیتابیس
        $this->check_database_version();
        
        // تنظیم هوک‌ها
        $this->setup_hooks();
    }
    
    /**
     * تنظیم هوک‌ها
     */
    private function setup_hooks() {
        add_action('aria_create_database_tables', array($this, 'create_tables'));
        add_action('aria_upgrade_database', array($this, 'upgrade_database'));
        add_action('aria_optimize_database', array($this, 'optimize_tables'));
        add_action('wp_ajax_aria_database_maintenance', array($this, 'handle_database_maintenance'));
    }
    
    /**
     * بررسی نسخه دیتابیس
     */
    private function check_database_version() {
        $current_version = get_option('aria_database_version', '0.0.0');
        
        if (version_compare($current_version, self::DB_VERSION, '<')) {
            $this->upgrade_database($current_version);
            update_option('aria_database_version', self::DB_VERSION);
        }
    }
    
    /**
     * ایجاد جداول دیتابیس
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // جدول مکالمات
        $conversations_table = $wpdb->prefix . self::$tables['conversations'];
        $sql_conversations = "CREATE TABLE $conversations_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id varchar(255) NOT NULL,
            session_id varchar(255) NOT NULL,
            message longtext NOT NULL,
            response longtext NOT NULL,
            message_type enum('text','voice','image') DEFAULT 'text',
            input_method enum('keyboard','voice','upload') DEFAULT 'keyboard',
            audio_message_url varchar(500) DEFAULT NULL,
            response_audio_url varchar(500) DEFAULT NULL,
            image_data longtext DEFAULT NULL,
            language_detected varchar(10) DEFAULT 'fa-IR',
            confidence_score decimal(4,3) DEFAULT 1.000,
            processing_time int(11) DEFAULT 0,
            tokens_used int(11) DEFAULT 0,
            model_used varchar(50) DEFAULT NULL,
            user_satisfaction enum('satisfied','neutral','dissatisfied') DEFAULT NULL,
            flagged tinyint(1) DEFAULT 0,
            flag_reason varchar(255) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            page_url varchar(500) DEFAULT NULL,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY session_id (session_id),
            KEY timestamp (timestamp),
            KEY user_satisfaction (user_satisfaction),
            KEY language_detected (language_detected),
            KEY flagged (flagged)
        ) $charset_collate;";
        
        // جدول حافظه
        $memory_table = $wpdb->prefix . self::$tables['memory'];
        $sql_memory = "CREATE TABLE $memory_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id varchar(255) NOT NULL,
            session_id varchar(255) DEFAULT NULL,
            memory_type enum('profile','preferences','interests','behavior','context') NOT NULL,
            memory_key varchar(255) NOT NULL,
            memory_value longtext NOT NULL,
            confidence_score decimal(4,3) DEFAULT 0.500,
            importance_level tinyint(1) DEFAULT 3,
            source enum('explicit','inferred','learned') DEFAULT 'inferred',
            verification_status enum('verified','pending','disputed') DEFAULT 'pending',
            is_active tinyint(1) DEFAULT 1,
            is_archived tinyint(1) DEFAULT 0,
            update_count int(11) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            last_accessed datetime DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_memory_key (user_id, memory_type, memory_key),
            KEY user_id (user_id),
            KEY memory_type (memory_type),
            KEY confidence_score (confidence_score),
            KEY is_active (is_active),
            KEY last_accessed (last_accessed)
        ) $charset_collate;";
        
        // جدول الگوهای رفتاری
        $patterns_table = $wpdb->prefix . self::$tables['behavior_patterns'];
        $sql_patterns = "CREATE TABLE $patterns_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id varchar(255) NOT NULL,
            pattern_type enum('time_usage','message_length','request_type','interaction_style','topic_preference') NOT NULL,
            pattern_data longtext NOT NULL,
            frequency int(11) DEFAULT 1,
            confidence_level decimal(4,3) DEFAULT 0.500,
            first_occurrence datetime NOT NULL,
            last_occurrence datetime DEFAULT CURRENT_TIMESTAMP,
            trend_direction enum('increasing','stable','decreasing') DEFAULT 'stable',
            is_active tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY pattern_type (pattern_type),
            KEY frequency (frequency),
            KEY last_occurrence (last_occurrence),
            KEY is_active (is_active)
        ) $charset_collate;";
        
        // جدول پایگاه دانش
        $knowledge_table = $wpdb->prefix . self::$tables['knowledge_base'];
        $sql_knowledge = "CREATE TABLE $knowledge_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            question text NOT NULL,
            answer longtext NOT NULL,
            keywords text DEFAULT NULL,
            category varchar(100) DEFAULT 'general',
            subcategory varchar(100) DEFAULT NULL,
            tags text DEFAULT NULL,
            language varchar(10) DEFAULT 'fa-IR',
            priority int(11) DEFAULT 1,
            usage_count int(11) DEFAULT 0,
            success_rate decimal(4,3) DEFAULT 0.000,
            created_by bigint(20) DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            is_public tinyint(1) DEFAULT 1,
            requires_approval tinyint(1) DEFAULT 0,
            approval_status enum('approved','pending','rejected') DEFAULT 'approved',
            source enum('manual','imported','learned','user_suggested') DEFAULT 'manual',
            confidence_score decimal(4,3) DEFAULT 1.000,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY category (category),
            KEY priority (priority),
            KEY is_active (is_active),
            KEY language (language),
            KEY usage_count (usage_count),
            FULLTEXT KEY search_content (question, answer, keywords, tags)
        ) $charset_collate;";
        
        // جدول آنالیتیکس
        $analytics_table = $wpdb->prefix . self::$tables['analytics'];
        $sql_analytics = "CREATE TABLE $analytics_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            date date NOT NULL,
            metric_type enum('conversations','messages','users','satisfaction','performance','errors') NOT NULL,
            metric_name varchar(100) NOT NULL,
            metric_value decimal(15,4) NOT NULL,
            additional_data longtext DEFAULT NULL,
            aggregation_period enum('hourly','daily','weekly','monthly') DEFAULT 'daily',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY date_metric (date, metric_type, metric_name, aggregation_period),
            KEY date (date),
            KEY metric_type (metric_type),
            KEY metric_name (metric_name)
        ) $charset_collate;";
        
        // جدول ارزیابی شخصیت
        $personality_table = $wpdb->prefix . self::$tables['personality_evaluations'];
        $sql_personality = "CREATE TABLE $personality_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id varchar(255) DEFAULT NULL,
            session_id varchar(255) DEFAULT NULL,
            conversation_id bigint(20) DEFAULT NULL,
            personality_type varchar(50) NOT NULL,
            satisfaction enum('satisfied','neutral','dissatisfied') NOT NULL,
            conversation_length int(11) NOT NULL,
            response_quality_score decimal(4,3) DEFAULT NULL,
            personality_consistency_score decimal(4,3) DEFAULT NULL,
            user_feedback_text text DEFAULT NULL,
            evaluation_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY personality_type (personality_type),
            KEY satisfaction (satisfaction),
            KEY evaluation_date (evaluation_date)
        ) $charset_collate;";
        
        // جدول نمونه‌های صوتی
        $voice_table = $wpdb->prefix . self::$tables['voice_samples'];
        $sql_voice = "CREATE TABLE $voice_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id varchar(255) DEFAULT NULL,
            session_id varchar(255) DEFAULT NULL,
            original_text text NOT NULL,
            audio_file_path varchar(500) NOT NULL,
            audio_file_url varchar(500) NOT NULL,
            file_format enum('mp3','wav','ogg','webm') NOT NULL,
            file_size int(11) NOT NULL,
            duration_seconds decimal(6,2) NOT NULL,
            language varchar(10) NOT NULL,
            voice_gender enum('male','female','neutral') DEFAULT 'neutral',
            transcription_accuracy decimal(4,3) DEFAULT NULL,
            processing_method enum('browser','azure','google','openai') DEFAULT 'browser',
            is_processed tinyint(1) DEFAULT 0,
            is_archived tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY session_id (session_id),
            KEY language (language),
            KEY is_processed (is_processed),
            KEY created_at (created_at),
            KEY expires_at (expires_at)
        ) $charset_collate;";
        
        // جدول جلسات کاربر
        $sessions_table = $wpdb->prefix . self::$tables['user_sessions'];
        $sql_sessions = "CREATE TABLE $sessions_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            user_id varchar(255) NOT NULL,
            start_time datetime DEFAULT CURRENT_TIMESTAMP,
            end_time datetime DEFAULT NULL,
            duration_seconds int(11) DEFAULT NULL,
            message_count int(11) DEFAULT 0,
            voice_message_count int(11) DEFAULT 0,
            total_tokens_used int(11) DEFAULT 0,
            primary_language varchar(10) DEFAULT 'fa-IR',
            device_type enum('desktop','mobile','tablet','unknown') DEFAULT 'unknown',
            browser_info varchar(255) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            country_code varchar(2) DEFAULT NULL,
            referrer_url varchar(500) DEFAULT NULL,
            entry_page varchar(500) DEFAULT NULL,
            exit_page varchar(500) DEFAULT NULL,
            user_satisfaction enum('satisfied','neutral','dissatisfied') DEFAULT NULL,
            session_quality_score decimal(4,3) DEFAULT NULL,
            is_completed tinyint(1) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY session_id (session_id),
            KEY user_id (user_id),
            KEY start_time (start_time),
            KEY duration_seconds (duration_seconds),
            KEY is_completed (is_completed)
        ) $charset_collate;";
        
        // جدول استفاده از API
        $api_usage_table = $wpdb->prefix . self::$tables['api_usage'];
        $sql_api_usage = "CREATE TABLE $api_usage_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            date date NOT NULL,
            hour tinyint(2) NOT NULL,
            model_name varchar(50) NOT NULL,
            request_count int(11) DEFAULT 0,
            token_count int(11) DEFAULT 0,
            error_count int(11) DEFAULT 0,
            average_response_time decimal(8,2) DEFAULT 0.00,
            estimated_cost decimal(10,6) DEFAULT 0.000000,
            PRIMARY KEY (id),
            UNIQUE KEY date_hour_model (date, hour, model_name),
            KEY date (date),
            KEY model_name (model_name)
        ) $charset_collate;";
        
        // جدول بازخورد
        $feedback_table = $wpdb->prefix . self::$tables['feedback'];
        $sql_feedback = "CREATE TABLE $feedback_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id varchar(255) DEFAULT NULL,
            session_id varchar(255) DEFAULT NULL,
            conversation_id bigint(20) DEFAULT NULL,
            feedback_type enum('satisfaction','bug_report','feature_request','general') NOT NULL,
            rating tinyint(1) DEFAULT NULL,
            feedback_text text DEFAULT NULL,
            is_anonymous tinyint(1) DEFAULT 1,
            contact_email varchar(255) DEFAULT NULL,
            is_resolved tinyint(1) DEFAULT 0,
            admin_response text DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            resolved_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY feedback_type (feedback_type),
            KEY rating (rating),
            KEY is_resolved (is_resolved),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // اجرای کوئری‌ها
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $tables_sql = array(
            $sql_conversations,
            $sql_memory,
            $sql_patterns,
            $sql_knowledge,
            $sql_analytics,
            $sql_personality,
            $sql_voice,
            $sql_sessions,
            $sql_api_usage,
            $sql_feedback
        );
        
        foreach ($tables_sql as $sql) {
            dbDelta($sql);
        }
        
        // ایجاد ایندکس‌های اضافی
        self::create_additional_indexes();
        
        // اضافه کردن داده‌های پیش‌فرض
        self::insert_default_data();
        
        // ثبت لاگ
        aria_log('Database tables created successfully', 'info');
    }
    
    /**
     * ایجاد ایندکس‌های اضافی
     */
    private static function create_additional_indexes() {
        global $wpdb;
        
        // ایندکس‌های مرکب برای عملکرد بهتر
        $additional_indexes = array(
            // جدول مکالمات
            "CREATE INDEX idx_user_session_time ON {$wpdb->prefix}aria_conversations (user_id, session_id, timestamp)",
            "CREATE INDEX idx_satisfaction_time ON {$wpdb->prefix}aria_conversations (user_satisfaction, timestamp)",
            
            // جدول حافظه
            "CREATE INDEX idx_user_type_confidence ON {$wpdb->prefix}aria_memory (user_id, memory_type, confidence_score)",
            "CREATE INDEX idx_active_important ON {$wpdb->prefix}aria_memory (is_active, importance_level)",
            
            // جدول آنالیتیکس
            "CREATE INDEX idx_date_type_period ON {$wpdb->prefix}aria_analytics (date, metric_type, aggregation_period)",
            
            // جدول جلسات
            "CREATE INDEX idx_user_start_end ON {$wpdb->prefix}aria_user_sessions (user_id, start_time, end_time)",
            "CREATE INDEX idx_quality_satisfaction ON {$wpdb->prefix}aria_user_sessions (session_quality_score, user_satisfaction)"
        );
        
        foreach ($additional_indexes as $index_sql) {
            $wpdb->query($index_sql);
        }
    }
    
    /**
     * اضافه کردن داده‌های پیش‌فرض
     */
    private static function insert_default_data() {
        global $wpdb;
        
        // پایگاه دانش پیش‌فرض
        $default_knowledge = array(
            array(
                'question' => 'سلام',
                'answer' => 'سلام! خوش آمدید. چطور می‌تونم کمکتون کنم؟',
                'keywords' => 'سلام,درود,hello',
                'category' => 'greeting',
                'priority' => 5
            ),
            array(
                'question' => 'چطور کار می‌کنی؟',
                'answer' => 'من یک دستیار هوشمند هستم که با استفاده از هوش مصنوعی به سوالات شما پاسخ می‌دهم.',
                'keywords' => 'چطور,کار,عملکرد',
                'category' => 'about',
                'priority' => 4
            ),
            array(
                'question' => 'ممنون',
                'answer' => 'خواهش می‌کنم! خوشحالم که تونستم کمکتون کنم.',
                'keywords' => 'ممنون,متشکر,مرسی',
                'category' => 'thanks',
                'priority' => 3
            ),
            array(
                'question' => 'خداحافظ',
                'answer' => 'خداحافظ! امیدوارم مفید بوده باشم. موفق باشید!',
                'keywords' => 'خداحافظ,بای,goodbye',
                'category' => 'goodbye',
                'priority' => 3
            )
        );
        
        $knowledge_table = $wpdb->prefix . self::$tables['knowledge_base'];
        
        foreach ($default_knowledge as $knowledge) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$knowledge_table} WHERE question = %s",
                $knowledge['question']
            ));
            
            if (!$existing) {
                $wpdb->insert($knowledge_table, $knowledge);
            }
        }
        
        // آنالیتیکس پیش‌فرض
        $today = current_time('Y-m-d');
        $analytics_table = $wpdb->prefix . self::$tables['analytics'];
        
        $default_analytics = array(
            array(
                'date' => $today,
                'metric_type' => 'conversations',
                'metric_name' => 'total_count',
                'metric_value' => 0
            ),
            array(
                'date' => $today,
                'metric_type' => 'users',
                'metric_name' => 'unique_count',
                'metric_value' => 0
            )
        );
        
        foreach ($default_analytics as $metric) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$analytics_table} WHERE date = %s AND metric_type = %s AND metric_name = %s",
                $metric['date'], $metric['metric_type'], $metric['metric_name']
            ));
            
            if (!$existing) {
                $wpdb->insert($analytics_table, $metric);
            }
        }
    }
    
    /**
     * ارتقاء دیتابیس
     */
    public function upgrade_database($from_version) {
        global $wpdb;
        
        aria_log("Upgrading database from version {$from_version} to " . self::DB_VERSION, 'info');
        
        // ارتقاء از نسخه‌های مختلف
        if (version_compare($from_version, '1.0.0', '<')) {
            $this->upgrade_from_1_0_0();
        }
        
        if (version_compare($from_version, '1.5.0', '<')) {
            $this->upgrade_from_1_5_0();
        }
        
        if (version_compare($from_version, '2.0.0', '<')) {
            $this->upgrade_from_2_0_0();
        }
        
        // بهینه‌سازی جداول بعد از ارتقاء
        $this->optimize_tables();
        
        aria_log("Database upgrade completed successfully", 'info');
    }
    
    /**
     * ارتقاء از نسخه 1.0.0
     */
    private function upgrade_from_1_0_0() {
        global $wpdb;
        
        // اضافه کردن ستون‌های جدید به جدول مکالمات
        $conversations_table = $wpdb->prefix . self::$tables['conversations'];
        
        $new_columns = array(
            "ADD COLUMN message_type enum('text','voice','image') DEFAULT 'text' AFTER response",
            "ADD COLUMN audio_message_url varchar(500) DEFAULT NULL AFTER message_type",
            "ADD COLUMN response_audio_url varchar(500) DEFAULT NULL AFTER audio_message_url",
            "ADD COLUMN confidence_score decimal(4,3) DEFAULT 1.000 AFTER response_audio_url"
        );
        
        foreach ($new_columns as $column_sql) {
            $wpdb->query("ALTER TABLE {$conversations_table} {$column_sql}");
        }
    }
    
    /**
     * ارتقاء از نسخه 1.5.0
     */
    private function upgrade_from_1_5_0() {
        global $wpdb;
        
        // ایجاد جدول‌های جدید
        $this->create_voice_samples_table();
        $this->create_user_sessions_table();
        
        // مهاجرت داده‌ها
        $this->migrate_session_data();
    }
    
    /**
     * ارتقاء از نسخه 2.0.0
     */
    private function upgrade_from_2_0_0() {
        global $wpdb;
        
        // ایجاد جداول جدید
        $this->create_api_usage_table();
        $this->create_feedback_table();
        
        // به‌روزرسانی ساختار موجود
        $this->update_existing_structure();
    }
    
    /**
     * ایجاد جدول نمونه‌های صوتی
     */
    private function create_voice_samples_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$tables['voice_samples'];
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id varchar(255) DEFAULT NULL,
            session_id varchar(255) DEFAULT NULL,
            original_text text NOT NULL,
            audio_file_path varchar(500) NOT NULL,
            audio_file_url varchar(500) NOT NULL,
            file_format enum('mp3','wav','ogg','webm') NOT NULL,
            file_size int(11) NOT NULL,
            duration_seconds decimal(6,2) NOT NULL,
            language varchar(10) NOT NULL,
            voice_gender enum('male','female','neutral') DEFAULT 'neutral',
            transcription_accuracy decimal(4,3) DEFAULT NULL,
            processing_method enum('browser','azure','google','openai') DEFAULT 'browser',
            is_processed tinyint(1) DEFAULT 0,
            is_archived tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY session_id (session_id),
            KEY language (language),
            KEY is_processed (is_processed),
            KEY created_at (created_at),
            KEY expires_at (expires_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * بهینه‌سازی جداول
     */
    public function optimize_tables() {
        global $wpdb;
        
        foreach (self::$tables as $table_key => $table_name) {
            $full_table_name = $wpdb->prefix . $table_name;
            
            // بررسی وجود جدول
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'");
            
            if ($table_exists) {
                // تحلیل جدول
                $wpdb->query("ANALYZE TABLE {$full_table_name}");
                
                // بهینه‌سازی جدول
                $wpdb->query("OPTIMIZE TABLE {$full_table_name}");
                
                aria_log("Optimized table: {$table_name}", 'info');
            }
        }
    }
    
    /**
     * پاک‌سازی داده‌های قدیمی
     */
    public function cleanup_old_data($days = 90) {
        global $wpdb;
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        // پاک کردن مکالمات قدیمی کاربران مهمان
        $conversations_table = $wpdb->prefix . self::$tables['conversations'];
        $deleted_conversations = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$conversations_table} 
             WHERE timestamp < %s 
             AND user_id LIKE 'guest_%' 
             AND user_satisfaction IS NULL",
            $cutoff_date
        ));
        
        // پاک کردن فایل‌های صوتی منقضی شده
        $voice_table = $wpdb->prefix . self::$tables['voice_samples'];
        $expired_voices = $wpdb->get_results($wpdb->prepare(
            "SELECT audio_file_path FROM {$voice_table} 
             WHERE expires_at < %s OR created_at < %s",
            current_time('mysql'),
            $cutoff_date
        ));
        
        // حذف فایل‌های فیزیکی
        foreach ($expired_voices as $voice) {
            if (file_exists($voice->audio_file_path)) {
                unlink($voice->audio_file_path);
            }
        }
        
        // حذف رکوردهای منقضی شده
        $deleted_voices = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$voice_table} 
             WHERE expires_at < %s OR created_at < %s",
            current_time('mysql'),
            $cutoff_date
        ));
        
        // پاک کردن جلسات کامل نشده قدیمی
        $sessions_table = $wpdb->prefix . self::$tables['user_sessions'];
        $deleted_sessions = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$sessions_table} 
             WHERE start_time < %s 
             AND is_completed = 0",
            $cutoff_date
        ));
        
        // پاک کردن حافظه کم اعتماد
        $memory_table = $wpdb->prefix . self::$tables['memory'];
        $deleted_memories = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$memory_table} 
             WHERE last_accessed < %s 
             AND confidence_score < 0.3 
             AND importance_level < 2",
            $cutoff_date
        ));
        
        $cleanup_summary = array(
            'conversations' => $deleted_conversations,
            'voice_samples' => $deleted_voices,
            'sessions' => $deleted_sessions,
            'memories' => $deleted_memories,
            'cleanup_date' => current_time('mysql')
        );
        
        aria_log('Database cleanup completed: ' . json_encode($cleanup_summary), 'info');
        
        return $cleanup_summary;
    }
    
    /**
     * تولید گزارش وضعیت دیتابیس
     */
    public function get_database_status() {
        global $wpdb;
        
        $status = array(
            'version' => get_option('aria_database_version'),
            'tables' => array(),
            'total_size' => 0,
            'total_rows' => 0,
            'health_score' => 0
        );
        
        foreach (self::$tables as $table_key => $table_name) {
            $full_table_name = $wpdb->prefix . $table_name;
            
            // دریافت اطلاعات جدول
            $table_info = $wpdb->get_row($wpdb->prepare(
                "SELECT 
                    table_rows as row_count,
                    data_length + index_length as size_bytes,
                    data_length,
                    index_length
                FROM information_schema.tables 
                WHERE table_schema = %s AND table_name = %s",
                DB_NAME,
                $full_table_name
            ));
            
            if ($table_info) {
                $status['tables'][$table_key] = array(
                    'name' => $table_name,
                    'rows' => intval($table_info->row_count),
                    'size_bytes' => intval($table_info->size_bytes),
                    'size_formatted' => size_format($table_info->size_bytes),
                    'data_size' => intval($table_info->data_length),
                    'index_size' => intval($table_info->index_length)
                );
                
                $status['total_size'] += intval($table_info->size_bytes);
                $status['total_rows'] += intval($table_info->row_count);
            } else {
                $status['tables'][$table_key] = array(
                    'name' => $table_name,
                    'exists' => false
                );
            }
        }
        
        $status['total_size_formatted'] = size_format($status['total_size']);
        $status['health_score'] = $this->calculate_health_score($status);
        
        return $status;
    }
    
    /**
     * محاسبه امتیاز سلامت دیتابیس
     */
    private function calculate_health_score($status) {
        $score = 100;
        
        // کسر امتیاز برای جداول گم شده
        $missing_tables = 0;
        foreach ($status['tables'] as $table) {
            if (isset($table['exists']) && !$table['exists']) {
                $missing_tables++;
            }
        }
        
        $score -= ($missing_tables * 20);
        
        // کسر امتیاز برای اندازه زیاد
        if ($status['total_size'] > 100 * 1024 * 1024) { // بیش از 100MB
            $score -= 10;
        }
        
        // کسر امتیاز برای تعداد رکورد زیاد
        if ($status['total_rows'] > 100000) {
            $score -= 5;
        }
        
        return max(0, min(100, $score));
    }
    
    /**
     * مدیریت نگهداری دیتابیس (AJAX)
     */
    public function handle_database_maintenance() {
        check_ajax_referer('aria_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('دسترسی غیرمجاز', 'aria-chatbot'));
        }
        
        $action = sanitize_text_field($_POST['maintenance_action'] ?? '');
        
        switch ($action) {
            case 'optimize':
                $this->optimize_tables();
                wp_send_json_success(__('جداول بهینه‌سازی شدند', 'aria-chatbot'));
                break;
                
            case 'cleanup':
                $days = intval($_POST['cleanup_days'] ?? 90);
                $result = $this->cleanup_old_data($days);
                wp_send_json_success(array(
                    'message' => __('پاک‌سازی انجام شد', 'aria-chatbot'),
                    'details' => $result
                ));
                break;
                
            case 'status':
                $status = $this->get_database_status();
                wp_send_json_success($status);
                break;
                
            case 'repair':
                $repair_result = $this->repair_tables();
                wp_send_json_success(array(
                    'message' => __('تعمیر جداول انجام شد', 'aria-chatbot'),
                    'details' => $repair_result
                ));
                break;
                
            default:
                wp_send_json_error(__('عملیات نامعتبر', 'aria-chatbot'));
        }
    }
    
    /**
     * تعمیر جداول
     */
    private function repair_tables() {
        global $wpdb;
        
        $repair_results = array();
        
        foreach (self::$tables as $table_key => $table_name) {
            $full_table_name = $wpdb->prefix . $table_name;
            
            // بررسی جدول
            $check_result = $wpdb->get_row("CHECK TABLE {$full_table_name}");
            
            if ($check_result && $check_result->Msg_text !== 'OK') {
                // تعمیر جدول
                $repair_result = $wpdb->get_row("REPAIR TABLE {$full_table_name}");
                $repair_results[$table_key] = array(
                    'table' => $table_name,
                    'status' => $repair_result->Msg_text ?? 'Unknown'
                );
            } else {
                $repair_results[$table_key] = array(
                    'table' => $table_name,
                    'status' => 'OK'
                );
            }
        }
        
        return $repair_results;
    }
    
    /**
     * حذف تمام جداول
     */
    public static function drop_tables() {
        global $wpdb;
        
        foreach (self::$tables as $table_name) {
            $full_table_name = $wpdb->prefix . $table_name;
            $wpdb->query("DROP TABLE IF EXISTS {$full_table_name}");
        }
        
        // حذف گزینه نسخه
        delete_option('aria_database_version');
        
        aria_log('All database tables dropped', 'info');
    }
    
    /**
     * دریافت نام جدول کامل
     */
    public static function get_table_name($table_key) {
        global $wpdb;
        
        if (isset(self::$tables[$table_key])) {
            return $wpdb->prefix . self::$tables[$table_key];
        }
        
        return null;
    }
    
    /**
     * اجرای کوئری امن
     */
    public static function safe_query($query, $params = array()) {
        global $wpdb;
        
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        
        $result = $wpdb->query($query);
        
        if ($wpdb->last_error) {
            aria_log('Database query error: ' . $wpdb->last_error, 'error');
            return false;
        }
        
        return $result;
    }
    
    /**
     * دریافت آمار کلی دیتابیس
     */
    public function get_database_statistics() {
        global $wpdb;
        
        $stats = array();
        
        foreach (self::$tables as $table_key => $table_name) {
            $full_table_name = $wpdb->prefix . $table_name;
            
            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$full_table_name}");
            $stats[$table_key] = intval($count);
        }
        
        return $stats;
    }
    
    /**
     * بک‌آپ دیتابیس
     */
    public function create_backup() {
        global $wpdb;
        
        $backup_data = array(
            'version' => self::DB_VERSION,
            'timestamp' => current_time('mysql'),
            'tables' => array()
        );
        
        foreach (self::$tables as $table_key => $table_name) {
            $full_table_name = $wpdb->prefix . $table_name;
            
            // دریافت ساختار جدول
            $create_table = $wpdb->get_row("SHOW CREATE TABLE {$full_table_name}", ARRAY_N);
            
            // دریافت داده‌ها
            $data = $wpdb->get_results("SELECT * FROM {$full_table_name}", ARRAY_A);
            
            $backup_data['tables'][$table_key] = array(
                'structure' => $create_table[1] ?? '',
                'data' => $data
            );
        }
        
        // ذخیره بک‌آپ
        $upload_dir = wp_upload_dir();
        $backup_dir = $upload_dir['basedir'] . '/aria-chatbot/backups/';
        
        if (!file_exists($backup_dir)) {
            wp_mkdir_p($backup_dir);
        }
        
        $backup_filename = 'aria_database_backup_' . date('Y-m-d_H-i-s') . '.json';
        $backup_path = $backup_dir . $backup_filename;
        
        file_put_contents($backup_path, json_encode($backup_data, JSON_PRETTY_PRINT));
        
        return array(
            'success' => true,
            'filename' => $backup_filename,
            'path' => $backup_path,
            'size' => filesize($backup_path)
        );
    }
}

// راه‌اندازی کلاس
new Aria_Database();