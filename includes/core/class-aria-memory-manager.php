<?php
/**
 * کلاس مدیریت حافظه و یادگیری تطبیقی پشتیبان هوشمند آریا
 * 
 * @package Aria_Chatbot
 * @author علی مطلقیان
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Aria_Memory_Manager {
    
    /**
     * شناسه کاربر فعلی
     */
    private $current_user_id;
    
    /**
     * شناسه جلسه فعلی
     */
    private $current_session_id;
    
    /**
     * حافظه جلسه فعلی
     */
    private $session_memory = array();
    
    /**
     * حافظه دراز مدت کاربر
     */
    private $user_memory = array();
    
    /**
     * تاریخچه مکالمات
     */
    private $conversation_history = array();
    
    /**
     * الگوهای شناسایی شده
     */
    private $identified_patterns = array();
    
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
        // تنظیم هوک‌ها
        $this->setup_hooks();
        
        // راه‌اندازی حافظه
        $this->init_memory_system();
    }
    
    /**
     * تنظیم هوک‌ها
     */
    private function setup_hooks() {
        // هوک‌های ذخیره مکالمات
        add_action('aria_conversation_saved', array($this, 'analyze_and_store_memory'));
        add_action('aria_user_message_received', array($this, 'extract_user_information'));
        add_action('aria_response_generated', array($this, 'update_conversation_context'));
        
        // هوک‌های جلسه
        add_action('aria_session_started', array($this, 'load_user_memory'));
        add_action('aria_session_ended', array($this, 'save_session_summary'));
        
        // فیلترها
        add_filter('aria_system_prompt', array($this, 'add_memory_context'), 15);
        add_filter('aria_conversation_context', array($this, 'enhance_context_with_memory'), 10);
        
        // پاک‌سازی دوره‌ای
        add_action('aria_weekly_cleanup', array($this, 'cleanup_old_memories'));
        
        // AJAX handlers
        add_action('wp_ajax_aria_get_user_memory', array($this, 'get_user_memory_ajax'));
        add_action('wp_ajax_aria_clear_user_memory', array($this, 'clear_user_memory_ajax'));
        add_action('wp_ajax_aria_export_memory', array($this, 'export_user_memory_ajax'));
    }
    
    /**
     * راه‌اندازی سیستم حافظه
     */
    private function init_memory_system() {
        // شناسایی کاربر و جلسه
        $this->identify_current_session();
        
        // بارگذاری حافظه کاربر
        $this->load_user_memory();
        
        // راه‌اندازی حافظه جلسه
        $this->init_session_memory();
    }
    
    /**
     * شناسایی جلسه فعلی
     */
    private function identify_current_session() {
        // شناسایی کاربر
        if (is_user_logged_in()) {
            $this->current_user_id = get_current_user_id();
        } else {
            // برای کاربران مهمان، از IP و user agent استفاده می‌کنیم
            $this->current_user_id = 'guest_' . md5($this->get_user_ip() . $_SERVER['HTTP_USER_AGENT']);
        }
        
        // شناسایی جلسه
        if (isset($_POST['session_id']) && !empty($_POST['session_id'])) {
            $this->current_session_id = sanitize_text_field($_POST['session_id']);
        } else {
            $this->current_session_id = $this->generate_session_id();
        }
    }
    
    /**
     * تولید شناسه جلسه
     */
    private function generate_session_id() {
        return 'session_' . $this->current_user_id . '_' . time() . '_' . wp_rand(1000, 9999);
    }
    
    /**
     * بارگذاری حافظه کاربر
     */
    public function load_user_memory($user_id = null) {
        $user_id = $user_id ?: $this->current_user_id;
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'aria_memory';
        
        // بارگذاری حافظه دراز مدت
        $long_term_memories = $wpdb->get_results($wpdb->prepare(
            "SELECT memory_key, memory_value, memory_type, confidence_score, last_accessed 
             FROM {$table_name} 
             WHERE user_id = %s 
             AND memory_type IN ('profile', 'preferences', 'behavior_pattern', 'interests')
             AND is_active = 1
             ORDER BY confidence_score DESC, last_accessed DESC",
            $user_id
        ));
        
        foreach ($long_term_memories as $memory) {
            $this->user_memory[$memory->memory_type][$memory->memory_key] = array(
                'value' => maybe_unserialize($memory->memory_value),
                'confidence' => floatval($memory->confidence_score),
                'last_accessed' => $memory->last_accessed
            );
        }
        
        // بارگذاری الگوهای رفتاری
        $this->load_behavior_patterns($user_id);
        
        // به‌روزرسانی زمان دسترسی
        $this->update_memory_access_time($user_id);
    }
    
    /**
     * بارگذاری الگوهای رفتاری
     */
    private function load_behavior_patterns($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aria_behavior_patterns';
        
        $patterns = $wpdb->get_results($wpdb->prepare(
            "SELECT pattern_type, pattern_data, frequency, last_occurrence 
             FROM {$table_name} 
             WHERE user_id = %s 
             AND is_active = 1
             ORDER BY frequency DESC",
            $user_id
        ));
        
        foreach ($patterns as $pattern) {
            $this->identified_patterns[$pattern->pattern_type] = array(
                'data' => maybe_unserialize($pattern->pattern_data),
                'frequency' => intval($pattern->frequency),
                'last_occurrence' => $pattern->last_occurrence
            );
        }
    }
    
    /**
     * راه‌اندازی حافظه جلسه
     */
    private function init_session_memory() {
        // بارگذاری اطلاعات جلسه از cache
        $cache_key = 'aria_session_' . $this->current_session_id;
        $cached_session = wp_cache_get($cache_key);
        
        if ($cached_session) {
            $this->session_memory = $cached_session;
        } else {
            $this->session_memory = array(
                'start_time' => current_time('mysql'),
                'messages_count' => 0,
                'topics_discussed' => array(),
                'emotional_state' => 'neutral',
                'language_preference' => 'fa-IR',
                'interaction_style' => 'polite',
                'current_context' => '',
                'user_satisfaction' => null
            );
        }
    }
    
    /**
     * استخراج اطلاعات کاربر از پیام
     */
    public function extract_user_information($message_data) {
        $message = $message_data['message'];
        $user_id = $message_data['user_id'] ?? $this->current_user_id;
        
        // تشخیص اطلاعات شخصی
        $personal_info = $this->extract_personal_information($message);
        if (!empty($personal_info)) {
            $this->store_memory($user_id, 'profile', $personal_info);
        }
        
        // تشخیص ترجیحات
        $preferences = $this->extract_preferences($message);
        if (!empty($preferences)) {
            $this->store_memory($user_id, 'preferences', $preferences);
        }
        
        // تشخیص علایق
        $interests = $this->extract_interests($message);
        if (!empty($interests)) {
            $this->store_memory($user_id, 'interests', $interests);
        }
        
        // تحلیل حالت عاطفی
        $emotional_state = $this->analyze_emotional_state($message);
        $this->session_memory['emotional_state'] = $emotional_state;
        
        // تحلیل سبک تعامل
        $interaction_style = $this->analyze_interaction_style($message);
        $this->session_memory['interaction_style'] = $interaction_style;
        
        // تشخیص زبان
        $detected_language = $this->detect_language($message);
        if ($detected_language) {
            $this->session_memory['language_preference'] = $detected_language;
            $this->store_memory($user_id, 'preferences', array('language' => $detected_language));
        }
    }
    
    /**
     * استخراج اطلاعات شخصی
     */
    private function extract_personal_information($message) {
        $personal_info = array();
        
        // الگوهای تشخیص نام
        if (preg_match('/نام من (.+?) است|من (.+?) هستم|اسم من (.+?)$/u', $message, $matches)) {
            $name = trim($matches[1] ?: $matches[2] ?: $matches[3]);
            if (!empty($name) && mb_strlen($name) < 50) {
                $personal_info['name'] = $name;
            }
        }
        
        // الگوهای تشخیص سن
        if (preg_match('/(\d+) سال دارم|سن من (\d+)|(\d+) ساله هستم/u', $message, $matches)) {
            $age = intval($matches[1] ?: $matches[2] ?: $matches[3]);
            if ($age > 0 && $age < 120) {
                $personal_info['age'] = $age;
            }
        }
        
        // الگوهای تشخیص شهر/کشور
        if (preg_match('/من در (.+?) زندگی می‌کنم|من از (.+?) هستم|شهر من (.+?) است/u', $message, $matches)) {
            $location = trim($matches[1] ?: $matches[2] ?: $matches[3]);
            if (!empty($location) && mb_strlen($location) < 100) {
                $personal_info['location'] = $location;
            }
        }
        
        // الگوهای تشخیص شغل
        if (preg_match('/شغل من (.+?) است|من (.+?) کار می‌کنم|من یک (.+?) هستم/u', $message, $matches)) {
            $job = trim($matches[1] ?: $matches[2] ?: $matches[3]);
            if (!empty($job) && mb_strlen($job) < 100) {
                $personal_info['occupation'] = $job;
            }
        }
        
        return $personal_info;
    }
    
    /**
     * استخراج ترجیحات
     */
    private function extract_preferences($message) {
        $preferences = array();
        
        // ترجیحات زمانی
        if (preg_match('/ترجیح می‌دهم|دوست دارم|بهتر است|بیشتر (.+?) را می‌پسندم/u', $message, $matches)) {
            $preference = trim($matches[1] ?? '');
            if (!empty($preference)) {
                $preferences['general'] = $preference;
            }
        }
        
        // ترجیحات ارتباطی
        if (strpos($message, 'رسمی') !== false) {
            $preferences['communication_style'] = 'formal';
        } elseif (strpos($message, 'دوستانه') !== false || strpos($message, 'راحت') !== false) {
            $preferences['communication_style'] = 'casual';
        }
        
        // ترجیحات زمانی
        if (preg_match('/صبح|عصر|شب|ظهر/u', $message)) {
            $time_preference = $this->extract_time_preference($message);
            if ($time_preference) {
                $preferences['preferred_time'] = $time_preference;
            }
        }
        
        return $preferences;
    }
    
    /**
     * استخراج علایق
     */
    private function extract_interests($message) {
        $interests = array();
        
        // کلمات کلیدی علایق
        $interest_keywords = array(
            'ورزش' => array('فوتبال', 'بسکتبال', 'تنیس', 'شنا', 'دویدن', 'ورزش'),
            'فناوری' => array('کامپیوتر', 'موبایل', 'اینترنت', 'نرم‌افزار', 'برنامه‌نویسی', 'تکنولوژی'),
            'هنر' => array('نقاشی', 'موسیقی', 'سینما', 'تئاتر', 'عکاسی', 'طراحی'),
            'مطالعه' => array('کتاب', 'مطالعه', 'تحقیق', 'یادگیری', 'مجله'),
            'سفر' => array('سفر', 'گردشگری', 'کشور', 'شهر', 'جاهای دیدنی'),
            'آشپزی' => array('آشپزی', 'غذا', 'رستوران', 'تهیه', 'پخت'),
            'خرید' => array('خرید', 'فروشگاه', 'لباس', 'کفش', 'لوازم')
        );
        
        foreach ($interest_keywords as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($message, $keyword) !== false) {
                    $interests[$category] = ($interests[$category] ?? 0) + 1;
                }
            }
        }
        
        // فیلتر کردن علایق با امتیاز بالا
        return array_filter($interests, function($score) {
            return $score > 0;
        });
    }
    
    /**
     * تحلیل حالت عاطفی
     */
    private function analyze_emotional_state($message) {
        $positive_words = array('خوب', 'عالی', 'خوشحال', 'راضی', 'ممنون', 'مرسی', 'خیلی خوب');
        $negative_words = array('بد', 'ناراحت', 'عصبی', 'مشکل', 'نامناسب', 'ضعیف');
        $neutral_words = array('خوب', 'معمولی', 'متوسط');
        
        $positive_score = 0;
        $negative_score = 0;
        
        foreach ($positive_words as $word) {
            if (strpos($message, $word) !== false) {
                $positive_score++;
            }
        }
        
        foreach ($negative_words as $word) {
            if (strpos($message, $word) !== false) {
                $negative_score++;
            }
        }
        
        if ($positive_score > $negative_score) {
            return 'positive';
        } elseif ($negative_score > $positive_score) {
            return 'negative';
        } else {
            return 'neutral';
        }
    }
    
    /**
     * تحلیل سبک تعامل
     */
    private function analyze_interaction_style($message) {
        $formal_indicators = array('لطفاً', 'محترم', 'جناب', 'سرکار', 'ممنون می‌شوم');
        $casual_indicators = array('سلام', 'چطوری', 'خوبی', 'ممنون', 'مرسی', 'باشه');
        
        $formal_score = 0;
        $casual_score = 0;
        
        foreach ($formal_indicators as $indicator) {
            if (strpos($message, $indicator) !== false) {
                $formal_score++;
            }
        }
        
        foreach ($casual_indicators as $indicator) {
            if (strpos($message, $indicator) !== false) {
                $casual_score++;
            }
        }
        
        if ($formal_score > $casual_score) {
            return 'formal';
        } elseif ($casual_score > $formal_score) {
            return 'casual';
        } else {
            return 'balanced';
        }
    }
    
    /**
     * تشخیص زبان
     */
    private function detect_language($message) {
        $language_patterns = array(
            'fa-IR' => '/[آابپتثجچحخدذرزژسشصضطظعغفقکگلمنوهی]/u',
            'ar-SA' => '/[ابتثجحخدذرزسشصضطظعغفقكلمنهوي]/u',
            'en-US' => '/^[a-zA-Z\s\.,!?]+$/'
        );
        
        $scores = array();
        
        foreach ($language_patterns as $lang => $pattern) {
            preg_match_all($pattern, $message, $matches);
            $scores[$lang] = count($matches[0]);
        }
        
        if (empty($scores)) {
            return null;
        }
        
        $detected_language = array_keys($scores, max($scores))[0];
        
        // اطمینان از دقت تشخیص
        if (max($scores) < mb_strlen($message) * 0.3) {
            return null;
        }
        
        return $detected_language;
    }
    
    /**
     * ذخیره حافظه
     */
    public function store_memory($user_id, $memory_type, $memory_data, $confidence = 0.8) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aria_memory';
        
        foreach ($memory_data as $key => $value) {
            // بررسی وجود حافظه قبلی
            $existing_memory = $wpdb->get_row($wpdb->prepare(
                "SELECT id, confidence_score FROM {$table_name} 
                 WHERE user_id = %s AND memory_type = %s AND memory_key = %s",
                $user_id, $memory_type, $key
            ));
            
            if ($existing_memory) {
                // به‌روزرسانی حافظه موجود
                $new_confidence = min(1.0, $existing_memory->confidence_score + 0.1);
                
                $wpdb->update(
                    $table_name,
                    array(
                        'memory_value' => maybe_serialize($value),
                        'confidence_score' => $new_confidence,
                        'last_accessed' => current_time('mysql'),
                        'update_count' => new WP_Query('update_count + 1')
                    ),
                    array('id' => $existing_memory->id),
                    array('%s', '%f', '%s', '%d'),
                    array('%d')
                );
            } else {
                // ایجاد حافظه جدید
                $wpdb->insert(
                    $table_name,
                    array(
                        'user_id' => $user_id,
                        'session_id' => $this->current_session_id,
                        'memory_type' => $memory_type,
                        'memory_key' => $key,
                        'memory_value' => maybe_serialize($value),
                        'confidence_score' => $confidence,
                        'created_at' => current_time('mysql'),
                        'last_accessed' => current_time('mysql'),
                        'is_active' => 1,
                        'update_count' => 1
                    ),
                    array('%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%d', '%d')
                );
            }
        }
        
        // به‌روزرسانی حافظه محلی
        foreach ($memory_data as $key => $value) {
            $this->user_memory[$memory_type][$key] = array(
                'value' => $value,
                'confidence' => $confidence,
                'last_accessed' => current_time('mysql')
            );
        }
    }
    
    /**
     * دریافت حافظه
     */
    public function get_memory($user_id, $memory_type, $memory_key = null) {
        if ($memory_key) {
            return $this->user_memory[$memory_type][$memory_key] ?? null;
        } else {
            return $this->user_memory[$memory_type] ?? array();
        }
    }
    
    /**
     * به‌روزرسانی زمینه مکالمه
     */
    public function update_conversation_context($response_data) {
        $this->session_memory['messages_count']++;
        
        // استخراج موضوعات بحث شده
        $topics = $this->extract_topics($response_data['message']);
        foreach ($topics as $topic) {
            $this->session_memory['topics_discussed'][$topic] = 
                ($this->session_memory['topics_discussed'][$topic] ?? 0) + 1;
        }
        
        // به‌روزرسانی زمینه فعلی
        $this->session_memory['current_context'] = $this->determine_current_context();
        
        // ذخیره حافظه جلسه در cache
        $cache_key = 'aria_session_' . $this->current_session_id;
        wp_cache_set($cache_key, $this->session_memory, '', 3600); // 1 hour
    }
    
    /**
     * استخراج موضوعات
     */
    private function extract_topics($message) {
        $topics = array();
        
        $topic_keywords = array(
            'خرید' => array('خرید', 'سفارش', 'محصول', 'قیمت'),
            'پشتیبانی' => array('مشکل', 'خطا', 'کمک', 'راهنمایی'),
            'اطلاعات' => array('چیست', 'کجاست', 'چگونه', 'اطلاعات'),
            'شکایت' => array('شکایت', 'ناراضی', 'مشکل', 'انتقاد'),
            'تشکر' => array('ممنون', 'متشکر', 'سپاس', 'مرسی')
        );
        
        foreach ($topic_keywords as $topic => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($message, $keyword) !== false) {
                    $topics[] = $topic;
                    break;
                }
            }
        }
        
        return array_unique($topics);
    }
    
    /**
     * تعیین زمینه فعلی
     */
    private function determine_current_context() {
        $topics = $this->session_memory['topics_discussed'];
        
        if (empty($topics)) {
            return 'general';
        }
        
        // موضوع پرتکرار را بر می‌گرداند
        return array_keys($topics, max($topics))[0];
    }
    
    /**
     * اضافه کردن زمینه حافظه به system prompt
     */
    public function add_memory_context($system_prompt) {
        $memory_context = $this->build_memory_context();
        
        if (!empty($memory_context)) {
            $system_prompt .= "\n\n=== اطلاعات کاربر ===\n" . $memory_context;
        }
        
        return $system_prompt;
    }
    
    /**
     * ساخت زمینه حافظه
     */
    private function build_memory_context() {
        $context_parts = array();
        
        // اطلاعات پروفایل کاربر
        if (!empty($this->user_memory['profile'])) {
            $profile_info = array();
            foreach ($this->user_memory['profile'] as $key => $data) {
                if ($data['confidence'] > 0.5) {
                    $profile_info[] = $key . ': ' . $data['value'];
                }
            }
            if (!empty($profile_info)) {
                $context_parts[] = "اطلاعات شخصی کاربر: " . implode(', ', $profile_info);
            }
        }
        
        // ترجیحات کاربر
        if (!empty($this->user_memory['preferences'])) {
            $preferences_info = array();
            foreach ($this->user_memory['preferences'] as $key => $data) {
                if ($data['confidence'] > 0.5) {
                    $preferences_info[] = $key . ': ' . $data['value'];
                }
            }
            if (!empty($preferences_info)) {
                $context_parts[] = "ترجیحات کاربر: " . implode(', ', $preferences_info);
            }
        }
        
        // علایق کاربر
        if (!empty($this->user_memory['interests'])) {
            $interests_info = array();
            foreach ($this->user_memory['interests'] as $category => $data) {
                if ($data['confidence'] > 0.5) {
                    $interests_info[] = $category;
                }
            }
            if (!empty($interests_info)) {
                $context_parts[] = "علایق کاربر: " . implode(', ', $interests_info);
            }
        }
        
        // اطلاعات جلسه فعلی
        if (!empty($this->session_memory)) {
            $session_info = array();
            
            if ($this->session_memory['messages_count'] > 0) {
                $session_info[] = "تعداد پیام‌های جلسه: " . $this->session_memory['messages_count'];
            }
            
            if (!empty($this->session_memory['current_context'])) {
                $session_info[] = "زمینه فعلی: " . $this->session_memory['current_context'];
            }
            
            if ($this->session_memory['emotional_state'] !== 'neutral') {
                $session_info[] = "حالت عاطفی: " . $this->session_memory['emotional_state'];
            }
            
            if (!empty($session_info)) {
                $context_parts[] = "اطلاعات جلسه: " . implode(', ', $session_info);
            }
        }
        
        return implode("\n", $context_parts);
    }
    
    /**
     * تحلیل و ذخیره حافظه از مکالمه
     */
    public function analyze_and_store_memory($conversation_data) {
        $user_id = $conversation_data['user_id'] ?? $this->current_user_id;
        $message = $conversation_data['message'];
        $response = $conversation_data['response'];
        
        // تحلیل الگوهای رفتاری
        $this->analyze_behavior_patterns($user_id, $conversation_data);
        
        // تحلیل رضایت کاربر
        $satisfaction = $this->analyze_user_satisfaction($message, $response);
        if ($satisfaction !== null) {
            $this->session_memory['user_satisfaction'] = $satisfaction;
            $this->store_memory($user_id, 'feedback', array('satisfaction' => $satisfaction));
        }
        
        // ذخیره تاریخچه مکالمه
        $this->store_conversation_context($user_id, $conversation_data);
    }
    
    /**
     * تحلیل الگوهای رفتاری
     */
    private function analyze_behavior_patterns($user_id, $conversation_data) {
        $current_time = current_time('mysql');
        $hour = date('H', strtotime($current_time));
        $day_of_week = date('w', strtotime($current_time));
        
        // الگوی زمانی استفاده
        $time_pattern = array(
            'hour' => $hour,
            'day_of_week' => $day_of_week,
            'usage_frequency' => 1
        );
        
        $this->update_behavior_pattern($user_id, 'time_usage', $time_pattern);
        
        // الگوی طول پیام
        $message_length = mb_strlen($conversation_data['message']);
        $length_pattern = array(
            'average_length' => $message_length,
            'message_count' => 1
        );
        
        $this->update_behavior_pattern($user_id, 'message_length', $length_pattern);
        
        // الگوی نوع درخواست
        $request_type = $this->classify_request_type($conversation_data['message']);
        $request_pattern = array(
            'type' => $request_type,
            'frequency' => 1
        );
        
        $this->update_behavior_pattern($user_id, 'request_type', $request_pattern);
    }
    
    /**
     * به‌روزرسانی الگوی رفتاری
     */
    private function update_behavior_pattern($user_id, $pattern_type, $pattern_data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aria_behavior_patterns';
        
        $existing_pattern = $wpdb->get_row($wpdb->prepare(
            "SELECT id, pattern_data, frequency FROM {$table_name} 
             WHERE user_id = %s AND pattern_type = %s",
            $user_id, $pattern_type
        ));
        
        if ($existing_pattern) {
            $old_data = maybe_unserialize($existing_pattern->pattern_data);
            $new_frequency = $existing_pattern->frequency + 1;
            
            // ترکیب داده‌های قدیم و جدید
            $merged_data = $this->merge_pattern_data($old_data, $pattern_data, $new_frequency);
            
            $wpdb->update(
                $table_name,
                array(
                    'pattern_data' => maybe_serialize($merged_data),
                    'frequency' => $new_frequency,
                    'last_occurrence' => current_time('mysql')
                ),
                array('id' => $existing_pattern->id),
                array('%s', '%d', '%s'),
                array('%d')
            );
        } else {
            $wpdb->insert(
                $table_name,
                array(
                    'user_id' => $user_id,
                    'pattern_type' => $pattern_type,
                    'pattern_data' => maybe_serialize($pattern_data),
                    'frequency' => 1,
                    'first_occurrence' => current_time('mysql'),
                    'last_occurrence' => current_time('mysql'),
                    'is_active' => 1
                ),
                array('%s', '%s', '%s', '%d', '%s', '%s', '%d')
            );
        }
    }
    
    /**
     * ترکیب داده‌های الگو
     */
    private function merge_pattern_data($old_data, $new_data, $frequency) {
        $merged = $old_data;
        
        foreach ($new_data as $key => $value) {
            if (is_numeric($value) && isset($old_data[$key]) && is_numeric($old_data[$key])) {
                // محاسبه میانگین
                $merged[$key] = (($old_data[$key] * ($frequency - 1)) + $value) / $frequency;
            } else {
                $merged[$key] = $value;
            }
        }
        
        return $merged;
    }
    
    /**
     * تحلیل رضایت کاربر
     */
    private function analyze_user_satisfaction($message, $response) {
        $satisfaction_indicators = array(
            'positive' => array('ممنون', 'عالی', 'خوب', 'مرسی', 'راضی', 'مفید'),
            'negative' => array('بد', 'ضعیف', 'نامناسب', 'کاربرد', 'اشتباه', 'نادرست')
        );
        
        $positive_score = 0;
        $negative_score = 0;
        
        foreach ($satisfaction_indicators['positive'] as $indicator) {
            if (strpos($message, $indicator) !== false) {
                $positive_score++;
            }
        }
        
        foreach ($satisfaction_indicators['negative'] as $indicator) {
            if (strpos($message, $indicator) !== false) {
                $negative_score++;
            }
        }
        
        if ($positive_score > 0 && $negative_score === 0) {
            return 'satisfied';
        } elseif ($negative_score > 0 && $positive_score === 0) {
            return 'dissatisfied';
        } else {
            return null; // خنثی یا نامشخص
        }
    }
    
    /**
     * ذخیره زمینه مکالمه
     */
    private function store_conversation_context($user_id, $conversation_data) {
        $context_data = array(
            'message_length' => mb_strlen($conversation_data['message']),
            'response_length' => mb_strlen($conversation_data['response']),
            'topics' => $this->extract_topics($conversation_data['message']),
            'emotional_state' => $this->session_memory['emotional_state'],
            'interaction_style' => $this->session_memory['interaction_style']
        );
        
        $this->store_memory($user_id, 'conversation_context', $context_data, 0.6);
    }
    
    /**
     * طبقه‌بندی نوع درخواست
     */
    private function classify_request_type($message) {
        $request_types = array(
            'question' => array('چیست', 'کجاست', 'چگونه', 'چرا', 'کی', 'سؤال'),
            'request' => array('می‌خواهم', 'لطفاً', 'کمک', 'راهنمایی'),
            'complaint' => array('مشکل', 'خطا', 'شکایت', 'ناراضی'),
            'compliment' => array('ممنون', 'مرسی', 'عالی', 'خوب'),
            'order' => array('سفارش', 'خرید', 'محصول', 'قیمت')
        );
        
        foreach ($request_types as $type => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($message, $keyword) !== false) {
                    return $type;
                }
            }
        }
        
        return 'general';
    }
    
    /**
     * پاک‌سازی حافظه‌های قدیمی
     */
    public function cleanup_old_memories() {
        global $wpdb;
        
        $memory_table = $wpdb->prefix . 'aria_memory';
        $patterns_table = $wpdb->prefix . 'aria_behavior_patterns';
        
        // حذف حافظه‌های کم اعتماد و قدیمی
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$memory_table} 
             WHERE confidence_score < 0.3 
             AND last_accessed < DATE_SUB(NOW(), INTERVAL 30 DAY)"
        ));
        
        // حذف الگوهای کم تکرار
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$patterns_table} 
             WHERE frequency < 3 
             AND last_occurrence < DATE_SUB(NOW(), INTERVAL 60 DAY)"
        ));
        
        // آرشیو حافظه‌های مهم
        $this->archive_important_memories();
    }
    
    /**
     * آرشیو حافظه‌های مهم
     */
    private function archive_important_memories() {
        global $wpdb;
        
        $memory_table = $wpdb->prefix . 'aria_memory';
        $archive_table = $wpdb->prefix . 'aria_memory_archive';
        
        // انتقال حافظه‌های با اعتماد بالا به آرشیو
        $wpdb->query($wpdb->prepare(
            "INSERT INTO {$archive_table} 
             SELECT *, NOW() as archived_at 
             FROM {$memory_table} 
             WHERE confidence_score > 0.8 
             AND last_accessed < DATE_SUB(NOW(), INTERVAL 90 DAY)"
        ));
        
        // حذف از جدول اصلی
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$memory_table} 
             WHERE confidence_score > 0.8 
             AND last_accessed < DATE_SUB(NOW(), INTERVAL 90 DAY)"
        ));
    }
    
    /**
     * دریافت IP کاربر
     */
    private function get_user_ip() {
        $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                return trim($ip);
            }
        }
        
        return 'unknown';
    }
    
    /**
     * به‌روزرسانی زمان دسترسی حافظه
     */
    private function update_memory_access_time($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aria_memory';
        
        $wpdb->query($wpdb->prepare(
            "UPDATE {$table_name} 
             SET last_accessed = NOW() 
             WHERE user_id = %s 
             AND last_accessed < DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            $user_id
        ));
    }
    
    /**
     * دریافت حافظه کاربر (AJAX)
     */
    public function get_user_memory_ajax() {
        check_ajax_referer('aria_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('دسترسی غیرمجاز', 'aria-chatbot'));
        }
        
        $user_id = sanitize_text_field($_POST['user_id'] ?? '');
        
        if (empty($user_id)) {
            wp_send_json_error(__('شناسه کاربر مشخص نشده', 'aria-chatbot'));
        }
        
        $this->load_user_memory($user_id);
        
        wp_send_json_success(array(
            'user_memory' => $this->user_memory,
            'behavior_patterns' => $this->identified_patterns
        ));
    }
    
    /**
     * پاک کردن حافظه کاربر (AJAX)
     */
    public function clear_user_memory_ajax() {
        check_ajax_referer('aria_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('دسترسی غیرمجاز', 'aria-chatbot'));
        }
        
        $user_id = sanitize_text_field($_POST['user_id'] ?? '');
        $memory_type = sanitize_text_field($_POST['memory_type'] ?? 'all');
        
        if (empty($user_id)) {
            wp_send_json_error(__('شناسه کاربر مشخص نشده', 'aria-chatbot'));
        }
        
        global $wpdb;
        $memory_table = $wpdb->prefix . 'aria_memory';
        $patterns_table = $wpdb->prefix . 'aria_behavior_patterns';
        
        if ($memory_type === 'all') {
            // حذف تمام حافظه‌ها
            $wpdb->delete($memory_table, array('user_id' => $user_id), array('%s'));
            $wpdb->delete($patterns_table, array('user_id' => $user_id), array('%s'));
        } else {
            // حذف نوع خاصی از حافظه
            $wpdb->delete($memory_table, array(
                'user_id' => $user_id,
                'memory_type' => $memory_type
            ), array('%s', '%s'));
        }
        
        wp_send_json_success(__('حافظه کاربر پاک شد', 'aria-chatbot'));
    }
    
    /**
     * اکسپورت حافظه کاربر (AJAX)
     */
    public function export_user_memory_ajax() {
        check_ajax_referer('aria_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('دسترسی غیرمجاز', 'aria-chatbot'));
        }
        
        $user_id = sanitize_text_field($_POST['user_id'] ?? '');
        
        if (empty($user_id)) {
            wp_send_json_error(__('شناسه کاربر مشخص نشده', 'aria-chatbot'));
        }
        
        $this->load_user_memory($user_id);
        
        $export_data = array(
            'user_id' => $user_id,
            'export_date' => current_time('mysql'),
            'user_memory' => $this->user_memory,
            'behavior_patterns' => $this->identified_patterns
        );
        
        $filename = 'user_memory_' . $user_id . '_' . date('Y-m-d-H-i-s') . '.json';
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/aria-chatbot/exports/' . $filename;
        
        // ایجاد دایرکتوری در صورت عدم وجود
        $export_dir = $upload_dir['basedir'] . '/aria-chatbot/exports/';
        if (!file_exists($export_dir)) {
            wp_mkdir_p($export_dir);
        }
        
        file_put_contents($file_path, json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        $download_url = $upload_dir['baseurl'] . '/aria-chatbot/exports/' . $filename;
        
        wp_send_json_success(array(
            'filename' => $filename,
            'download_url' => $download_url
        ));
    }
    
    /**
     * دریافت آمار حافظه
     */
    public function get_memory_statistics() {
        global $wpdb;
        
        $memory_table = $wpdb->prefix . 'aria_memory';
        $patterns_table = $wpdb->prefix . 'aria_behavior_patterns';
        
        $stats = array(
            'total_users_with_memory' => $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$memory_table}"),
            'total_memories' => $wpdb->get_var("SELECT COUNT(*) FROM {$memory_table}"),
            'total_behavior_patterns' => $wpdb->get_var("SELECT COUNT(*) FROM {$patterns_table}"),
            'memory_by_type' => $wpdb->get_results("
                SELECT memory_type, COUNT(*) as count 
                FROM {$memory_table} 
                GROUP BY memory_type 
                ORDER BY count DESC
            "),
            'average_confidence' => $wpdb->get_var("SELECT AVG(confidence_score) FROM {$memory_table}"),
            'most_active_users' => $wpdb->get_results("
                SELECT user_id, COUNT(*) as memory_count 
                FROM {$memory_table} 
                GROUP BY user_id 
                ORDER BY memory_count DESC 
                LIMIT 10
            ")
        );
        
        return $stats;
    }
}

// راه‌اندازی کلاس
new Aria_Memory_Manager();