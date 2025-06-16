<?php
/**
 * کلاس مدیریت قابلیت‌های صوتی پشتیبان هوشمند آریا
 * 
 * @package Aria_Chatbot
 * @author علی مطلقیان
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Aria_Voice_Handler {
    
    /**
     * تنظیمات صوتی
     */
    private $voice_settings;
    
    /**
     * زبان‌های پشتیبانی شده
     */
    private $supported_languages;
    
    /**
     * صداهای در دسترس
     */
    private $available_voices;
    
    /**
     * مسیر ذخیره فایل‌های صوتی
     */
    private $audio_upload_path;
    
    /**
     * URL فایل‌های صوتی
     */
    private $audio_upload_url;
    
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
        
        // تنظیم مسیرها
        $this->setup_paths();
        
        // تنظیم هوک‌ها
        $this->setup_hooks();
        
        // راه‌اندازی داده‌های صوتی
        $this->init_voice_data();
    }
    
    /**
     * بارگذاری تنظیمات
     */
    private function load_settings() {
        $this->voice_settings = get_option('aria_chatbot_voice_options', array());
    }
    
    /**
     * تنظیم مسیرها
     */
    private function setup_paths() {
        $upload_dir = wp_upload_dir();
        $this->audio_upload_path = $upload_dir['basedir'] . '/aria-chatbot/voices/';
        $this->audio_upload_url = $upload_dir['baseurl'] . '/aria-chatbot/voices/';
        
        // اطمینان از وجود دایرکتوری
        if (!file_exists($this->audio_upload_path)) {
            wp_mkdir_p($this->audio_upload_path);
            
            // ایجاد فایل .htaccess برای امنیت
            $htaccess_content = "Options -Indexes\n";
            $htaccess_content .= "<Files *.php>\nDeny from all\n</Files>\n";
            $htaccess_content .= "AddType audio/mpeg .mp3\n";
            $htaccess_content .= "AddType audio/wav .wav\n";
            $htaccess_content .= "AddType audio/ogg .ogg\n";
            file_put_contents($this->audio_upload_path . '.htaccess', $htaccess_content);
        }
    }
    
    /**
     * تنظیم هوک‌ها
     */
    private function setup_hooks() {
        // AJAX handlers
        add_action('wp_ajax_aria_process_voice_message', array($this, 'handle_voice_message'));
        add_action('wp_ajax_nopriv_aria_process_voice_message', array($this, 'handle_voice_message'));
        add_action('wp_ajax_aria_text_to_speech', array($this, 'handle_text_to_speech'));
        add_action('wp_ajax_nopriv_aria_text_to_speech', array($this, 'handle_text_to_speech'));
        add_action('wp_ajax_aria_test_voice_settings', array($this, 'test_voice_settings'));
        add_action('wp_ajax_aria_upload_custom_sound', array($this, 'upload_custom_sound'));
        add_action('wp_ajax_aria_delete_audio_file', array($this, 'delete_audio_file'));
        
        // Frontend scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_voice_scripts'));
        
        // پاک‌سازی فایل‌های قدیمی
        add_action('aria_daily_cleanup', array($this, 'cleanup_old_audio_files'));
        
        // فیلترها
        add_filter('aria_chatbot_response', array($this, 'add_voice_response'), 10, 2);
    }
    
    /**
     * راه‌اندازی داده‌های صوتی
     */
    private function init_voice_data() {
        $this->supported_languages = array(
            'fa-IR' => array(
                'name' => 'فارسی',
                'code' => 'fa',
                'rtl' => true,
                'voices' => array('male', 'female')
            ),
            'en-US' => array(
                'name' => 'English (US)',
                'code' => 'en',
                'rtl' => false,
                'voices' => array('male', 'female')
            ),
            'en-GB' => array(
                'name' => 'English (UK)',
                'code' => 'en',
                'rtl' => false,
                'voices' => array('male', 'female')
            ),
            'ar-SA' => array(
                'name' => 'العربية',
                'code' => 'ar',
                'rtl' => true,
                'voices' => array('male', 'female')
            ),
            'fr-FR' => array(
                'name' => 'Français',
                'code' => 'fr',
                'rtl' => false,
                'voices' => array('male', 'female')
            ),
            'de-DE' => array(
                'name' => 'Deutsch',
                'code' => 'de',
                'rtl' => false,
                'voices' => array('male', 'female')
            ),
            'es-ES' => array(
                'name' => 'Español',
                'code' => 'es',
                'rtl' => false,
                'voices' => array('male', 'female')
            ),
            'it-IT' => array(
                'name' => 'Italiano',
                'code' => 'it',
                'rtl' => false,
                'voices' => array('male', 'female')
            ),
            'pt-BR' => array(
                'name' => 'Português',
                'code' => 'pt',
                'rtl' => false,
                'voices' => array('male', 'female')
            ),
            'ru-RU' => array(
                'name' => 'Русский',
                'code' => 'ru',
                'rtl' => false,
                'voices' => array('male', 'female')
            ),
            'ja-JP' => array(
                'name' => '日本語',
                'code' => 'ja',
                'rtl' => false,
                'voices' => array('male', 'female')
            ),
            'ko-KR' => array(
                'name' => '한국어',
                'code' => 'ko',
                'rtl' => false,
                'voices' => array('male', 'female')
            ),
            'zh-CN' => array(
                'name' => '中文',
                'code' => 'zh',
                'rtl' => false,
                'voices' => array('male', 'female')
            ),
            'hi-IN' => array(
                'name' => 'हिन्दी',
                'code' => 'hi',
                'rtl' => false,
                'voices' => array('male', 'female')
            ),
            'tr-TR' => array(
                'name' => 'Türkçe',
                'code' => 'tr',
                'rtl' => false,
                'voices' => array('male', 'female')
            )
        );
        
        $this->available_voices = array(
            'fa-IR' => array(
                'male' => array(
                    'name' => 'آرش',
                    'description' => 'صدای مردانه رسمی و واضح',
                    'voice_id' => 'fa-IR-ArashNeural',
                    'sample_text' => 'سلام، من آرش هستم. چطور می‌تونم کمکتون کنم؟'
                ),
                'female' => array(
                    'name' => 'زهرا',
                    'description' => 'صدای زنانه ملایم و طبیعی',
                    'voice_id' => 'fa-IR-ZahraNeural',
                    'sample_text' => 'سلام، من زهرا هستم. خوشحالم که با شما آشنا شدم.'
                )
            ),
            'en-US' => array(
                'male' => array(
                    'name' => 'David',
                    'description' => 'Professional male voice',
                    'voice_id' => 'en-US-DavidNeural',
                    'sample_text' => 'Hello, I am David. How can I assist you today?'
                ),
                'female' => array(
                    'name' => 'Sarah',
                    'description' => 'Natural female voice',
                    'voice_id' => 'en-US-SarahNeural',
                    'sample_text' => 'Hi there! I am Sarah, your virtual assistant.'
                )
            ),
            'ar-SA' => array(
                'male' => array(
                    'name' => 'محمد',
                    'description' => 'صوت رجولي واضح ومهني',
                    'voice_id' => 'ar-SA-MohammedNeural',
                    'sample_text' => 'السلام عليكم، أنا محمد. كيف يمكنني مساعدتك؟'
                ),
                'female' => array(
                    'name' => 'فاطمة',
                    'description' => 'صوت نسائي لطيف وطبيعي',
                    'voice_id' => 'ar-SA-FatimaNeural',
                    'sample_text' => 'مرحباً، أنا فاطمة. سعيدة بلقائك.'
                )
            )
        );
    }
    
    /**
     * بارگذاری اسکریپت‌های صوتی
     */
    public function enqueue_voice_scripts() {
        if (!$this->should_load_voice_features()) {
            return;
        }
        
        // اسکریپت اصلی صوتی
        wp_enqueue_script(
            'aria-voice-handler',
            ARIA_CHATBOT_PLUGIN_URL . 'public/js/voice-handler.js',
            array('jquery'),
            ARIA_CHATBOT_VERSION,
            true
        );
        
        // اسکریپت پردازش صوت
        wp_enqueue_script(
            'aria-audio-processor',
            ARIA_CHATBOT_PLUGIN_URL . 'public/js/audio-processor.js',
            array('aria-voice-handler'),
            ARIA_CHATBOT_VERSION,
            true
        );
        
        // CSS برای رابط کاربری صوتی
        wp_enqueue_style(
            'aria-voice-ui',
            ARIA_CHATBOT_PLUGIN_URL . 'public/css/voice-ui.css',
            array(),
            ARIA_CHATBOT_VERSION
        );
        
        // تنظیمات JavaScript
        wp_localize_script('aria-voice-handler', 'ariaVoice', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aria_voice_nonce'),
            'settings' => $this->get_frontend_voice_settings(),
            'supported_languages' => $this->supported_languages,
            'available_voices' => $this->available_voices,
            'audio_formats' => array('mp3', 'wav', 'ogg'),
            'max_recording_duration' => 60, // ثانیه
            'sample_rate' => 44100,
            'bit_depth' => 16
        ));
    }
    
    /**
     * بررسی نیاز به بارگذاری ویژگی‌های صوتی
     */
    private function should_load_voice_features() {
        $voice_enabled = $this->voice_settings['voice_input_enabled'] ?? false;
        $tts_enabled = $this->voice_settings['tts_enabled'] ?? false;
        
        return $voice_enabled || $tts_enabled;
    }
    
    /**
     * دریافت تنظیمات صوتی برای frontend
     */
    private function get_frontend_voice_settings() {
        return array(
            'voice_input_enabled' => $this->voice_settings['voice_input_enabled'] ?? false,
            'tts_enabled' => $this->voice_settings['tts_enabled'] ?? false,
            'language' => $this->voice_settings['language'] ?? 'fa-IR',
            'voice_gender' => $this->voice_settings['voice_gender'] ?? 'female',
            'speech_rate' => floatval($this->voice_settings['speech_rate'] ?? 1.0),
            'speech_pitch' => floatval($this->voice_settings['speech_pitch'] ?? 1.0),
            'speech_volume' => floatval($this->voice_settings['speech_volume'] ?? 0.8),
            'auto_play_responses' => $this->voice_settings['auto_play_responses'] ?? false,
            'voice_commands_enabled' => $this->voice_settings['voice_commands_enabled'] ?? true,
            'noise_reduction' => $this->voice_settings['noise_reduction'] ?? true,
            'echo_cancellation' => $this->voice_settings['echo_cancellation'] ?? true,
            'voice_activation_threshold' => floatval($this->voice_settings['voice_activation_threshold'] ?? 0.5),
            'message_sent_sound' => $this->voice_settings['message_sent_sound'] ?? '',
            'message_received_sound' => $this->voice_settings['message_received_sound'] ?? '',
            'notification_sound' => $this->voice_settings['notification_sound'] ?? ''
        );
    }
    
    /**
     * مدیریت پیام صوتی
     */
    public function handle_voice_message() {
        // بررسی امنیت
        if (!check_ajax_referer('aria_voice_nonce', 'nonce', false)) {
            wp_send_json_error(__('درخواست نامعتبر', 'aria-chatbot'));
        }
        
        // بررسی فعال بودن ویژگی
        if (!($this->voice_settings['voice_input_enabled'] ?? false)) {
            wp_send_json_error(__('ورودی صوتی غیرفعال است', 'aria-chatbot'));
        }
        
        // بررسی وجود فایل صوتی
        if (!isset($_FILES['audio_data'])) {
            wp_send_json_error(__('فایل صوتی ارسال نشده', 'aria-chatbot'));
        }
        
        $audio_file = $_FILES['audio_data'];
        
        // اعتبارسنجی فایل
        $validation_result = $this->validate_audio_file($audio_file);
        if (!$validation_result['valid']) {
            wp_send_json_error($validation_result['message']);
        }
        
        // پردازش فایل صوتی
        $processing_result = $this->process_audio_file($audio_file);
        if (!$processing_result['success']) {
            wp_send_json_error($processing_result['message']);
        }
        
        // تبدیل گفتار به متن
        $transcription_result = $this->speech_to_text($processing_result['file_path']);
        if (!$transcription_result['success']) {
            wp_send_json_error($transcription_result['message']);
        }
        
        // ارسال متن تشخیص داده شده به چت بات
        $context = array(
            'session_id' => sanitize_text_field($_POST['session_id'] ?? ''),
            'input_type' => 'voice',
            'audio_file' => $processing_result['file_url'],
            'confidence' => $transcription_result['confidence'],
            'language_detected' => $transcription_result['language']
        );
        
        $api_handler = new Aria_API_Handler();
        $chat_response = $api_handler->send_message($transcription_result['text'], $context);
        
        if ($chat_response['success']) {
            // تولید پاسخ صوتی
            $audio_response = null;
            if ($this->voice_settings['tts_enabled'] ?? false) {
                $tts_result = $this->text_to_speech($chat_response['message'], $context['language_detected']);
                if ($tts_result['success']) {
                    $audio_response = $tts_result['audio_url'];
                }
            }
            
            wp_send_json_success(array(
                'transcribed_text' => $transcription_result['text'],
                'response_text' => $chat_response['message'],
                'audio_response' => $audio_response,
                'confidence' => $transcription_result['confidence'],
                'language' => $transcription_result['language'],
                'processing_time' => $transcription_result['processing_time'] + ($chat_response['response_time'] ?? 0)
            ));
        } else {
            wp_send_json_error($chat_response['error']);
        }
        
        // پاک کردن فایل موقت
        if (file_exists($processing_result['file_path'])) {
            unlink($processing_result['file_path']);
        }
    }
    
    /**
     * اعتبارسنجی فایل صوتی
     */
    private function validate_audio_file($file) {
        // بررسی خطای آپلود
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return array(
                'valid' => false,
                'message' => __('خطا در آپلود فایل', 'aria-chatbot')
            );
        }
        
        // بررسی اندازه فایل (حداکثر 10MB)
        $max_file_size = 10 * 1024 * 1024; // 10MB
        if ($file['size'] > $max_file_size) {
            return array(
                'valid' => false,
                'message' => __('اندازه فایل بیش از حد مجاز است', 'aria-chatbot')
            );
        }
        
        // بررسی نوع فایل
        $allowed_types = array(
            'audio/mpeg',
            'audio/mp3',
            'audio/wav',
            'audio/wave',
            'audio/x-wav',
            'audio/ogg',
            'audio/webm'
        );
        
        if (!in_array($file['type'], $allowed_types)) {
            return array(
                'valid' => false,
                'message' => __('نوع فایل پشتیبانی نمی‌شود', 'aria-chatbot')
            );
        }
        
        // بررسی پسوند فایل
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_extensions = array('mp3', 'wav', 'ogg', 'webm');
        
        if (!in_array($file_extension, $allowed_extensions)) {
            return array(
                'valid' => false,
                'message' => __('پسوند فایل مجاز نیست', 'aria-chatbot')
            );
        }
        
        return array('valid' => true);
    }
    
    /**
     * پردازش فایل صوتی
     */
    private function process_audio_file($file) {
        $file_name = 'voice_' . uniqid() . '_' . time() . '.wav';
        $file_path = $this->audio_upload_path . 'temp/' . $file_name;
        $file_url = $this->audio_upload_url . 'temp/' . $file_name;
        
        // ایجاد دایرکتوری temp در صورت عدم وجود
        $temp_dir = $this->audio_upload_path . 'temp/';
        if (!file_exists($temp_dir)) {
            wp_mkdir_p($temp_dir);
        }
        
        // انتقال فایل
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            // پردازش فایل (نرمال‌سازی، کاهش نویز، تبدیل فرمت)
            $processed_path = $this->normalize_audio_file($file_path);
            
            return array(
                'success' => true,
                'file_path' => $processed_path ?: $file_path,
                'file_url' => $file_url,
                'file_size' => filesize($processed_path ?: $file_path)
            );
        } else {
            return array(
                'success' => false,
                'message' => __('خطا در ذخیره فایل', 'aria-chatbot')
            );
        }
    }
    
    /**
     * نرمال‌سازی فایل صوتی
     */
    private function normalize_audio_file($file_path) {
        // اگر FFmpeg در دسترس باشد، فایل را پردازش می‌کنیم
        if ($this->is_ffmpeg_available()) {
            $output_path = str_replace('.wav', '_normalized.wav', $file_path);
            
            $command = sprintf(
                'ffmpeg -i %s -ar 16000 -ac 1 -acodec pcm_s16le -y %s 2>&1',
                escapeshellarg($file_path),
                escapeshellarg($output_path)
            );
            
            exec($command, $output, $return_code);
            
            if ($return_code === 0 && file_exists($output_path)) {
                unlink($file_path); // حذف فایل اصلی
                return $output_path;
            }
        }
        
        return null; // در صورت عدم موفقیت، فایل اصلی را برمی‌گرداند
    }
    
    /**
     * بررسی در دسترس بودن FFmpeg
     */
    private function is_ffmpeg_available() {
        $output = null;
        $return_code = null;
        exec('ffmpeg -version 2>&1', $output, $return_code);
        
        return $return_code === 0;
    }
    
    /**
     * تبدیل گفتار به متن
     */
    private function speech_to_text($audio_file_path) {
        $start_time = microtime(true);
        
        // در اینجا باید از سرویس تشخیص گفتار استفاده کنیم
        // برای مثال: Google Speech-to-Text، Azure Speech، یا OpenAI Whisper
        
        // استفاده از OpenAI Whisper API
        $transcription_result = $this->transcribe_with_whisper($audio_file_path);
        
        if ($transcription_result['success']) {
            $processing_time = round((microtime(true) - $start_time) * 1000);
            
            return array(
                'success' => true,
                'text' => $transcription_result['text'],
                'confidence' => $transcription_result['confidence'] ?? 0.9,
                'language' => $transcription_result['language'] ?? $this->voice_settings['language'],
                'processing_time' => $processing_time
            );
        } else {
            return array(
                'success' => false,
                'message' => $transcription_result['error'] ?? __('خطا در تشخیص گفتار', 'aria-chatbot')
            );
        }
    }
    
    /**
     * تشخیص گفتار با Whisper
     */
    private function transcribe_with_whisper($audio_file_path) {
        $api_key = aria_get_option('openai_api_key');
        
        if (empty($api_key)) {
            return array(
                'success' => false,
                'error' => __('کلید API OpenAI موجود نیست', 'aria-chatbot')
            );
        }
        
        $url = 'https://api.openai.com/v1/audio/transcriptions';
        
        // آماده‌سازی داده‌ها
        $post_fields = array(
            'file' => new CURLFile($audio_file_path),
            'model' => 'whisper-1',
            'language' => $this->get_whisper_language_code(),
            'response_format' => 'verbose_json'
        );
        
        // ارسال درخواست
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $post_fields,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $api_key
            ),
            CURLOPT_TIMEOUT => 30
        ));
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            $data = json_decode($response, true);
            
            return array(
                'success' => true,
                'text' => $data['text'] ?? '',
                'language' => $data['language'] ?? 'fa',
                'confidence' => $this->calculate_confidence($data)
            );
        } else {
            $error_data = json_decode($response, true);
            
            return array(
                'success' => false,
                'error' => $error_data['error']['message'] ?? __('خطای ناشناخته', 'aria-chatbot')
            );
        }
    }
    
    /**
     * محاسبه اعتماد
     */
    private function calculate_confidence($whisper_data) {
        // Whisper معمولاً اعتماد مستقیم برنمی‌گرداند
        // بر اساس طول متن و کیفیت پاسخ تخمین می‌زنیم
        $text_length = strlen($whisper_data['text'] ?? '');
        
        if ($text_length > 50) {
            return 0.9;
        } elseif ($text_length > 20) {
            return 0.8;
        } elseif ($text_length > 5) {
            return 0.7;
        } else {
            return 0.5;
        }
    }
    
    /**
     * دریافت کد زبان برای Whisper
     */
    private function get_whisper_language_code() {
        $language = $this->voice_settings['language'] ?? 'fa-IR';
        $language_map = array(
            'fa-IR' => 'fa',
            'en-US' => 'en',
            'en-GB' => 'en',
            'ar-SA' => 'ar',
            'fr-FR' => 'fr',
            'de-DE' => 'de',
            'es-ES' => 'es',
            'it-IT' => 'it',
            'pt-BR' => 'pt',
            'ru-RU' => 'ru',
            'ja-JP' => 'ja',
            'ko-KR' => 'ko',
            'zh-CN' => 'zh',
            'hi-IN' => 'hi',
            'tr-TR' => 'tr'
        );
        
        return $language_map[$language] ?? 'fa';
    }
    
    /**
     * تبدیل متن به گفتار
     */
    public function text_to_speech($text, $language = null, $voice = null) {
        // بررسی فعال بودن TTS
        if (!($this->voice_settings['tts_enabled'] ?? false)) {
            return array(
                'success' => false,
                'error' => __('تبدیل متن به گفتار غیرفعال است', 'aria-chatbot')
            );
        }
        
        // تنظیم پارامترها
        $language = $language ?: ($this->voice_settings['language'] ?? 'fa-IR');
        $voice_gender = $voice ?: ($this->voice_settings['voice_gender'] ?? 'female');
        
        // تولید نام فایل یکتا
        $file_hash = md5($text . $language . $voice_gender);
        $file_name = 'tts_' . $file_hash . '.mp3';
        $file_path = $this->audio_upload_path . $file_name;
        $file_url = $this->audio_upload_url . $file_name;
        
        // بررسی وجود فایل در کش
        if (file_exists($file_path)) {
            return array(
                'success' => true,
                'audio_url' => $file_url,
                'cached' => true
            );
        }
        
        // تولید فایل صوتی جدید
        $generation_result = $this->generate_speech_file($text, $language, $voice_gender, $file_path);
        
        if ($generation_result['success']) {
            return array(
                'success' => true,
                'audio_url' => $file_url,
                'cached' => false,
                'file_size' => filesize($file_path)
            );
        } else {
            return $generation_result;
        }
    }
    
    /**
     * تولید فایل گفتار
     */
    private function generate_speech_file($text, $language, $voice_gender, $output_path) {
        // اگر Web Speech API از طریق JavaScript استفاده می‌کنیم
        // یا از سرویس‌های ابری مثل Google Text-to-Speech
        
        // برای مثال، استفاده از Azure Cognitive Services
        return $this->generate_with_azure_tts($text, $language, $voice_gender, $output_path);
    }
    
    /**
     * تولید گفتار با Azure
     */
    private function generate_with_azure_tts($text, $language, $voice_gender, $output_path) {
        // کلید Azure (باید در تنظیمات باشد)
        $azure_key = $this->voice_settings['azure_speech_key'] ?? '';
        $azure_region = $this->voice_settings['azure_speech_region'] ?? 'westus';
        
        if (empty($azure_key)) {
            // استفاده از روش جایگزین (مثل ResponsiveVoice یا browser-based TTS)
            return $this->generate_with_browser_tts($text, $language, $voice_gender, $output_path);
        }
        
        $voice_name = $this->get_azure_voice_name($language, $voice_gender);
        
        $ssml = sprintf(
            '<speak version="1.0" xmlns="http://www.w3.org/2001/10/synthesis" xml:lang="%s">
                <voice name="%s">
                    <prosody rate="%s" pitch="%s" volume="%s">
                        %s
                    </prosody>
                </voice>
            </speak>',
            $language,
            $voice_name,
            $this->voice_settings['speech_rate'] ?? '1.0',
            $this->voice_settings['speech_pitch'] ?? '1.0',
            $this->voice_settings['speech_volume'] ?? '0.8',
            htmlspecialchars($text)
        );
        
        $url = "https://{$azure_region}.tts.speech.microsoft.com/cognitiveservices/v1";
        
        $headers = array(
            'Ocp-Apim-Subscription-Key: ' . $azure_key,
            'Content-Type: application/ssml+xml',
            'X-Microsoft-OutputFormat: audio-16khz-128kbitrate-mono-mp3'
        );
        
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $ssml,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30
        ));
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            file_put_contents($output_path, $response);
            
            return array(
                'success' => true,
                'method' => 'azure'
            );
        } else {
            return array(
                'success' => false,
                'error' => __('خطا در تولید گفتار', 'aria-chatbot')
            );
        }
    }
    
    /**
     * تولید گفتار با مرورگر (fallback)
     */
    private function generate_with_browser_tts($text, $language, $voice_gender, $output_path) {
        // این روش نیاز به پردازش سمت کلاینت دارد
        // فعلاً یک فایل ساختگی تولید می‌کنیم
        
        // در واقعیت، باید از JavaScript و Web Audio API استفاده کنیم
        return array(
            'success' => false,
            'error' => __('سرویس تبدیل متن به گفتار در دسترس نیست', 'aria-chatbot')
        );
    }
    
    /**
     * دریافت نام صدای Azure
     */
    private function get_azure_voice_name($language, $gender) {
        $voice_map = array(
            'fa-IR' => array(
                'male' => 'fa-IR-FaridNeural',
                'female' => 'fa-IR-DilaraNeural'
            ),
            'en-US' => array(
                'male' => 'en-US-DavisNeural',
                'female' => 'en-US-JennyNeural'
            ),
            'ar-SA' => array(
                'male' => 'ar-SA-HamedNeural',
                'female' => 'ar-SA-ZariyahNeural'
            )
        );
        
        return $voice_map[$language][$gender] ?? 'en-US-JennyNeural';
    }
    
    /**
     * مدیریت درخواست TTS AJAX
     */
    public function handle_text_to_speech() {
        check_ajax_referer('aria_voice_nonce', 'nonce');
        
        $text = sanitize_textarea_field($_POST['text'] ?? '');
        $language = sanitize_text_field($_POST['language'] ?? '');
        $voice = sanitize_text_field($_POST['voice'] ?? '');
        
        if (empty($text)) {
            wp_send_json_error(__('متن خالی است', 'aria-chatbot'));
        }
        
        $result = $this->text_to_speech($text, $language, $voice);
        wp_send_json($result);
    }
    
    /**
     * تست تنظیمات صوتی
     */
    public function test_voice_settings() {
        check_ajax_referer('aria_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('دسترسی غیرمجاز', 'aria-chatbot'));
        }
        
        $test_text = sanitize_text_field($_POST['test_text'] ?? 'این یک متن تست است.');
        $language = sanitize_text_field($_POST['language'] ?? 'fa-IR');
        $voice = sanitize_text_field($_POST['voice'] ?? 'female');
        
        $result = $this->text_to_speech($test_text, $language, $voice);
        wp_send_json($result);
    }
    
    /**
     * آپلود صدای سفارشی
     */
    public function upload_custom_sound() {
        check_ajax_referer('aria_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('دسترسی غیرمجاز', 'aria-chatbot'));
        }
        
        if (!isset($_FILES['sound_file'])) {
            wp_send_json_error(__('فایل انتخاب نشده', 'aria-chatbot'));
        }
        
        $file = $_FILES['sound_file'];
        $sound_type = sanitize_text_field($_POST['sound_type'] ?? 'notification');
        
        // اعتبارسنجی
        $validation = $this->validate_audio_file($file);
        if (!$validation['valid']) {
            wp_send_json_error($validation['message']);
        }
        
        // تعیین نام فایل
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = $sound_type . '_custom.' . $file_extension;
        $destination = $this->audio_upload_path . 'custom/' . $new_filename;
        
        // ایجاد دایرکتوری در صورت عدم وجود
        $custom_dir = $this->audio_upload_path . 'custom/';
        if (!file_exists($custom_dir)) {
            wp_mkdir_p($custom_dir);
        }
        
        // انتقال فایل
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $file_url = $this->audio_upload_url . 'custom/' . $new_filename;
            
            // به‌روزرسانی تنظیمات
            $this->voice_settings[$sound_type . '_sound'] = $file_url;
            update_option('aria_chatbot_voice_options', $this->voice_settings);
            
            wp_send_json_success(array(
                'file_url' => $file_url,
                'message' => __('فایل صوتی با موفقیت آپلود شد', 'aria-chatbot')
            ));
        } else {
            wp_send_json_error(__('خطا در آپلود فایل', 'aria-chatbot'));
        }
    }
    
    /**
     * حذف فایل صوتی
     */
    public function delete_audio_file() {
        check_ajax_referer('aria_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('دسترسی غیرمجاز', 'aria-chatbot'));
        }
        
        $file_url = esc_url_raw($_POST['file_url'] ?? '');
        
        if (empty($file_url)) {
            wp_send_json_error(__('URL فایل مشخص نشده', 'aria-chatbot'));
        }
        
        // تبدیل URL به مسیر فایل
        $file_path = str_replace($this->audio_upload_url, $this->audio_upload_path, $file_url);
        
        if (file_exists($file_path) && strpos($file_path, $this->audio_upload_path) === 0) {
            unlink($file_path);
            wp_send_json_success(__('فایل حذف شد', 'aria-chatbot'));
        } else {
            wp_send_json_error(__('فایل یافت نشد', 'aria-chatbot'));
        }
    }
    
    /**
     * پاک‌سازی فایل‌های قدیمی
     */
    public function cleanup_old_audio_files() {
        $directories = array(
            $this->audio_upload_path . 'temp/',
            $this->audio_upload_path
        );
        
        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                continue;
            }
            
            $files = glob($dir . '*.{mp3,wav,ogg}', GLOB_BRACE);
            $cutoff_time = strtotime('-7 days'); // فایل‌های بالای 7 روز
            
            foreach ($files as $file) {
                if (filemtime($file) < $cutoff_time) {
                    unlink($file);
                }
            }
        }
    }
    
    /**
     * اضافه کردن پاسخ صوتی
     */
    public function add_voice_response($response, $context) {
        if (!($this->voice_settings['tts_enabled'] ?? false)) {
            return $response;
        }
        
        if (!($this->voice_settings['auto_play_responses'] ?? false)) {
            return $response;
        }
        
        $tts_result = $this->text_to_speech($response['message']);
        
        if ($tts_result['success']) {
            $response['audio_url'] = $tts_result['audio_url'];
        }
        
        return $response;
    }
    
    /**
     * دریافت زبان‌های پشتیبانی شده
     */
    public function get_supported_languages() {
        return $this->supported_languages;
    }
    
    /**
     * دریافت صداهای در دسترس
     */
    public function get_available_voices($language = null) {
        if ($language) {
            return $this->available_voices[$language] ?? array();
        }
        
        return $this->available_voices;
    }
    
    /**
     * دریافت آمار استفاده صوتی
     */
    public function get_voice_usage_stats() {
        return array(
            'voice_messages_today' => $this->count_voice_messages_today(),
            'tts_generations_today' => $this->count_tts_generations_today(),
            'total_audio_files' => $this->count_total_audio_files(),
            'storage_used' => $this->calculate_storage_usage()
        );
    }
    
    /**
     * شمارش پیام‌های صوتی امروز
     */
    private function count_voice_messages_today() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aria_conversations';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} 
             WHERE DATE(timestamp) = %s 
             AND audio_message_url IS NOT NULL",
            current_time('Y-m-d')
        ));
    }
    
    /**
     * شمارش تولیدات TTS امروز
     */
    private function count_tts_generations_today() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aria_conversations';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} 
             WHERE DATE(timestamp) = %s 
             AND response_audio_url IS NOT NULL",
            current_time('Y-m-d')
        ));
    }
    
    /**
     * شمارش کل فایل‌های صوتی
     */
    private function count_total_audio_files() {
        $count = 0;
        $directories = array(
            $this->audio_upload_path,
            $this->audio_upload_path . 'custom/',
            $this->audio_upload_path . 'temp/'
        );
        
        foreach ($directories as $dir) {
            if (file_exists($dir)) {
                $files = glob($dir . '*.{mp3,wav,ogg}', GLOB_BRACE);
                $count += count($files);
            }
        }
        
        return $count;
    }
    
    /**
     * محاسبه فضای استفاده شده
     */
    private function calculate_storage_usage() {
        $size = 0;
        $directories = array(
            $this->audio_upload_path,
            $this->audio_upload_path . 'custom/',
            $this->audio_upload_path . 'temp/'
        );
        
        foreach ($directories as $dir) {
            if (file_exists($dir)) {
                $files = glob($dir . '*.{mp3,wav,ogg}', GLOB_BRACE);
                foreach ($files as $file) {
                    $size += filesize($file);
                }
            }
        }
        
        return $size; // بایت
    }
}

// راه‌اندازی کلاس
new Aria_Voice_Handler();