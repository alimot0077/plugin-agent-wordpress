<?php
/**
 * Plugin Name: پشتیبان هوشمند آریا
 * Plugin URI: https://hrnb.ir
 * Description: چت بات هوشمند با قابلیت‌های پیشرفته صوتی، حافظه، شخصیت‌پردازی و یکپارچگی کامل
 * Version: 2.0.0
 * Author: علی مطلقیان
 * Author URI: https://hrnb.ir
 * License: GPL v2 or later
 * Text Domain: aria-chatbot
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit('Access denied.');
}

// تعریف ثابت‌های پلاگین
define('ARIA_CHATBOT_VERSION', '2.0.0');
define('ARIA_CHATBOT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ARIA_CHATBOT_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('ARIA_CHATBOT_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('ARIA_CHATBOT_MIN_PHP_VERSION', '7.4');
define('ARIA_CHATBOT_MIN_WP_VERSION', '5.0');

/**
 * کلاس اصلی پلاگین پشتیبان هوشمند آریا
 */
class Aria_Smart_Assistant {
    
    /**
     * نمونه واحد کلاس
     */
    private static $instance = null;
    
    /**
     * آرایه خطاها
     */
    private $errors = array();
    
    /**
     * وضعیت پلاگین
     */
    private $plugin_ready = false;
    
    /**
     * گرفتن یا ایجاد نمونه واحد
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * سازنده کلاس
     */
    private function __construct() {
        // چک کردن نیازمندی‌ها
        if (!$this->check_requirements()) {
            add_action('admin_notices', array($this, 'show_requirements_notice'));
            return;
        }
        
        // راه‌اندازی پلاگین
        $this->init();
    }
    
    /**
     * بررسی نیازمندی‌های پلاگین
     */
    private function check_requirements() {
        global $wp_version;
        
        // بررسی نسخه PHP
        if (version_compare(PHP_VERSION, ARIA_CHATBOT_MIN_PHP_VERSION, '<')) {
            $this->errors[] = sprintf(
                __('پلاگین پشتیبان هوشمند آریا نیاز به PHP نسخه %s یا بالاتر دارد. نسخه فعلی شما: %s', 'aria-chatbot'),
                ARIA_CHATBOT_MIN_PHP_VERSION,
                PHP_VERSION
            );
            return false;
        }
        
        // بررسی نسخه وردپرس
        if (version_compare($wp_version, ARIA_CHATBOT_MIN_WP_VERSION, '<')) {
            $this->errors[] = sprintf(
                __('پلاگین پشتیبان هوشمند آریا نیاز به وردپرس نسخه %s یا بالاتر دارد. نسخه فعلی شما: %s', 'aria-chatbot'),
                ARIA_CHATBOT_MIN_WP_VERSION,
                $wp_version
            );
            return false;
        }
        
        // بررسی پسوند cURL
        if (!extension_loaded('curl')) {
            $this->errors[] = __('پلاگین پشتیبان هوشمند آریا نیاز به پسوند cURL دارد.', 'aria-chatbot');
            return false;
        }
        
        // بررسی پسوند JSON
        if (!extension_loaded('json')) {
            $this->errors[] = __('پلاگین پشتیبان هوشمند آریا نیاز به پسوند JSON دارد.', 'aria-chatbot');
            return false;
        }
        
        return true;
    }
    
    /**
     * نمایش پیام‌های خطا
     */
    public function show_requirements_notice() {
        if (!empty($this->errors)) {
            foreach ($this->errors as $error) {
                printf(
                    '<div class="notice notice-error"><p><strong>%s:</strong> %s</p></div>',
                    __('خطا', 'aria-chatbot'),
                    $error
                );
            }
        }
    }
    
    /**
     * راه‌اندازی اولیه پلاگین
     */
    private function init() {
        // بارگذاری فایل‌های مورد نیاز
        $this->load_dependencies();
        
        // تنظیم هوک‌ها
        $this->setup_hooks();
        
        // راه‌اندازی پلاگین
        add_action('init', array($this, 'initialize_plugin'));
        
        // فعال‌سازی و غیرفعال‌سازی
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate_plugin'));
        register_uninstall_hook(__FILE__, array('Aria_Smart_Assistant', 'uninstall_plugin'));
        
        $this->plugin_ready = true;
    }
    
    /**
     * بارگذاری فایل‌های وابسته
     */
    private function load_dependencies() {
        // کلاس‌های اصلی
        require_once ARIA_CHATBOT_PLUGIN_PATH . 'includes/core/class-aria-database.php';
        require_once ARIA_CHATBOT_PLUGIN_PATH . 'includes/core/class-aria-admin.php';
        require_once ARIA_CHATBOT_PLUGIN_PATH . 'includes/core/class-aria-frontend.php';
        require_once ARIA_CHATBOT_PLUGIN_PATH . 'includes/core/class-aria-api-handler.php';
        require_once ARIA_CHATBOT_PLUGIN_PATH . 'includes/core/class-aria-voice-handler.php';
        require_once ARIA_CHATBOT_PLUGIN_PATH . 'includes/core/class-aria-memory-manager.php';
        require_once ARIA_CHATBOT_PLUGIN_PATH . 'includes/core/class-aria-personality-engine.php';
        require_once ARIA_CHATBOT_PLUGIN_PATH . 'includes/core/class-aria-analytics.php';
        require_once ARIA_CHATBOT_PLUGIN_PATH . 'includes/core/class-aria-security.php';
        require_once ARIA_CHATBOT_PLUGIN_PATH . 'includes/core/class-aria-performance.php';
        
        // یکپارچگی‌ها
        require_once ARIA_CHATBOT_PLUGIN_PATH . 'includes/integrations/class-aria-woocommerce.php';
        require_once ARIA_CHATBOT_PLUGIN_PATH . 'includes/integrations/class-aria-lms.php';
        require_once ARIA_CHATBOT_PLUGIN_PATH . 'includes/integrations/class-aria-social-media.php';
        require_once ARIA_CHATBOT_PLUGIN_PATH . 'includes/integrations/class-aria-crm.php';
        
        // ابزارها
        require_once ARIA_CHATBOT_PLUGIN_PATH . 'includes/helpers/class-aria-helpers.php';
        require_once ARIA_CHATBOT_PLUGIN_PATH . 'includes/helpers/class-aria-validator.php';
        require_once ARIA_CHATBOT_PLUGIN_PATH . 'includes/helpers/class-aria-sanitizer.php';
    }
    
    /**
     * تنظیم هوک‌ها
     */
    private function setup_hooks() {
        // بارگذاری زبان‌ها
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // اضافه کردن لینک تنظیمات
        add_filter('plugin_action_links_' . ARIA_CHATBOT_PLUGIN_BASENAME, array($this, 'add_action_links'));
        
        // هوک‌های AJAX
        add_action('wp_ajax_aria_test_connection', array($this, 'test_api_connection'));
        add_action('wp_ajax_aria_save_settings', array($this, 'save_settings'));
        add_action('wp_ajax_aria_reset_settings', array($this, 'reset_settings'));
        add_action('wp_ajax_aria_export_settings', array($this, 'export_settings'));
        add_action('wp_ajax_aria_import_settings', array($this, 'import_settings'));
        
        // هوک‌های عمومی AJAX
        add_action('wp_ajax_nopriv_aria_send_message', array($this, 'handle_public_message'));
        add_action('wp_ajax_aria_send_message', array($this, 'handle_public_message'));
        
        // هوک‌های سفارشی
        add_action('aria_daily_cleanup', array($this, 'daily_cleanup'));
        add_action('aria_weekly_analytics', array($this, 'weekly_analytics'));
    }
    
    /**
     * راه‌اندازی پلاگین
     */
    public function initialize_plugin() {
        if (!$this->plugin_ready) {
            return;
        }
        
        // راه‌اندازی کلاس‌ها
        new Aria_Database();
        
        if (is_admin()) {
            new Aria_Admin();
        } else {
            new Aria_Frontend();
        }
        
        new Aria_API_Handler();
        new Aria_Voice_Handler();
        new Aria_Memory_Manager();
        new Aria_Personality_Engine();
        new Aria_Analytics();
        new Aria_Security();
        new Aria_Performance();
        
        // یکپارچگی‌ها
        if (class_exists('WooCommerce')) {
            new Aria_WooCommerce_Integration();
        }
        
        if (class_exists('LearnPress')) {
            new Aria_LMS_Integration();
        }
        
        new Aria_Social_Media_Integration();
        new Aria_CRM_Integration();
        
        // تنظیم برنامه‌های زمان‌بندی شده
        if (!wp_next_scheduled('aria_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'aria_daily_cleanup');
        }
        
        if (!wp_next_scheduled('aria_weekly_analytics')) {
            wp_schedule_event(time(), 'weekly', 'aria_weekly_analytics');
        }
    }
    
    /**
     * بارگذاری فایل‌های ترجمه
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'aria-chatbot',
            false,
            dirname(ARIA_CHATBOT_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * اضافه کردن لینک‌های عمل
     */
    public function add_action_links($links) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=aria-chatbot'),
            __('تنظیمات', 'aria-chatbot')
        );
        
        $support_link = sprintf(
            '<a href="%s" target="_blank">%s</a>',
            'https://hrnb.ir/support',
            __('پشتیبانی', 'aria-chatbot')
        );
        
        array_unshift($links, $settings_link, $support_link);
        
        return $links;
    }
    
    /**
     * فعال‌سازی پلاگین
     */
    public function activate_plugin() {
        // ایجاد جداول دیتابیس
        Aria_Database::create_tables();
        
        // تنظیم تنظیمات پیش‌فرض
        $this->set_default_options();
        
        // ایجاد دایرکتوری‌های مورد نیاز
        $this->create_directories();
        
        // تنظیم مجوزهای فایل
        $this->set_file_permissions();
        
        // به‌روزرسانی نسخه
        update_option('aria_chatbot_version', ARIA_CHATBOT_VERSION);
        
        // فلاش کردن قوانین rewrite
        flush_rewrite_rules();
        
        // لاگ فعال‌سازی
        $this->log_activation();
    }
    
    /**
     * غیرفعال‌سازی پلاگین
     */
    public function deactivate_plugin() {
        // حذف برنامه‌های زمان‌بندی شده
        wp_clear_scheduled_hook('aria_daily_cleanup');
        wp_clear_scheduled_hook('aria_weekly_analytics');
        
        // پاک کردن کش‌ها
        wp_cache_flush();
        
        // فلاش کردن قوانین rewrite
        flush_rewrite_rules();
        
        // لاگ غیرفعال‌سازی
        error_log('Aria Chatbot Plugin Deactivated at ' . current_time('mysql'));
    }
    
    /**
     * حذف کامل پلاگین
     */
    public static function uninstall_plugin() {
        // حذف تنظیمات
        $options_to_delete = array(
            'aria_chatbot_options',
            'aria_chatbot_voice_options',
            'aria_chatbot_personality_options',
            'aria_chatbot_design_options',
            'aria_chatbot_security_options',
            'aria_chatbot_integrations_options',
            'aria_chatbot_advanced_options',
            'aria_chatbot_version'
        );
        
        foreach ($options_to_delete as $option) {
            delete_option($option);
        }
        
        // حذف transient ها
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_aria_chatbot_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_aria_chatbot_%'");
        
        // حذف جداول دیتابیس (اختیاری)
        $keep_data = get_option('aria_chatbot_keep_data_on_uninstall', false);
        if (!$keep_data) {
            Aria_Database::drop_tables();
        }
        
        // حذف فایل‌ها
        self::remove_plugin_files();
    }
    
    /**
     * تنظیم گزینه‌های پیش‌فرض
     */
    private function set_default_options() {
        // تنظیمات اصلی
        if (!get_option('aria_chatbot_options')) {
            $default_options = array(
                'enabled' => true,
                'openai_api_key' => '',
                'openai_model' => 'gpt-4.1',
                'max_tokens' => 1000,
                'temperature' => 0.7,
                'site_type' => 'general',
                'about_us' => '',
                'custom_instructions' => '',
                'rate_limit' => 50,
                'session_timeout' => 3600,
                'auto_detect_language' => true,
                'fallback_language' => 'fa-IR'
            );
            
            update_option('aria_chatbot_options', $default_options);
        }
        
        // تنظیمات صوتی
        if (!get_option('aria_chatbot_voice_options')) {
            $voice_options = array(
                'voice_input_enabled' => true,
                'tts_enabled' => true,
                'language' => 'fa-IR',
                'voice_gender' => 'female',
                'speech_rate' => 1.0,
                'speech_pitch' => 1.0,
                'speech_volume' => 0.8,
                'auto_play_responses' => false,
                'voice_commands_enabled' => true,
                'noise_reduction' => true
            );
            
            update_option('aria_chatbot_voice_options', $voice_options);
        }
        
        // تنظیمات شخصیت
        if (!get_option('aria_chatbot_personality_options')) {
            $personality_options = array(
                'bot_name' => 'آریا',
                'personality_type' => 'friendly',
                'tone' => 'professional',
                'humor_level' => 3,
                'formality_level' => 3,
                'empathy_level' => 4,
                'emoji_usage' => 'moderate',
                'greeting_style' => 'warm',
                'custom_traits' => '',
                'conversation_starters' => array(
                    'سلام! چطور می‌تونم کمکتون کنم؟',
                    'چه سوالی دارید؟',
                    'کدوم بخش رو می‌خواین بررسی کنیم؟'
                )
            );
            
            update_option('aria_chatbot_personality_options', $personality_options);
        }
        
        // تنظیمات طراحی
        if (!get_option('aria_chatbot_design_options')) {
            $design_options = array(
                'position' => 'bottom-right',
                'theme' => 'modern',
                'primary_color' => '#667eea',
                'secondary_color' => '#f8f9fa',
                'accent_color' => '#764ba2',
                'text_color' => '#333333',
                'font_family' => 'system-ui',
                'font_size' => '14px',
                'border_radius' => '12px',
                'animation_type' => 'slide',
                'show_avatar' => true,
                'custom_avatar' => '',
                'width' => '350px',
                'height' => '500px',
                'z_index' => 9999
            );
            
            update_option('aria_chatbot_design_options', $design_options);
        }
    }
    
    /**
     * ایجاد دایرکتوری‌های مورد نیاز
     */
    private function create_directories() {
        $upload_dir = wp_upload_dir();
        $directories = array(
            $upload_dir['basedir'] . '/aria-chatbot/',
            $upload_dir['basedir'] . '/aria-chatbot/voices/',
            $upload_dir['basedir'] . '/aria-chatbot/avatars/',
            $upload_dir['basedir'] . '/aria-chatbot/logs/',
            $upload_dir['basedir'] . '/aria-chatbot/backups/',
            $upload_dir['basedir'] . '/aria-chatbot/temp/'
        );
        
        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
                
                // ایجاد فایل .htaccess برای امنیت
                $htaccess_content = "Options -Indexes\n<Files *.php>\nDeny from all\n</Files>";
                file_put_contents($dir . '.htaccess', $htaccess_content);
                
                // ایجاد فایل index.php خالی
                file_put_contents($dir . 'index.php', '<?php // Silence is golden');
            }
        }
    }
    
    /**
     * تنظیم مجوزهای فایل
     */
    private function set_file_permissions() {
        $upload_dir = wp_upload_dir();
        $aria_dir = $upload_dir['basedir'] . '/aria-chatbot/';
        
        if (file_exists($aria_dir)) {
            chmod($aria_dir, 0755);
            
            $subdirs = array('voices', 'avatars', 'logs', 'backups', 'temp');
            foreach ($subdirs as $subdir) {
                $path = $aria_dir . $subdir . '/';
                if (file_exists($path)) {
                    chmod($path, 0755);
                }
            }
        }
    }
    
    /**
     * لاگ فعال‌سازی
     */
    private function log_activation() {
        $log_data = array(
            'timestamp' => current_time('mysql'),
            'plugin_version' => ARIA_CHATBOT_VERSION,
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'site_url' => get_site_url(),
            'user_id' => get_current_user_id()
        );
        
        update_option('aria_chatbot_activation_log', $log_data);
        
        // ارسال آمار ناشناس (اختیاری)
        if (get_option('aria_chatbot_allow_tracking', false)) {
            wp_remote_post('https://hrnb.ir/api/activation', array(
                'body' => json_encode($log_data),
                'headers' => array('Content-Type' => 'application/json'),
                'timeout' => 5
            ));
        }
    }
    
    /**
     * حذف فایل‌های پلاگین
     */
    private static function remove_plugin_files() {
        $upload_dir = wp_upload_dir();
        $aria_dir = $upload_dir['basedir'] . '/aria-chatbot/';
        
        if (file_exists($aria_dir)) {
            self::recursive_rmdir($aria_dir);
        }
    }
    
    /**
     * حذف بازگشتی دایرکتوری
     */
    private static function recursive_rmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object)) {
                        self::recursive_rmdir($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }
    
    /**
     * تمیزکاری روزانه
     */
    public function daily_cleanup() {
        // پاک کردن لاگ‌های قدیمی
        $this->cleanup_old_logs();
        
        // پاک کردن فایل‌های موقت
        $this->cleanup_temp_files();
        
        // بهینه‌سازی دیتابیس
        $this->optimize_database();
    }
    
    /**
     * آنالیز هفتگی
     */
    public function weekly_analytics() {
        // ایجاد گزارش هفتگی
        $analytics = new Aria_Analytics();
        $analytics->generate_weekly_report();
        
        // ارسال گزارش به ایمیل (اختیاری)
        if (get_option('aria_chatbot_email_reports', false)) {
            $this->send_weekly_report();
        }
    }
    
    /**
     * پاک کردن لاگ‌های قدیمی
     */
    private function cleanup_old_logs() {
        $upload_dir = wp_upload_dir();
        $logs_dir = $upload_dir['basedir'] . '/aria-chatbot/logs/';
        
        if (file_exists($logs_dir)) {
            $files = glob($logs_dir . '*.log');
            $cutoff_time = strtotime('-30 days');
            
            foreach ($files as $file) {
                if (filemtime($file) < $cutoff_time) {
                    unlink($file);
                }
            }
        }
    }
    
    /**
     * پاک کردن فایل‌های موقت
     */
    private function cleanup_temp_files() {
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/aria-chatbot/temp/';
        
        if (file_exists($temp_dir)) {
            $files = glob($temp_dir . '*');
            $cutoff_time = strtotime('-1 day');
            
            foreach ($files as $file) {
                if (is_file($file) && filemtime($file) < $cutoff_time) {
                    unlink($file);
                }
            }
        }
    }
    
    /**
     * بهینه‌سازی دیتابیس
     */
    private function optimize_database() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'aria_conversations',
            $wpdb->prefix . 'aria_memory',
            $wpdb->prefix . 'aria_analytics',
            $wpdb->prefix . 'aria_knowledge_base'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("OPTIMIZE TABLE {$table}");
        }
    }
    
    /**
     * بررسی سلامت پلاگین
     */
    public function health_check() {
        $health_data = array(
            'plugin_version' => ARIA_CHATBOT_VERSION,
            'database_status' => $this->check_database_status(),
            'api_status' => $this->check_api_status(),
            'file_permissions' => $this->check_file_permissions(),
            'memory_usage' => memory_get_usage(true),
            'active_sessions' => $this->count_active_sessions()
        );
        
        return $health_data;
    }
    
    /**
     * گرفتن اطلاعات پلاگین
     */
    public static function get_plugin_info() {
        return array(
            'name' => 'پشتیبان هوشمند آریا',
            'version' => ARIA_CHATBOT_VERSION,
            'author' => 'علی مطلقیان',
            'url' => 'https://hrnb.ir',
            'description' => 'چت بات هوشمند با قابلیت‌های پیشرفته',
            'text_domain' => 'aria-chatbot'
        );
    }
}

// راه‌اندازی پلاگین
function aria_chatbot_init() {
    return Aria_Smart_Assistant::get_instance();
}

// شروع پلاگین
add_action('plugins_loaded', 'aria_chatbot_init', 10);

// توابع کمکی سراسری
if (!function_exists('aria_get_option')) {
    function aria_get_option($option_name, $default = null) {
        $options = get_option('aria_chatbot_options', array());
        return isset($options[$option_name]) ? $options[$option_name] : $default;
    }
}

if (!function_exists('aria_update_option')) {
    function aria_update_option($option_name, $value) {
        $options = get_option('aria_chatbot_options', array());
        $options[$option_name] = $value;
        return update_option('aria_chatbot_options', $options);
    }
}

if (!function_exists('aria_log')) {
    function aria_log($message, $type = 'info') {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        $upload_dir = wp_upload_dir();
        $log_file = $upload_dir['basedir'] . '/aria-chatbot/logs/aria-' . date('Y-m-d') . '.log';
        
        $log_entry = sprintf(
            "[%s] [%s] %s\n",
            current_time('mysql'),
            strtoupper($type),
            $message
        );
        
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
}

// پایان فایل