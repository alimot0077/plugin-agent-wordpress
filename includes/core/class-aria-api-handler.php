<?php
/**
 * کلاس مدیریت API OpenAI برای پشتیبان هوشمند آریا
 * 
 * @package Aria_Chatbot
 * @author علی مطلقیان
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Aria_API_Handler {
    
    /**
     * تنظیمات API
     */
    private $settings;
    
    /**
     * کلید API فعال
     */
    private $active_api_key;
    
    /**
     * مدل فعال
     */
    private $active_model;
    
    /**
     * آمار استفاده
     */
    private $usage_stats;
    
    /**
     * کش درخواست‌ها
     */
    private $request_cache = array();
    
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
        
        // راه‌اندازی آمار
        $this->init_usage_stats();
    }
    
    /**
     * بارگذاری تنظیمات
     */
    private function load_settings() {
        $this->settings = get_option('aria_chatbot_options', array());
        $this->active_api_key = $this->get_working_api_key();
        $this->active_model = $this->settings['openai_model'] ?? 'gpt-4.1';
    }
    
    /**
     * تنظیم هوک‌ها
     */
    private function setup_hooks() {
        add_action('wp_ajax_aria_send_message', array($this, 'handle_chat_request'));
        add_action('wp_ajax_nopriv_aria_send_message', array($this, 'handle_chat_request'));
        add_action('wp_ajax_aria_test_message', array($this, 'handle_test_message'));
        add_action('aria_daily_cleanup', array($this, 'cleanup_old_requests'));
        add_filter('aria_api_request_headers', array($this, 'add_custom_headers'));
    }
    
    /**
     * راه‌اندازی آمار استفاده
     */
    private function init_usage_stats() {
        $this->usage_stats = get_transient('aria_api_usage_today') ?: array(
            'requests' => 0,
            'tokens_used' => 0,
            'estimated_cost' => 0,
            'last_reset' => date('Y-m-d')
        );
        
        // ریست آمار در صورت تغییر روز
        if ($this->usage_stats['last_reset'] !== date('Y-m-d')) {
            $this->reset_daily_stats();
        }
    }
    
    /**
     * دریافت کلید API قابل استفاده
     */
    private function get_working_api_key() {
        $main_key = $this->settings['openai_api_key'] ?? '';
        $backup_keys = $this->settings['backup_api_keys'] ?? array();
        
        // تست کلید اصلی
        if (!empty($main_key) && $this->test_api_key($main_key)) {
            return $main_key;
        }
        
        // تست کلیدهای پشتیبان
        foreach ($backup_keys as $backup_key) {
            if (!empty($backup_key) && $this->test_api_key($backup_key)) {
                // لاگ تغییر کلید
                aria_log("Switched to backup API key", 'warning');
                return $backup_key;
            }
        }
        
        return $main_key; // برگرداندن کلید اصلی حتی اگر کار نکند
    }
    
    /**
     * تست کلید API
     */
    private function test_api_key($api_key) {
        if (empty($api_key)) {
            return false;
        }
        
        $cache_key = 'aria_api_test_' . md5($api_key);
        $cached_result = get_transient($cache_key);
        
        if ($cached_result !== false) {
            return $cached_result === 'valid';
        }
        
        $response = wp_remote_get('https://api.openai.com/v1/models', array(
            'timeout' => 10,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'OpenAI-Organization' => $this->settings['api_organization'] ?? ''
            )
        ));
        
        $is_valid = !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
        
        // کش کردن نتیجه برای 1 ساعت
        set_transient($cache_key, $is_valid ? 'valid' : 'invalid', HOUR_IN_SECONDS);
        
        return $is_valid;
    }
    
    /**
     * ارسال پیام به OpenAI
     */
    public function send_message($message, $context = array()) {
        // اعتبارسنجی ورودی
        if (empty($message) || empty($this->active_api_key)) {
            return array(
                'success' => false,
                'error' => __('پیام یا کلید API موجود نیست', 'aria-chatbot')
            );
        }
        
        // بررسی محدودیت نرخ
        if (!$this->check_rate_limit()) {
            return array(
                'success' => false,
                'error' => __('محدودیت نرخ درخواست', 'aria-chatbot')
            );
        }
        
        // آماده‌سازی درخواست
        $request_data = $this->prepare_request($message, $context);
        
        // بررسی کش
        if ($this->settings['enable_caching'] ?? true) {
            $cached_response = $this->get_cached_response($request_data);
            if ($cached_response) {
                return $cached_response;
            }
        }
        
        // ارسال درخواست
        $start_time = microtime(true);
        $response = $this->make_api_request($request_data);
        $response_time = round((microtime(true) - $start_time) * 1000);
        
        if ($response['success']) {
            // پردازش پاسخ موفق
            $processed_response = $this->process_successful_response($response, $context, $response_time);
            
            // کش کردن پاسخ
            if ($this->settings['enable_caching'] ?? true) {
                $this->cache_response($request_data, $processed_response);
            }
            
            // به‌روزرسانی آمار
            $this->update_usage_stats($response['data']['usage'] ?? array());
            
            return $processed_response;
        } else {
            // پردازش خطا
            return $this->process_error_response($response, $request_data);
        }
    }
    
    /**
     * آماده‌سازی درخواست
     */
    private function prepare_request($message, $context) {
        // تعیین مدل مناسب
        $model = $this->determine_appropriate_model($message, $context);
        
        // ساخت تاریخچه مکالمه
        $messages = $this->build_conversation_history($message, $context);
        
        // پارامترهای درخواست
        $request_data = array(
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $this->settings['max_tokens'] ?? 1000,
            'temperature' => floatval($this->settings['temperature'] ?? 0.7),
            'top_p' => floatval($this->settings['top_p'] ?? 1),
            'frequency_penalty' => floatval($this->settings['frequency_penalty'] ?? 0),
            'presence_penalty' => floatval($this->settings['presence_penalty'] ?? 0),
            'user' => $this->get_user_identifier($context)
        );
        
        // اضافه کردن function calling در صورت نیاز
        if ($this->requires_function_calling($message)) {
            $request_data['functions'] = $this->get_available_functions();
            $request_data['function_call'] = 'auto';
        }
        
        return $request_data;
    }
    
    /**
     * تعیین مدل مناسب
     */
    private function determine_appropriate_model($message, $context) {
        // بررسی نیاز به تحلیل تصویر
        if (!empty($context['has_image'])) {
            return $this->settings['image_analysis_model'] ?? 'gpt-4o';
        }
        
        // بررسی نیاز به استدلال پیچیده
        if ($this->requires_complex_reasoning($message)) {
            return $this->settings['reasoning_model'] ?? 'o3';
        }
        
        // بررسی نیاز به پاسخ سریع
        if (!empty($context['quick_response']) || strlen($message) < 50) {
            return $this->settings['quick_response_model'] ?? 'gpt-4.1-nano';
        }
        
        // استفاده از مدل اصلی
        return $this->active_model;
    }
    
    /**
     * ساخت تاریخچه مکالمه
     */
    private function build_conversation_history($message, $context) {
        $messages = array();
        
        // پیام سیستم
        $system_prompt = $this->build_system_prompt($context);
        if (!empty($system_prompt)) {
            $messages[] = array(
                'role' => 'system',
                'content' => $system_prompt
            );
        }
        
        // تاریخچه مکالمات قبلی
        $conversation_history = $this->get_conversation_history($context);
        $context_window = intval($this->settings['context_window'] ?? 10);
        
        // محدود کردن تاریخچه
        $limited_history = array_slice($conversation_history, -$context_window);
        $messages = array_merge($messages, $limited_history);
        
        // پیام فعلی کاربر
        $user_message = array(
            'role' => 'user',
            'content' => $message
        );
        
        // اضافه کردن تصویر در صورت وجود
        if (!empty($context['image_data'])) {
            $user_message['content'] = array(
                array(
                    'type' => 'text',
                    'text' => $message
                ),
                array(
                    'type' => 'image_url',
                    'image_url' => array(
                        'url' => $context['image_data']
                    )
                )
            );
        }
        
        $messages[] = $user_message;
        
        return $messages;
    }
    
    /**
     * ساخت پرامپت سیستم
     */
    private function build_system_prompt($context) {
        $prompt_parts = array();
        
        // پرامپت پایه
        $base_prompt = $this->settings['system_prompt'] ?? '';
        if (!empty($base_prompt)) {
            $prompt_parts[] = $base_prompt;
        }
        
        // پرامپت شخصیت
        $personality_engine = new Aria_Personality_Engine();
        $personality_prompt = $personality_engine->get_personality_prompt();
        if (!empty($personality_prompt)) {
            $prompt_parts[] = $personality_prompt;
        }
        
        // پرامپت تخصصی بر اساس نوع درخواست
        $specialized_prompt = $this->get_specialized_prompt($context);
        if (!empty($specialized_prompt)) {
            $prompt_parts[] = $specialized_prompt;
        }
        
        // اطلاعات سایت
        $site_context = $this->get_site_context();
        if (!empty($site_context)) {
            $prompt_parts[] = $site_context;
        }
        
        // حافظه کاربر
        if (!empty($context['user_memories'])) {
            $memory_context = $this->build_memory_context($context['user_memories']);
            $prompt_parts[] = $memory_context;
        }
        
        return implode("\n\n", array_filter($prompt_parts));
    }
    
    /**
     * دریافت پرامپت تخصصی
     */
    private function get_specialized_prompt($context) {
        $request_type = $context['request_type'] ?? $this->classify_request_type($context['message'] ?? '');
        
        switch ($request_type) {
            case 'sales':
                return $this->settings['sales_prompt'] ?? '';
            case 'support':
                return $this->settings['support_prompt'] ?? '';
            case 'information':
                return $this->settings['info_prompt'] ?? '';
            default:
                return '';
        }
    }
    
    /**
     * طبقه‌بندی نوع درخواست
     */
    private function classify_request_type($message) {
        $sales_keywords = array('خرید', 'قیمت', 'سفارش', 'پرداخت', 'تخفیف');
        $support_keywords = array('مشکل', 'خطا', 'کمک', 'راهنمایی', 'درست');
        $info_keywords = array('چیست', 'کجاست', 'چگونه', 'چرا', 'اطلاعات');
        
        $message_lower = mb_strtolower($message);
        
        $sales_score = $this->calculate_keyword_score($message_lower, $sales_keywords);
        $support_score = $this->calculate_keyword_score($message_lower, $support_keywords);
        $info_score = $this->calculate_keyword_score($message_lower, $info_keywords);
        
        if ($sales_score > $support_score && $sales_score > $info_score) {
            return 'sales';
        } elseif ($support_score > $info_score) {
            return 'support';
        } else {
            return 'information';
        }
    }
    
    /**
     * محاسبه امتیاز کلمات کلیدی
     */
    private function calculate_keyword_score($text, $keywords) {
        $score = 0;
        foreach ($keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                $score++;
            }
        }
        return $score;
    }
    
    /**
     * ارسال درخواست به API
     */
    private function make_api_request($request_data) {
        $url = 'https://api.openai.com/v1/chat/completions';
        $timeout = intval($this->settings['response_timeout'] ?? 30);
        $retry_attempts = intval($this->settings['retry_attempts'] ?? 2);
        
        $headers = array(
            'Authorization' => 'Bearer ' . $this->active_api_key,
            'Content-Type' => 'application/json',
            'User-Agent' => 'Aria-ChatBot/' . ARIA_CHATBOT_VERSION
        );
        
        // اضافه کردن شناسه سازمان در صورت وجود
        if (!empty($this->settings['api_organization'])) {
            $headers['OpenAI-Organization'] = $this->settings['api_organization'];
        }
        
        // اعمال فیلتر برای هدرهای سفارشی
        $headers = apply_filters('aria_api_request_headers', $headers, $request_data);
        
        $args = array(
            'timeout' => $timeout,
            'headers' => $headers,
            'body' => wp_json_encode($request_data),
            'method' => 'POST'
        );
        
        // تلاش برای ارسال درخواست
        for ($attempt = 0; $attempt <= $retry_attempts; $attempt++) {
            $response = wp_remote_request($url, $args);
            
            if (!is_wp_error($response)) {
                $status_code = wp_remote_retrieve_response_code($response);
                $body = wp_remote_retrieve_body($response);
                
                if ($status_code === 200) {
                    $data = json_decode($body, true);
                    
                    if ($data && isset($data['choices'])) {
                        return array(
                            'success' => true,
                            'data' => $data,
                            'status_code' => $status_code
                        );
                    }
                }
                
                // در صورت خطای 429 (Rate Limit)، انتظار بیشتر
                if ($status_code === 429 && $attempt < $retry_attempts) {
                    sleep(pow(2, $attempt)); // Exponential backoff
                    continue;
                }
                
                return array(
                    'success' => false,
                    'error' => $this->parse_api_error($body, $status_code),
                    'status_code' => $status_code
                );
            } else {
                // در صورت خطای شبکه، تلاش مجدد
                if ($attempt < $retry_attempts) {
                    sleep(1);
                    continue;
                }
                
                return array(
                    'success' => false,
                    'error' => $response->get_error_message(),
                    'status_code' => 0
                );
            }
        }
        
        return array(
            'success' => false,
            'error' => __('تمام تلاش‌ها ناموفق بود', 'aria-chatbot'),
            'status_code' => 0
        );
    }
    
    /**
     * پردازش پاسخ موفق
     */
    private function process_successful_response($response, $context, $response_time) {
        $data = $response['data'];
        $message_content = $data['choices'][0]['message']['content'] ?? '';
        
        // اعمال فیلترهای پردازش
        $processed_message = $this->apply_response_filters($message_content, $context);
        
        // ذخیره مکالمه
        $this->save_conversation($context['message'] ?? '', $processed_message, $context);
        
        // آماده‌سازی پاسخ نهایی
        $final_response = array(
            'success' => true,
            'message' => $processed_message,
            'model_used' => $data['model'] ?? $this->active_model,
            'usage' => $data['usage'] ?? array(),
            'response_time' => $response_time,
            'conversation_id' => $context['conversation_id'] ?? null
        );
        
        // بررسی function calling
        if (isset($data['choices'][0]['message']['function_call'])) {
            $final_response['function_call'] = $data['choices'][0]['message']['function_call'];
            $final_response = $this->handle_function_call($final_response, $context);
        }
        
        return $final_response;
    }
    
    /**
     * اعمال فیلترهای پردازش پاسخ
     */
    private function apply_response_filters($message, $context) {
        // فیلتر شخصیت
        $personality_engine = new Aria_Personality_Engine();
        $message = $personality_engine->apply_personality_style($message);
        
        // فیلتر امنیتی
        $message = $this->apply_security_filters($message);
        
        // فیلتر زبان
        $message = $this->apply_language_filters($message, $context);
        
        // فیلترهای سفارشی
        $message = apply_filters('aria_response_message', $message, $context);
        
        return $message;
    }
    
    /**
     * اعمال فیلترهای امنیتی
     */
    private function apply_security_filters($message) {
        // حذف اطلاعات حساس احتمالی
        $sensitive_patterns = array(
            '/sk-[a-zA-Z0-9]{48}/', // API keys
            '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', // Emails
            '/\b\d{16}\b/', // Credit card numbers
        );
        
        foreach ($sensitive_patterns as $pattern) {
            $message = preg_replace($pattern, '[محرمانه]', $message);
        }
        
        return $message;
    }
    
    /**
     * مدیریت function calling
     */
    private function handle_function_call($response, $context) {
        $function_call = $response['function_call'];
        $function_name = $function_call['name'];
        $function_arguments = json_decode($function_call['arguments'], true);
        
        // اجرای تابع مربوطه
        $function_result = $this->execute_function($function_name, $function_arguments, $context);
        
        if ($function_result) {
            // ارسال مجدد درخواست با نتیجه تابع
            $followup_request = $this->build_function_followup_request($response, $function_result);
            return $this->make_api_request($followup_request);
        }
        
        return $response;
    }
    
    /**
     * اجرای توابع در دسترس
     */
    private function execute_function($function_name, $arguments, $context) {
        switch ($function_name) {
            case 'search_products':
                if (class_exists('Aria_WooCommerce_Integration')) {
                    $woo_integration = new Aria_WooCommerce_Integration();
                    return $woo_integration->search_products($arguments['query']);
                }
                break;
                
            case 'get_order_status':
                if (class_exists('Aria_WooCommerce_Integration')) {
                    $woo_integration = new Aria_WooCommerce_Integration();
                    return $woo_integration->get_order_status($arguments['order_id']);
                }
                break;
                
            case 'search_knowledge_base':
                return $this->search_knowledge_base($arguments['query']);
                
            case 'get_current_time':
                return array('current_time' => current_time('mysql'));
                
            default:
                return null;
        }
    }
    
    /**
     * جستجو در پایگاه دانش
     */
    private function search_knowledge_base($query) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aria_knowledge_base';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT question, answer FROM {$table_name} 
             WHERE is_active = 1 
             AND (question LIKE %s OR keywords LIKE %s OR answer LIKE %s)
             ORDER BY priority DESC
             LIMIT 5",
            '%' . $wpdb->esc_like($query) . '%',
            '%' . $wpdb->esc_like($query) . '%',
            '%' . $wpdb->esc_like($query) . '%'
        ));
        
        return $results;
    }
    
    /**
     * بررسی محدودیت نرخ
     */
    private function check_rate_limit() {
        $rate_limit = intval($this->settings['rate_limit'] ?? 100);
        
        if ($this->usage_stats['requests'] >= $rate_limit) {
            return false;
        }
        
        return true;
    }
    
    /**
     * به‌روزرسانی آمار استفاده
     */
    private function update_usage_stats($usage_data) {
        $this->usage_stats['requests']++;
        
        if (isset($usage_data['total_tokens'])) {
            $this->usage_stats['tokens_used'] += $usage_data['total_tokens'];
        }
        
        // محاسبه هزینه تقریبی
        $model_info = $this->get_model_info($this->active_model);
        if ($model_info && isset($usage_data['total_tokens'])) {
            $cost = $usage_data['total_tokens'] * $model_info['cost_per_token'];
            $this->usage_stats['estimated_cost'] += $cost;
        }
        
        // ذخیره آمار
        set_transient('aria_api_usage_today', $this->usage_stats, DAY_IN_SECONDS);
    }
    
    /**
     * ریست آمار روزانه
     */
    private function reset_daily_stats() {
        // آرشیو آمار قبلی
        $yesterday_stats = $this->usage_stats;
        $yesterday_stats['date'] = date('Y-m-d', strtotime('-1 day'));
        
        $historical_stats = get_option('aria_api_usage_history', array());
        $historical_stats[] = $yesterday_stats;
        
        // نگهداری آمار 30 روز گذشته
        $historical_stats = array_slice($historical_stats, -30);
        update_option('aria_api_usage_history', $historical_stats);
        
        // ریست آمار فعلی
        $this->usage_stats = array(
            'requests' => 0,
            'tokens_used' => 0,
            'estimated_cost' => 0,
            'last_reset' => date('Y-m-d')
        );
        
        set_transient('aria_api_usage_today', $this->usage_stats, DAY_IN_SECONDS);
    }
    
    /**
     * دریافت اطلاعات مدل
     */
    private function get_model_info($model_name) {
        $models = array(
            'gpt-4.1' => array('cost_per_token' => 0.03),
            'gpt-4.1-mini' => array('cost_per_token' => 0.015),
            'gpt-4.1-nano' => array('cost_per_token' => 0.005),
            'gpt-4o' => array('cost_per_token' => 0.025),
            'gpt-4o-mini' => array('cost_per_token' => 0.012),
            'o3' => array('cost_per_token' => 0.05),
            'o3-mini' => array('cost_per_token' => 0.025),
            'o3-pro' => array('cost_per_token' => 0.08),
            'o4-mini' => array('cost_per_token' => 0.02)
        );
        
        return $models[$model_name] ?? null;
    }
    
    /**
     * مدیریت درخواست چت AJAX
     */
    public function handle_chat_request() {
        // بررسی nonce
        if (!check_ajax_referer('aria_chatbot_nonce', 'nonce', false)) {
            wp_send_json_error(__('درخواست نامعتبر', 'aria-chatbot'));
        }
        
        // دریافت داده‌ها
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        $context = array(
            'session_id' => $session_id,
            'user_id' => get_current_user_id(),
            'ip_address' => $this->get_user_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'page_url' => esc_url_raw($_POST['page_url'] ?? ''),
            'message' => $message
        );
        
        // بررسی امنیتی
        $security_check = apply_filters('aria_security_check', true, $message, $context);
        if (!$security_check) {
            wp_send_json_error(__('درخواست رد شد', 'aria-chatbot'));
        }
        
        // ارسال پیام
        $response = $this->send_message($message, $context);
        
        if ($response['success']) {
            wp_send_json_success($response);
        } else {
            wp_send_json_error($response['error']);
        }
    }
    
    /**
     * مدیریت پیام تست
     */
    public function handle_test_message() {
        // بررسی مجوز
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('دسترسی غیرمجاز', 'aria-chatbot'));
        }
        
        check_ajax_referer('aria_admin_nonce', 'nonce');
        
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        
        if (empty($message)) {
            wp_send_json_error(__('پیام خالی است', 'aria-chatbot'));
        }
        
        $context = array(
            'session_id' => 'test_' . uniqid(),
            'user_id' => get_current_user_id(),
            'is_test' => true
        );
        
        $response = $this->send_message($message, $context);
        wp_send_json($response);
    }
    
    /**
     * تست اتصال API
     */
    public function test_connection($api_key = null, $model = null) {
        $test_key = $api_key ?: $this->active_api_key;
        $test_model = $model ?: $this->active_model;
        
        if (empty($test_key)) {
            return array(
                'success' => false,
                'message' => __('کلید API موجود نیست', 'aria-chatbot')
            );
        }
        
        // تست ساده با درخواست models
        $response = wp_remote_get('https://api.openai.com/v1/models', array(
            'timeout' => 10,
            'headers' => array(
                'Authorization' => 'Bearer ' . $test_key,
                'OpenAI-Organization' => $this->settings['api_organization'] ?? ''
            )
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 200) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            // بررسی در دسترس بودن مدل
            $available_models = array_column($data['data'] ?? array(), 'id');
            $model_available = in_array($test_model, $available_models);
            
            return array(
                'success' => true,
                'message' => __('اتصال موفق', 'aria-chatbot'),
                'model_info' => array(
                    'model' => $test_model,
                    'available' => $model_available,
                    'total_models' => count($available_models)
                )
            );
        } else {
            $error_body = wp_remote_retrieve_body($response);
            $error_data = json_decode($error_body, true);
            
            return array(
                'success' => false,
                'message' => $error_data['error']['message'] ?? __('خطای ناشناخته', 'aria-chatbot')
            );
        }
    }
    
    /**
     * دریافت IP کاربر
     */
    private function get_user_ip() {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // در صورت وجود چندین IP، اولی را برمی‌گرداند
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                return trim($ip);
            }
        }
        
        return 'unknown';
    }
    
    /**
     * پاک‌سازی درخواست‌های قدیمی
     */
    public function cleanup_old_requests() {
        // پاک کردن کش‌های قدیمی
        global $wpdb;
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE %s 
             AND option_name LIKE %s",
            '_transient_aria_api_cache_%',
            '%' . date('Y-m-d', strtotime('-7 days')) . '%'
        ));
    }
    
    /**
     * دریافت آمار استفاده
     */
    public function get_usage_stats() {
        return $this->usage_stats;
    }
    
    /**
     * دریافت آمار تاریخی
     */
    public function get_historical_stats($days = 30) {
        $historical_stats = get_option('aria_api_usage_history', array());
        return array_slice($historical_stats, -$days);
    }
}

// راه‌اندازی کلاس
new Aria_API_Handler();