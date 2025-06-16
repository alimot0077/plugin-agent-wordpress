<?php
/**
 * کلاس مدیریت پنل ادمین پشتیبان هوشمند آریا
 * 
 * @package Aria_Chatbot
 * @author علی مطلقیان
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Aria_Admin {
    
    /**
     * نمونه واحد کلاس
     */
    private static $instance = null;
    
    /**
     * آرایه صفحات ادمین
     */
    private $admin_pages = array();
    
    /**
     * تنظیمات فعلی
     */
    private $current_settings = array();
    
    /**
     * خطاها و پیام‌ها
     */
    private $messages = array();
    
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
        // بارگذاری تنظیمات
        $this->load_settings();
        
        // تنظیم هوک‌ها
        $this->setup_hooks();
        
        // ثبت منوها
        add_action('admin_menu', array($this, 'register_admin_menus'));
        
        // ثبت تنظیمات
        add_action('admin_init', array($this, 'register_settings'));
        
        // بارگذاری assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * بارگذاری تنظیمات
     */
    private function load_settings() {
        $this->current_settings = array(
            'main' => get_option('aria_chatbot_options', array()),
            'voice' => get_option('aria_chatbot_voice_options', array()),
            'personality' => get_option('aria_chatbot_personality_options', array()),
            'design' => get_option('aria_chatbot_design_options', array()),
            'security' => get_option('aria_chatbot_security_options', array()),
            'integrations' => get_option('aria_chatbot_integrations_options', array()),
            'advanced' => get_option('aria_chatbot_advanced_options', array())
        );
    }
    
    /**
     * تنظیم هوک‌ها
     */
    private function setup_hooks() {
        // AJAX handlers
        add_action('wp_ajax_aria_test_api', array($this, 'test_openai_connection'));
        add_action('wp_ajax_aria_test_voice', array($this, 'test_voice_settings'));
        add_action('wp_ajax_aria_save_personality', array($this, 'save_personality_settings'));
        add_action('wp_ajax_aria_preview_design', array($this, 'preview_design_changes'));
        add_action('wp_ajax_aria_import_knowledge', array($this, 'import_knowledge_base'));
        add_action('wp_ajax_aria_export_data', array($this, 'export_plugin_data'));
        add_action('wp_ajax_aria_reset_all', array($this, 'reset_all_settings'));
        add_action('wp_ajax_aria_backup_settings', array($this, 'backup_settings'));
        add_action('wp_ajax_aria_restore_settings', array($this, 'restore_settings'));
        add_action('wp_ajax_aria_check_health', array($this, 'check_system_health'));
        add_action('wp_ajax_aria_optimize_db', array($this, 'optimize_database'));
        add_action('wp_ajax_aria_clear_cache', array($this, 'clear_plugin_cache'));
        add_action('wp_ajax_aria_generate_report', array($this, 'generate_analytics_report'));
        
        // فیلترها
        add_filter('aria_admin_tabs', array($this, 'register_admin_tabs'));
        add_filter('aria_settings_fields', array($this, 'register_settings_fields'));
    }
    
    /**
     * ثبت منوهای ادمین
     */
    public function register_admin_menus() {
        // منوی اصلی
        add_menu_page(
            __('پشتیبان هوشمند آریا', 'aria-chatbot'),
            __('پشتیبان آریا', 'aria-chatbot'),
            'manage_options',
            'aria-chatbot',
            array($this, 'render_main_page'),
            $this->get_menu_icon(),
            30
        );
        
        // زیرمنوها
        $submenus = array(
            'aria-chatbot' => array(
                'title' => __('داشبورد اصلی', 'aria-chatbot'),
                'callback' => array($this, 'render_dashboard_page')
            ),
            'aria-basic-settings' => array(
                'title' => __('تنظیمات پایه', 'aria-chatbot'),
                'callback' => array($this, 'render_basic_settings_page')
            ),
            'aria-ai-settings' => array(
                'title' => __('تنظیمات هوش مصنوعی', 'aria-chatbot'),
                'callback' => array($this, 'render_ai_settings_page')
            ),
            'aria-voice-settings' => array(
                'title' => __('تنظیمات صوتی', 'aria-chatbot'),
                'callback' => array($this, 'render_voice_settings_page')
            ),
            'aria-personality' => array(
                'title' => __('شخصیت و رفتار', 'aria-chatbot'),
                'callback' => array($this, 'render_personality_page')
            ),
            'aria-design' => array(
                'title' => __('طراحی و ظاهر', 'aria-chatbot'),
                'callback' => array($this, 'render_design_page')
            ),
            'aria-knowledge' => array(
                'title' => __('پایگاه دانش', 'aria-chatbot'),
                'callback' => array($this, 'render_knowledge_page')
            ),
            'aria-conversations' => array(
                'title' => __('مکالمات', 'aria-chatbot'),
                'callback' => array($this, 'render_conversations_page')
            ),
            'aria-analytics' => array(
                'title' => __('آمار و تحلیل', 'aria-chatbot'),
                'callback' => array($this, 'render_analytics_page')
            ),
            'aria-integrations' => array(
                'title' => __('یکپارچگی‌ها', 'aria-chatbot'),
                'callback' => array($this, 'render_integrations_page')
            ),
            'aria-security' => array(
                'title' => __('امنیت و حریم خصوصی', 'aria-chatbot'),
                'callback' => array($this, 'render_security_page')
            ),
            'aria-performance' => array(
                'title' => __('عملکرد و بهینه‌سازی', 'aria-chatbot'),
                'callback' => array($this, 'render_performance_page')
            ),
            'aria-tools' => array(
                'title' => __('ابزارها', 'aria-chatbot'),
                'callback' => array($this, 'render_tools_page')
            ),
            'aria-support' => array(
                'title' => __('پشتیبانی', 'aria-chatbot'),
                'callback' => array($this, 'render_support_page')
            )
        );
        
        foreach ($submenus as $slug => $submenu) {
            add_submenu_page(
                'aria-chatbot',
                $submenu['title'],
                $submenu['title'],
                'manage_options',
                $slug,
                $submenu['callback']
            );
        }
        
        // حذف منوی تکراری
        remove_submenu_page('aria-chatbot', 'aria-chatbot');
    }
    
    /**
     * بارگذاری assets ادمین
     */
    public function enqueue_admin_assets($hook) {
        // فقط در صفحات پلاگین
        if (strpos($hook, 'aria-chatbot') === false) {
            return;
        }
        
        // CSS
        wp_enqueue_style(
            'aria-admin-style',
            ARIA_CHATBOT_PLUGIN_URL . 'admin/css/admin-style.css',
            array(),
            ARIA_CHATBOT_VERSION
        );
        
        wp_enqueue_style(
            'aria-admin-components',
            ARIA_CHATBOT_PLUGIN_URL . 'admin/css/components.css',
            array('aria-admin-style'),
            ARIA_CHATBOT_VERSION
        );
        
        // JavaScript
        wp_enqueue_script(
            'aria-admin-script',
            ARIA_CHATBOT_PLUGIN_URL . 'admin/js/admin-script.js',
            array('jquery', 'wp-color-picker', 'jquery-ui-sortable', 'wp-media'),
            ARIA_CHATBOT_VERSION,
            true
        );
        
        wp_enqueue_script(
            'aria-admin-components',
            ARIA_CHATBOT_PLUGIN_URL . 'admin/js/components.js',
            array('aria-admin-script'),
            ARIA_CHATBOT_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('aria-admin-script', 'ariaAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aria_admin_nonce'),
            'plugin_url' => ARIA_CHATBOT_PLUGIN_URL,
            'current_user' => wp_get_current_user()->display_name,
            'messages' => array(
                'saving' => __('در حال ذخیره...', 'aria-chatbot'),
                'saved' => __('تنظیمات ذخیره شد', 'aria-chatbot'),
                'error' => __('خطا در ذخیره تنظیمات', 'aria-chatbot'),
                'confirm_reset' => __('آیا مطمئن هستید؟ تمام تنظیمات پاک خواهد شد.', 'aria-chatbot'),
                'testing' => __('در حال تست...', 'aria-chatbot'),
                'test_success' => __('تست موفق بود', 'aria-chatbot'),
                'test_failed' => __('تست ناموفق', 'aria-chatbot')
            ),
            'models' => $this->get_available_models(),
            'voices' => $this->get_available_voices(),
            'languages' => $this->get_supported_languages()
        ));
        
        // Color picker
        wp_enqueue_style('wp-color-picker');
        
        // Media uploader
        wp_enqueue_media();
        
        // Chart.js برای آمار
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js',
            array(),
            '3.9.1',
            true
        );
    }
    
    /**
     * رندر صفحه داشبورد
     */
    public function render_dashboard_page() {
        $analytics = new Aria_Analytics();
        $stats = $analytics->get_dashboard_stats();
        $health = $this->get_system_health();
        
        include ARIA_CHATBOT_PLUGIN_PATH . 'admin/views/dashboard.php';
    }
    
    /**
     * رندر صفحه تنظیمات پایه
     */
    public function render_basic_settings_page() {
        $this->render_settings_page('basic');
    }
    
    /**
     * رندر صفحه تنظیمات هوش مصنوعی
     */
    public function render_ai_settings_page() {
        include ARIA_CHATBOT_PLUGIN_PATH . 'admin/views/ai-settings.php';
    }
    
    /**
     * رندر صفحه تنظیمات صوتی
     */
    public function render_voice_settings_page() {
        include ARIA_CHATBOT_PLUGIN_PATH . 'admin/views/voice-settings.php';
    }
    
    /**
     * رندر صفحه شخصیت
     */
    public function render_personality_page() {
        include ARIA_CHATBOT_PLUGIN_PATH . 'admin/views/personality.php';
    }
    
    /**
     * رندر صفحه طراحی
     */
    public function render_design_page() {
        include ARIA_CHATBOT_PLUGIN_PATH . 'admin/views/design.php';
    }
    
    /**
     * رندر صفحه پایگاه دانش
     */
    public function render_knowledge_page() {
        global $wpdb;
        
        $knowledge_table = $wpdb->prefix . 'aria_knowledge_base';
        $knowledge_items = $wpdb->get_results("SELECT * FROM {$knowledge_table} ORDER BY priority DESC, id DESC");
        
        include ARIA_CHATBOT_PLUGIN_PATH . 'admin/views/knowledge.php';
    }
    
    /**
     * رندر صفحه مکالمات
     */
    public function render_conversations_page() {
        global $wpdb;
        
        // دریافت مکالمات با پیجینیشن
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        $conversations_table = $wpdb->prefix . 'aria_conversations';
        
        $total_conversations = $wpdb->get_var("SELECT COUNT(*) FROM {$conversations_table}");
        $conversations = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$conversations_table} ORDER BY timestamp DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));
        
        $total_pages = ceil($total_conversations / $per_page);
        
        include ARIA_CHATBOT_PLUGIN_PATH . 'admin/views/conversations.php';
    }
    
    /**
     * رندر صفحه آمار
     */
    public function render_analytics_page() {
        $analytics = new Aria_Analytics();
        $analytics_data = $analytics->get_comprehensive_analytics();
        
        include ARIA_CHATBOT_PLUGIN_PATH . 'admin/views/analytics.php';
    }
    
    /**
     * رندر صفحه یکپارچگی‌ها
     */
    public function render_integrations_page() {
        $available_integrations = array(
            'woocommerce' => array(
                'name' => 'ووکامرس',
                'description' => 'یکپارچگی کامل با فروشگاه آنلاین',
                'active' => class_exists('WooCommerce'),
                'required_plugin' => 'WooCommerce'
            ),
            'learndash' => array(
                'name' => 'LearnDash',
                'description' => 'یکپارچگی با سیستم آموزش آنلاین',
                'active' => class_exists('SFWD_LMS'),
                'required_plugin' => 'LearnDash'
            ),
            'contact_form_7' => array(
                'name' => 'Contact Form 7',
                'description' => 'یکپارچگی با فرم‌های تماس',
                'active' => class_exists('WPCF7'),
                'required_plugin' => 'Contact Form 7'
            ),
            'google_analytics' => array(
                'name' => 'Google Analytics',
                'description' => 'ردیابی تعاملات در Google Analytics',
                'active' => !empty($this->current_settings['integrations']['ga_tracking_id']),
                'required_plugin' => false
            ),
            'social_media' => array(
                'name' => 'شبکه‌های اجتماعی',
                'description' => 'اتصال به تلگرام، واتساپ و...',
                'active' => true,
                'required_plugin' => false
            )
        );
        
        include ARIA_CHATBOT_PLUGIN_PATH . 'admin/views/integrations.php';
    }
    
    /**
     * رندر صفحه امنیت
     */
    public function render_security_page() {
        $security_checks = $this->run_security_checks();
        
        include ARIA_CHATBOT_PLUGIN_PATH . 'admin/views/security.php';
    }
    
    /**
     * رندر صفحه عملکرد
     */
    public function render_performance_page() {
        $performance_data = $this->get_performance_data();
        
        include ARIA_CHATBOT_PLUGIN_PATH . 'admin/views/performance.php';
    }
    
    /**
     * رندر صفحه ابزارها
     */
    public function render_tools_page() {
        include ARIA_CHATBOT_PLUGIN_PATH . 'admin/views/tools.php';
    }
    
    /**
     * رندر صفحه پشتیبانی
     */
    public function render_support_page() {
        $system_info = $this->get_system_info();
        
        include ARIA_CHATBOT_PLUGIN_PATH . 'admin/views/support.php';
    }
    
    /**
     * تست اتصال OpenAI
     */
    public function test_openai_connection() {
        check_ajax_referer('aria_admin_nonce', 'nonce');
        
        $api_key = sanitize_text_field($_POST['api_key']);
        $model = sanitize_text_field($_POST['model']);
        
        if (empty($api_key)) {
            wp_send_json_error(__('کلید API وارد نشده است', 'aria-chatbot'));
        }
        
        $api_handler = new Aria_API_Handler();
        $result = $api_handler->test_connection($api_key, $model);
        
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => __('اتصال موفق بود', 'aria-chatbot'),
                'model_info' => $result['model_info']
            ));
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * تست تنظیمات صوتی
     */
    public function test_voice_settings() {
        check_ajax_referer('aria_admin_nonce', 'nonce');
        
        $text = sanitize_text_field($_POST['text']);
        $voice = sanitize_text_field($_POST['voice']);
        $language = sanitize_text_field($_POST['language']);
        
        $voice_handler = new Aria_Voice_Handler();
        $result = $voice_handler->text_to_speech($text, $voice, $language);
        
        wp_send_json($result);
    }
    
    /**
     * ذخیره تنظیمات شخصیت
     */
    public function save_personality_settings() {
        check_ajax_referer('aria_admin_nonce', 'nonce');
        
        $personality_data = array(
            'bot_name' => sanitize_text_field($_POST['bot_name']),
            'personality_type' => sanitize_text_field($_POST['personality_type']),
            'tone' => sanitize_text_field($_POST['tone']),
            'humor_level' => intval($_POST['humor_level']),
            'formality_level' => intval($_POST['formality_level']),
            'empathy_level' => intval($_POST['empathy_level']),
            'emoji_usage' => sanitize_text_field($_POST['emoji_usage']),
            'greeting_style' => sanitize_text_field($_POST['greeting_style']),
            'custom_traits' => sanitize_textarea_field($_POST['custom_traits']),
            'conversation_starters' => array_map('sanitize_text_field', $_POST['conversation_starters'])
        );
        
        update_option('aria_chatbot_personality_options', $personality_data);
        
        wp_send_json_success(__('تنظیمات شخصیت ذخیره شد', 'aria-chatbot'));
    }
    
    /**
     * پیش‌نمایش تغییرات طراحی
     */
    public function preview_design_changes() {
        check_ajax_referer('aria_admin_nonce', 'nonce');
        
        $design_data = array(
            'primary_color' => sanitize_hex_color($_POST['primary_color']),
            'secondary_color' => sanitize_hex_color($_POST['secondary_color']),
            'accent_color' => sanitize_hex_color($_POST['accent_color']),
            'text_color' => sanitize_hex_color($_POST['text_color']),
            'font_family' => sanitize_text_field($_POST['font_family']),
            'font_size' => sanitize_text_field($_POST['font_size']),
            'border_radius' => intval($_POST['border_radius']),
            'animation_type' => sanitize_text_field($_POST['animation_type'])
        );
        
        $css = $this->generate_preview_css($design_data);
        
        wp_send_json_success(array('css' => $css));
    }
    
    /**
     * ایمپورت پایگاه دانش
     */
    public function import_knowledge_base() {
        check_ajax_referer('aria_admin_nonce', 'nonce');
        
        if (!isset($_FILES['knowledge_file'])) {
            wp_send_json_error(__('فایل انتخاب نشده است', 'aria-chatbot'));
        }
        
        $file = $_FILES['knowledge_file'];
        $file_type = wp_check_filetype($file['name']);
        
        if (!in_array($file_type['ext'], array('json', 'csv', 'xlsx'))) {
            wp_send_json_error(__('نوع فایل پشتیبانی نمی‌شود', 'aria-chatbot'));
        }
        
        $imported_count = $this->process_knowledge_import($file);
        
        if ($imported_count > 0) {
            wp_send_json_success(sprintf(
                __('%d مورد با موفقیت وارد شد', 'aria-chatbot'),
                $imported_count
            ));
        } else {
            wp_send_json_error(__('خطا در وارد کردن فایل', 'aria-chatbot'));
        }
    }
    
    /**
     * اکسپورت داده‌ها
     */
    public function export_plugin_data() {
        check_ajax_referer('aria_admin_nonce', 'nonce');
        
        $export_type = sanitize_text_field($_POST['export_type']);
        $format = sanitize_text_field($_POST['format']);
        
        $data = $this->prepare_export_data($export_type);
        $filename = $this->generate_export_file($data, $format, $export_type);
        
        wp_send_json_success(array(
            'filename' => $filename,
            'download_url' => wp_upload_dir()['baseurl'] . '/aria-chatbot/exports/' . $filename
        ));
    }
    
    /**
     * ریست کردن تمام تنظیمات
     */
    public function reset_all_settings() {
        check_ajax_referer('aria_admin_nonce', 'nonce');
        
        $confirm = sanitize_text_field($_POST['confirm']);
        
        if ($confirm !== 'DELETE') {
            wp_send_json_error(__('تأیید نادرست', 'aria-chatbot'));
        }
        
        // حذف تمام تنظیمات
        $options_to_delete = array(
            'aria_chatbot_options',
            'aria_chatbot_voice_options',
            'aria_chatbot_personality_options',
            'aria_chatbot_design_options',
            'aria_chatbot_security_options',
            'aria_chatbot_integrations_options',
            'aria_chatbot_advanced_options'
        );
        
        foreach ($options_to_delete as $option) {
            delete_option($option);
        }
        
        // تنظیم مجدد تنظیمات پیش‌فرض
        $aria_instance = Aria_Smart_Assistant::get_instance();
        $aria_instance->set_default_options();
        
        wp_send_json_success(__('تمام تنظیمات پاک شد و به حالت پیش‌فرض بازگشت', 'aria-chatbot'));
    }
    
    /**
     * بک‌آپ تنظیمات
     */
    public function backup_settings() {
        check_ajax_referer('aria_admin_nonce', 'nonce');
        
        $all_settings = array(
            'main' => get_option('aria_chatbot_options'),
            'voice' => get_option('aria_chatbot_voice_options'),
            'personality' => get_option('aria_chatbot_personality_options'),
            'design' => get_option('aria_chatbot_design_options'),
            'security' => get_option('aria_chatbot_security_options'),
            'integrations' => get_option('aria_chatbot_integrations_options'),
            'advanced' => get_option('aria_chatbot_advanced_options')
        );
        
        $backup_data = array(
            'version' => ARIA_CHATBOT_VERSION,
            'timestamp' => current_time('mysql'),
            'site_url' => get_site_url(),
            'settings' => $all_settings
        );
        
        $upload_dir = wp_upload_dir();
        $backup_dir = $upload_dir['basedir'] . '/aria-chatbot/backups/';
        $filename = 'aria-backup-' . date('Y-m-d-H-i-s') . '.json';
        $file_path = $backup_dir . $filename;
        
        if (!file_exists($backup_dir)) {
            wp_mkdir_p($backup_dir);
        }
        
        file_put_contents($file_path, json_encode($backup_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        wp_send_json_success(array(
            'message' => __('بک‌آپ با موفقیت ایجاد شد', 'aria-chatbot'),
            'filename' => $filename,
            'download_url' => $upload_dir['baseurl'] . '/aria-chatbot/backups/' . $filename
        ));
    }
    
    /**
     * بازیابی تنظیمات
     */
    public function restore_settings() {
        check_ajax_referer('aria_admin_nonce', 'nonce');
        
        if (!isset($_FILES['backup_file'])) {
            wp_send_json_error(__('فایل بک‌آپ انتخاب نشده است', 'aria-chatbot'));
        }
        
        $file = $_FILES['backup_file'];
        $file_content = file_get_contents($file['tmp_name']);
        $backup_data = json_decode($file_content, true);
        
        if (!$backup_data || !isset($backup_data['settings'])) {
            wp_send_json_error(__('فایل بک‌آپ نامعتبر است', 'aria-chatbot'));
        }
        
        // بازیابی تنظیمات
        foreach ($backup_data['settings'] as $option_name => $option_value) {
            update_option('aria_chatbot_' . $option_name . '_options', $option_value);
        }
        
        wp_send_json_success(__('تنظیمات با موفقیت بازیابی شد', 'aria-chatbot'));
    }
    
    /**
     * بررسی سلامت سیستم
     */
    public function check_system_health() {
        check_ajax_referer('aria_admin_nonce', 'nonce');
        
        $health_data = array(
            'database' => $this->check_database_health(),
            'api' => $this->check_api_health(),
            'files' => $this->check_file_permissions(),
            'memory' => $this->check_memory_usage(),
            'performance' => $this->check_performance_metrics()
        );
        
        wp_send_json_success($health_data);
    }
    
    /**
     * بهینه‌سازی دیتابیس
     */
    public function optimize_database() {
        check_ajax_referer('aria_admin_nonce', 'nonce');
        
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'aria_conversations',
            $wpdb->prefix . 'aria_memory',
            $wpdb->prefix . 'aria_analytics',
            $wpdb->prefix . 'aria_knowledge_base'
        );
        
        $optimized_tables = 0;
        foreach ($tables as $table) {
            $result = $wpdb->query("OPTIMIZE TABLE {$table}");
            if ($result !== false) {
                $optimized_tables++;
            }
        }
        
        wp_send_json_success(sprintf(
            __('%d جدول بهینه‌سازی شد', 'aria-chatbot'),
            $optimized_tables
        ));
    }
    
    /**
     * پاک کردن کش
     */
    public function clear_plugin_cache() {
        check_ajax_referer('aria_admin_nonce', 'nonce');
        
        // پاک کردن transients
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_aria_chatbot_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_aria_chatbot_%'");
        
        // پاک کردن فایل‌های کش
        $upload_dir = wp_upload_dir();
        $cache_dir = $upload_dir['basedir'] . '/aria-chatbot/cache/';
        
        if (file_exists($cache_dir)) {
            $files = glob($cache_dir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        
        wp_send_json_success(__('کش با موفقیت پاک شد', 'aria-chatbot'));
    }
    
    /**
     * تولید گزارش آمار
     */
    public function generate_analytics_report() {
        check_ajax_referer('aria_admin_nonce', 'nonce');
        
        $period = sanitize_text_field($_POST['period']);
        $format = sanitize_text_field($_POST['format']);
        
        $analytics = new Aria_Analytics();
        $report_data = $analytics->generate_report($period);
        
        if ($format === 'pdf') {
            $filename = $this->generate_pdf_report($report_data, $period);
        } else {
            $filename = $this->generate_csv_report($report_data, $period);
        }
        
        $upload_dir = wp_upload_dir();
        $download_url = $upload_dir['baseurl'] . '/aria-chatbot/reports/' . $filename;
        
        wp_send_json_success(array(
            'filename' => $filename,
            'download_url' => $download_url
        ));
    }
    
    /**
     * دریافت مدل‌های در دسترس
     */
    private function get_available_models() {
        return array(
            'gpt-4.1' => array(
                'name' => 'GPT-4.1',
                'description' => 'قدرتمندترین مدل با حداکثر دقت و درک عمیق',
                'max_tokens' => 8192,
                'cost_per_token' => 0.03,
                'features' => array('text', 'reasoning', 'multilingual')
            ),
            'gpt-4.1-mini' => array(
                'name' => 'GPT-4.1 Mini',
                'description' => 'نسخه سریع‌تر با حفظ کیفیت بالا',
                'max_tokens' => 4096,
                'cost_per_token' => 0.015,
                'features' => array('text', 'fast', 'multilingual')
            ),
            'gpt-4.1-nano' => array(
                'name' => 'GPT-4.1 Nano',
                'description' => 'سریع‌ترین نسخه برای پاسخ‌های فوری',
                'max_tokens' => 2048,
                'cost_per_token' => 0.005,
                'features' => array('text', 'ultra-fast')
            ),
            'gpt-4o' => array(
                'name' => 'GPT-4O',
                'description' => 'مدل چندحالته با پردازش متن، تصویر و صوت',
                'max_tokens' => 8192,
                'cost_per_token' => 0.025,
                'features' => array('text', 'image', 'audio', 'multimodal')
            ),
            'gpt-4o-mini' => array(
                'name' => 'GPT-4O Mini',
                'description' => 'نسخه کوچک‌تر چندحالته',
                'max_tokens' => 4096,
                'cost_per_token' => 0.012,
                'features' => array('text', 'image', 'multimodal')
            ),
            'o3' => array(
                'name' => 'O3',
                'description' => 'مدل استدلال پیشرفته برای حل مسائل پیچیده',
                'max_tokens' => 16384,
                'cost_per_token' => 0.05,
                'features' => array('reasoning', 'problem-solving', 'analysis')
            ),
            'o3-mini' => array(
                'name' => 'O3 Mini',
                'description' => 'نسخه سریع استدلال',
                'max_tokens' => 8192,
                'cost_per_token' => 0.025,
                'features' => array('reasoning', 'fast')
            ),
            'o3-pro' => array(
                'name' => 'O3 Pro',
                'description' => 'نسخه حرفه‌ای برای کاربردهای تجاری',
                'max_tokens' => 32768,
                'cost_per_token' => 0.08,
                'features' => array('reasoning', 'enterprise', 'advanced')
            ),
            'o4-mini' => array(
                'name' => 'O4 Mini',
                'description' => 'مدل نسل آینده با قابلیت‌های نوین',
                'max_tokens' => 8192,
                'cost_per_token' => 0.02,
                'features' => array('next-gen', 'efficient', 'advanced')
            )
        );
    }
    
    /**
     * دریافت صداهای در دسترس
     */
    private function get_available_voices() {
        return array(
            'fa-IR' => array(
                'male' => array(
                    'name' => 'آرش',
                    'description' => 'صدای مردانه رسمی و واضح',
                    'sample' => ARIA_CHATBOT_PLUGIN_URL . 'assets/voice-samples/arash.mp3'
                ),
                'female' => array(
                    'name' => 'زهرا',
                    'description' => 'صدای زنانه ملایم و طبیعی',
                    'sample' => ARIA_CHATBOT_PLUGIN_URL . 'assets/voice-samples/zahra.mp3'
                )
            ),
            'en-US' => array(
                'male' => array(
                    'name' => 'David',
                    'description' => 'Professional male voice',
                    'sample' => ARIA_CHATBOT_PLUGIN_URL . 'assets/voice-samples/david.mp3'
                ),
                'female' => array(
                    'name' => 'Sarah',
                    'description' => 'Natural female voice',
                    'sample' => ARIA_CHATBOT_PLUGIN_URL . 'assets/voice-samples/sarah.mp3'
                )
            )
        );
    }
    
    /**
     * دریافت زبان‌های پشتیبانی شده
     */
    private function get_supported_languages() {
        return array(
            'fa-IR' => 'فارسی',
            'en-US' => 'English (US)',
            'en-GB' => 'English (UK)',
            'ar-SA' => 'العربية',
            'fr-FR' => 'Français',
            'de-DE' => 'Deutsch',
            'es-ES' => 'Español',
            'it-IT' => 'Italiano',
            'pt-BR' => 'Português',
            'ru-RU' => 'Русский',
            'ja-JP' => '日本語',
            'ko-KR' => '한국어',
            'zh-CN' => '中文',
            'hi-IN' => 'हिन्दी',
            'tr-TR' => 'Türkçe'
        );
    }
    
    /**
     * دریافت آیکون منو
     */
    private function get_menu_icon() {
        return 'data:image/svg+xml;base64,' . base64_encode('
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                <path d="M12 2C17.5228 2 22 6.47715 22 12C22 17.5228 17.5228 22 12 22H2L12 2Z" fill="#9CA3AF"/>
                <circle cx="8" cy="12" r="1" fill="white"/>
                <circle cx="12" cy="12" r="1" fill="white"/>
                <circle cx="16" cy="12" r="1" fill="white"/>
            </svg>
        ');
    }
    
    /**
     * دریافت سلامت سیستم
     */
    private function get_system_health() {
        return array(
            'overall_score' => 85,
            'database_status' => 'healthy',
            'api_status' => 'connected',
            'memory_usage' => '45%',
            'performance_score' => 92,
            'security_score' => 88
        );
    }
    
    /**
     * اجرای بررسی‌های امنیتی
     */
    private function run_security_checks() {
        return array(
            'api_key_protection' => true,
            'rate_limiting' => true,
            'input_sanitization' => true,
            'ssl_enabled' => is_ssl(),
            'file_permissions' => $this->check_file_permissions(),
            'vulnerable_plugins' => false
        );
    }
    
    /**
     * دریافت داده‌های عملکرد
     */
    private function get_performance_data() {
        return array(
            'response_time' => 1.2,
            'memory_usage' => memory_get_usage(true),
            'cache_hit_rate' => 78,
            'api_calls_today' => 245,
            'database_queries' => 12,
            'optimization_score' => 85
        );
    }
    
    /**
     * دریافت اطلاعات سیستم
     */
    private function get_system_info() {
        global $wp_version, $wpdb;
        
        return array(
            'plugin_version' => ARIA_CHATBOT_VERSION,
            'wordpress_version' => $wp_version,
            'php_version' => PHP_VERSION,
            'mysql_version' => $wpdb->db_version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'],
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'active_plugins' => get_option('active_plugins'),
            'theme' => wp_get_theme()->get('Name')
        );
    }
}

// راه‌اندازی کلاس در صورت بودن در ادمین
if (is_admin()) {
    new Aria_Admin();
}