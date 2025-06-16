<?php
/**
 * کلاس Frontend پشتیبان هوشمند آریا
 * 
 * @package Aria_Chatbot
 * @author علی مطلقیان
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Aria_Frontend {
    
    /**
     * تنظیمات عمومی
     */
    private $settings;
    
    /**
     * تنظیمات طراحی
     */
    private $design_settings;
    
    /**
     * تنظیمات شخصیت
     */
    private $personality_settings;
    
    /**
     * تنظیمات صوتی
     */
    private $voice_settings;
    
    /**
     * شناسه جلسه فعلی
     */
    private $session_id;
    
    /**
     * شناسه کاربر فعلی
     */
    private $user_id;
    
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
        
        // شناسایی کاربر و جلسه
        $this->identify_session();
        
        // تنظیم هوک‌ها
        $this->setup_hooks();
    }
    
    /**
     * بارگذاری تنظیمات
     */
    private function load_settings() {
        $this->settings = get_option('aria_chatbot_options', array());
        $this->design_settings = get_option('aria_chatbot_design_options', array());
        $this->personality_settings = get_option('aria_chatbot_personality_options', array());
        $this->voice_settings = get_option('aria_chatbot_voice_options', array());
    }
    
    /**
     * شناسایی جلسه و کاربر
     */
    private function identify_session() {
        // شناسایی کاربر
        if (is_user_logged_in()) {
            $this->user_id = get_current_user_id();
        } else {
            // برای کاربران مهمان
            $this->user_id = 'guest_' . md5($this->get_user_ip() . $_SERVER['HTTP_USER_AGENT']);
        }
        
        // تولید شناسه جلسه
        $this->session_id = $this->generate_session_id();
    }
    
    /**
     * تولید شناسه جلسه
     */
    private function generate_session_id() {
        $session_key = 'aria_session_' . $this->user_id;
        $existing_session = wp_cache_get($session_key);
        
        if ($existing_session) {
            return $existing_session;
        }
        
        $new_session = 'session_' . $this->user_id . '_' . time() . '_' . wp_rand(1000, 9999);
        wp_cache_set($session_key, $new_session, '', 3600); // 1 hour
        
        return $new_session;
    }
    
    /**
     * تنظیم هوک‌ها
     */
    private function setup_hooks() {
        // بارگذاری assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // نمایش چت بات
        add_action('wp_footer', array($this, 'render_chatbot'));
        
        // AJAX handlers
        add_action('wp_ajax_aria_send_message', array($this, 'handle_message'));
        add_action('wp_ajax_nopriv_aria_send_message', array($this, 'handle_message'));
        add_action('wp_ajax_aria_get_conversation_history', array($this, 'get_conversation_history'));
        add_action('wp_ajax_nopriv_aria_get_conversation_history', array($this, 'get_conversation_history'));
        add_action('wp_ajax_aria_rate_response', array($this, 'rate_response'));
        add_action('wp_ajax_nopriv_aria_rate_response', array($this, 'rate_response'));
        add_action('wp_ajax_aria_end_session', array($this, 'end_session'));
        add_action('wp_ajax_nopriv_aria_end_session', array($this, 'end_session'));
        
        // Shortcode
        add_shortcode('aria_chatbot', array($this, 'chatbot_shortcode'));
        
        // فیلترها
        add_filter('aria_chatbot_should_display', array($this, 'should_display_chatbot'));
        add_filter('aria_chatbot_user_permissions', array($this, 'check_user_permissions'));
        
        // ثبت جلسه در شروع
        add_action('wp', array($this, 'register_session_start'));
    }
    
    /**
     * بارگذاری assets فرانت‌اند
     */
    public function enqueue_frontend_assets() {
        // بررسی نمایش چت بات
        if (!$this->should_display_chatbot()) {
            return;
        }
        
        // CSS اصلی
        wp_enqueue_style(
            'aria-chatbot-frontend',
            ARIA_CHATBOT_PLUGIN_URL . 'public/css/chatbot.css',
            array(),
            ARIA_CHATBOT_VERSION
        );
        
        // CSS تم
        $theme = $this->design_settings['theme'] ?? 'modern';
        wp_enqueue_style(
            'aria-chatbot-theme',
            ARIA_CHATBOT_PLUGIN_URL . "public/css/themes/{$theme}.css",
            array('aria-chatbot-frontend'),
            ARIA_CHATBOT_VERSION
        );
        
        // JavaScript اصلی
        wp_enqueue_script(
            'aria-chatbot-frontend',
            ARIA_CHATBOT_PLUGIN_URL . 'public/js/chatbot-frontend.js',
            array('jquery'),
            ARIA_CHATBOT_VERSION,
            true
        );
        
        // JavaScript قابلیت‌های پیشرفته
        wp_enqueue_script(
            'aria-chatbot-advanced',
            ARIA_CHATBOT_PLUGIN_URL . 'public/js/chatbot-advanced.js',
            array('aria-chatbot-frontend'),
            ARIA_CHATBOT_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('aria-chatbot-frontend', 'ariaChatbot', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aria_chatbot_nonce'),
            'session_id' => $this->session_id,
            'user_id' => $this->user_id,
            'settings' => $this->get_frontend_settings(),
            'design' => $this->get_design_config(),
            'voice' => $this->get_voice_config(),
            'personality' => $this->get_personality_config(),
            'strings' => $this->get_localized_strings(),
            'rtl' => is_rtl(),
            'current_page' => array(
                'url' => get_permalink(),
                'title' => get_the_title(),
                'type' => $this->get_page_type()
            )
        ));
        
        // CSS سفارشی
        add_action('wp_head', array($this, 'output_custom_css'));
    }
    
    /**
     * دریافت تنظیمات فرانت‌اند
     */
    private function get_frontend_settings() {
        return array(
            'enabled' => $this->settings['enabled'] ?? true,
            'auto_open' => $this->settings['auto_open'] ?? false,
            'auto_open_delay' => $this->settings['auto_open_delay'] ?? 3000,
            'typing_delay' => $this->settings['typing_delay'] ?? 1000,
            'max_message_length' => $this->settings['max_message_length'] ?? 500,
            'show_timestamps' => $this->settings['show_timestamps'] ?? true,
            'show_typing_indicator' => $this->settings['show_typing_indicator'] ?? true,
            'enable_sound_effects' => $this->settings['enable_sound_effects'] ?? true,
            'conversation_timeout' => $this->settings['conversation_timeout'] ?? 1800, // 30 minutes
            'quick_replies' => $this->settings['quick_replies'] ?? array(),
            'persistent_history' => $this->settings['persistent_history'] ?? true,
            'show_satisfaction_survey' => $this->settings['show_satisfaction_survey'] ?? true,
            'enable_file_upload' => $this->settings['enable_file_upload'] ?? false,
            'allowed_file_types' => $this->settings['allowed_file_types'] ?? array('jpg', 'png', 'pdf'),
            'max_file_size' => $this->settings['max_file_size'] ?? 5242880 // 5MB
        );
    }
    
    /**
     * دریافت پیکربندی طراحی
     */
    private function get_design_config() {
        return array(
            'position' => $this->design_settings['position'] ?? 'bottom-right',
            'theme' => $this->design_settings['theme'] ?? 'modern',
            'primary_color' => $this->design_settings['primary_color'] ?? '#667eea',
            'secondary_color' => $this->design_settings['secondary_color'] ?? '#f8f9fa',
            'accent_color' => $this->design_settings['accent_color'] ?? '#764ba2',
            'text_color' => $this->design_settings['text_color'] ?? '#333333',
            'font_family' => $this->design_settings['font_family'] ?? 'system-ui',
            'font_size' => $this->design_settings['font_size'] ?? '14px',
            'border_radius' => $this->design_settings['border_radius'] ?? '12px',
            'animation_type' => $this->design_settings['animation_type'] ?? 'slide',
            'show_avatar' => $this->design_settings['show_avatar'] ?? true,
            'custom_avatar' => $this->design_settings['custom_avatar'] ?? '',
            'width' => $this->design_settings['width'] ?? '350px',
            'height' => $this->design_settings['height'] ?? '500px',
            'z_index' => $this->design_settings['z_index'] ?? 9999,
            'custom_css' => $this->design_settings['custom_css'] ?? ''
        );
    }
    
    /**
     * دریافت پیکربندی صوتی
     */
    private function get_voice_config() {
        return array(
            'voice_input_enabled' => $this->voice_settings['voice_input_enabled'] ?? false,
            'tts_enabled' => $this->voice_settings['tts_enabled'] ?? false,
            'language' => $this->voice_settings['language'] ?? 'fa-IR',
            'voice_gender' => $this->voice_settings['voice_gender'] ?? 'female',
            'speech_rate' => $this->voice_settings['speech_rate'] ?? 1.0,
            'speech_pitch' => $this->voice_settings['speech_pitch'] ?? 1.0,
            'speech_volume' => $this->voice_settings['speech_volume'] ?? 0.8,
            'auto_play_responses' => $this->voice_settings['auto_play_responses'] ?? false,
            'voice_commands_enabled' => $this->voice_settings['voice_commands_enabled'] ?? true
        );
    }
    
    /**
     * دریافت پیکربندی شخصیت
     */
    private function get_personality_config() {
        return array(
            'bot_name' => $this->personality_settings['bot_name'] ?? 'آریا',
            'greeting_message' => $this->get_personalized_greeting(),
            'personality_type' => $this->personality_settings['personality_type'] ?? 'friendly',
            'conversation_starters' => $this->personality_settings['conversation_starters'] ?? array()
        );
    }
    
    /**
     * دریافت متن‌های محلی‌سازی شده
     */
    private function get_localized_strings() {
        return array(
            'welcome_message' => __('سلام! چطور می‌تونم کمکتون کنم؟', 'aria-chatbot'),
            'type_message' => __('پیام خود را بنویسید...', 'aria-chatbot'),
            'send' => __('ارسال', 'aria-chatbot'),
            'connecting' => __('در حال اتصال...', 'aria-chatbot'),
            'typing' => __('در حال تایپ...', 'aria-chatbot'),
            'error_occurred' => __('خطایی رخ داد. لطفاً دوباره تلاش کنید.', 'aria-chatbot'),
            'network_error' => __('خطا در برقراری ارتباط', 'aria-chatbot'),
            'message_too_long' => __('پیام شما خیلی طولانی است', 'aria-chatbot'),
            'recording' => __('در حال ضبط...', 'aria-chatbot'),
            'processing' => __('در حال پردازش...', 'aria-chatbot'),
            'speak' => __('پخش صوتی', 'aria-chatbot'),
            'stop_speaking' => __('توقف پخش', 'aria-chatbot'),
            'minimize' => __('کوچک کردن', 'aria-chatbot'),
            'maximize' => __('بزرگ کردن', 'aria-chatbot'),
            'close' => __('بستن', 'aria-chatbot'),
            'new_conversation' => __('مکالمه جدید', 'aria-chatbot'),
            'rate_response' => __('امتیاز دهید', 'aria-chatbot'),
            'helpful' => __('مفید بود', 'aria-chatbot'),
            'not_helpful' => __('مفید نبود', 'aria-chatbot'),
            'thanks_feedback' => __('از بازخوردتان متشکریم', 'aria-chatbot'),
            'file_upload' => __('آپلود فایل', 'aria-chatbot'),
            'file_too_large' => __('اندازه فایل بیش از حد مجاز است', 'aria-chatbot'),
            'file_type_not_allowed' => __('نوع فایل مجاز نیست', 'aria-chatbot')
        );
    }
    
    /**
     * تشخیص نوع صفحه
     */
    private function get_page_type() {
        if (is_home() || is_front_page()) {
            return 'home';
        } elseif (is_shop() || is_product_category() || is_product_tag()) {
            return 'shop';
        } elseif (is_product()) {
            return 'product';
        } elseif (is_cart()) {
            return 'cart';
        } elseif (is_checkout()) {
            return 'checkout';
        } elseif (is_account_page()) {
            return 'account';
        } elseif (is_single()) {
            return 'post';
        } elseif (is_page()) {
            return 'page';
        } elseif (is_category() || is_tag() || is_archive()) {
            return 'archive';
        } elseif (is_search()) {
            return 'search';
        } else {
            return 'other';
        }
    }
    
    /**
     * خروجی CSS سفارشی
     */
    public function output_custom_css() {
        $custom_css = $this->generate_dynamic_css();
        
        if (!empty($custom_css)) {
            echo "<style id='aria-chatbot-custom-css'>\n" . $custom_css . "\n</style>\n";
        }
    }
    
    /**
     * تولید CSS پویا
     */
    private function generate_dynamic_css() {
        $design = $this->design_settings;
        $css = '';
        
        // متغیرهای CSS
        $css .= ":root {\n";
        $css .= "  --aria-primary-color: " . ($design['primary_color'] ?? '#667eea') . ";\n";
        $css .= "  --aria-secondary-color: " . ($design['secondary_color'] ?? '#f8f9fa') . ";\n";
        $css .= "  --aria-accent-color: " . ($design['accent_color'] ?? '#764ba2') . ";\n";
        $css .= "  --aria-text-color: " . ($design['text_color'] ?? '#333333') . ";\n";
        $css .= "  --aria-font-family: " . ($design['font_family'] ?? 'system-ui') . ";\n";
        $css .= "  --aria-font-size: " . ($design['font_size'] ?? '14px') . ";\n";
        $css .= "  --aria-border-radius: " . ($design['border_radius'] ?? '12px') . ";\n";
        $css .= "  --aria-width: " . ($design['width'] ?? '350px') . ";\n";
        $css .= "  --aria-height: " . ($design['height'] ?? '500px') . ";\n";
        $css .= "  --aria-z-index: " . ($design['z_index'] ?? '9999') . ";\n";
        $css .= "}\n\n";
        
        // موقعیت چت بات
        $position = $design['position'] ?? 'bottom-right';
        $css .= "#aria-chatbot-container {\n";
        $css .= "  position: fixed;\n";
        $css .= "  z-index: var(--aria-z-index);\n";
        
        switch ($position) {
            case 'bottom-left':
                $css .= "  bottom: 20px;\n  left: 20px;\n";
                break;
            case 'bottom-center':
                $css .= "  bottom: 20px;\n  left: 50%;\n  transform: translateX(-50%);\n";
                break;
            case 'top-right':
                $css .= "  top: 20px;\n  right: 20px;\n";
                break;
            case 'top-left':
                $css .= "  top: 20px;\n  left: 20px;\n";
                break;
            case 'top-center':
                $css .= "  top: 20px;\n  left: 50%;\n  transform: translateX(-50%);\n";
                break;
            case 'center-right':
                $css .= "  top: 50%;\n  right: 20px;\n  transform: translateY(-50%);\n";
                break;
            case 'center-left':
                $css .= "  top: 50%;\n  left: 20px;\n  transform: translateY(-50%);\n";
                break;
            default: // bottom-right
                $css .= "  bottom: 20px;\n  right: 20px;\n";
        }
        $css .= "}\n\n";
        
        // انیمیشن
        $animation_type = $design['animation_type'] ?? 'slide';
        switch ($animation_type) {
            case 'fade':
                $css .= ".aria-chatbot-window {\n";
                $css .= "  transition: opacity 0.3s ease;\n";
                $css .= "}\n";
                $css .= ".aria-chatbot-window.hidden {\n";
                $css .= "  opacity: 0;\n";
                $css .= "  pointer-events: none;\n";
                $css .= "}\n\n";
                break;
                
            case 'scale':
                $css .= ".aria-chatbot-window {\n";
                $css .= "  transition: transform 0.3s ease, opacity 0.3s ease;\n";
                $css .= "  transform-origin: bottom right;\n";
                $css .= "}\n";
                $css .= ".aria-chatbot-window.hidden {\n";
                $css .= "  transform: scale(0.8);\n";
                $css .= "  opacity: 0;\n";
                $css .= "  pointer-events: none;\n";
                $css .= "}\n\n";
                break;
                
            default: // slide
                $css .= ".aria-chatbot-window {\n";
                $css .= "  transition: transform 0.3s ease;\n";
                $css .= "}\n";
                $css .= ".aria-chatbot-window.hidden {\n";
                $css .= "  transform: translateY(100%);\n";
                $css .= "}\n\n";
        }
        
        // CSS سفارشی اضافی
        if (!empty($design['custom_css'])) {
            $css .= $design['custom_css'] . "\n";
        }
        
        return $css;
    }
    
    /**
     * نمایش چت بات
     */
    public function render_chatbot() {
        if (!$this->should_display_chatbot()) {
            return;
        }
        
        $design = $this->design_settings;
        $personality = $this->personality_settings;
        $voice = $this->voice_settings;
        
        $bot_name = $personality['bot_name'] ?? 'آریا';
        $welcome_message = $this->get_personalized_greeting();
        $avatar_url = $this->get_avatar_url();
        
        ?>
        <div id="aria-chatbot-container" class="aria-chatbot-container" style="display: none;">
            <!-- دکمه باز/بسته کردن -->
            <div id="aria-chatbot-toggle" class="aria-chatbot-toggle">
                <div class="aria-toggle-icon">
                    <?php if ($avatar_url): ?>
                        <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($bot_name); ?>" class="aria-bot-avatar">
                    <?php else: ?>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                            <path d="M13 8H8"/>
                            <path d="M16 12H8"/>
                        </svg>
                    <?php endif; ?>
                </div>
                <div class="aria-notification-badge" id="aria-notification-badge" style="display: none;">
                    <span class="aria-badge-count">1</span>
                </div>
            </div>
            
            <!-- پنجره چت -->
            <div id="aria-chatbot-window" class="aria-chatbot-window hidden">
                <!-- هدر -->
                <div class="aria-chatbot-header">
                    <div class="aria-header-info">
                        <?php if ($avatar_url): ?>
                            <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($bot_name); ?>" class="aria-header-avatar">
                        <?php endif; ?>
                        <div class="aria-header-text">
                            <h4 class="aria-bot-name"><?php echo esc_html($bot_name); ?></h4>
                            <span class="aria-status-indicator">
                                <span class="aria-status-dot"></span>
                                <span class="aria-status-text"><?php _e('آنلاین', 'aria-chatbot'); ?></span>
                            </span>
                        </div>
                    </div>
                    <div class="aria-header-actions">
                        <button class="aria-action-btn" id="aria-new-conversation" title="<?php _e('مکالمه جدید', 'aria-chatbot'); ?>">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 5v14"/>
                                <path d="M5 12h14"/>
                            </svg>
                        </button>
                        <button class="aria-action-btn" id="aria-minimize" title="<?php _e('کوچک کردن', 'aria-chatbot'); ?>">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M6 9l6 6 6-6"/>
                            </svg>
                        </button>
                        <button class="aria-action-btn" id="aria-close" title="<?php _e('بستن', 'aria-chatbot'); ?>">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"/>
                                <line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- ناحیه پیام‌ها -->
                <div class="aria-messages-container" id="aria-messages">
                    <!-- پیام خوش‌آمدگویی -->
                    <div class="aria-message aria-bot-message" data-message-id="welcome">
                        <?php if ($avatar_url): ?>
                            <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($bot_name); ?>" class="aria-message-avatar">
                        <?php endif; ?>
                        <div class="aria-message-content">
                            <div class="aria-message-text">
                                <?php echo esc_html($welcome_message); ?>
                            </div>
                            <div class="aria-message-actions">
                                <?php if ($voice['tts_enabled']): ?>
                                    <button class="aria-speak-btn" title="<?php _e('پخش صوتی', 'aria-chatbot'); ?>">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/>
                                            <path d="M15.54 8.46a5 5 0 0 1 0 7.07"/>
                                            <path d="M19.07 4.93a10 10 0 0 1 0 14.14"/>
                                        </svg>
                                    </button>
                                <?php endif; ?>
                            </div>
                            <div class="aria-message-time">
                                <?php echo current_time('H:i'); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- پیشنهادات سریع -->
                    <?php if (!empty($this->settings['quick_replies'])): ?>
                        <div class="aria-quick-replies" id="aria-quick-replies">
                            <?php foreach ($this->settings['quick_replies'] as $reply): ?>
                                <button class="aria-quick-reply-btn" data-reply="<?php echo esc_attr($reply); ?>">
                                    <?php echo esc_html($reply); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- نشانگر تایپ -->
                <div class="aria-typing-indicator" id="aria-typing-indicator" style="display: none;">
                    <?php if ($avatar_url): ?>
                        <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($bot_name); ?>" class="aria-message-avatar">
                    <?php endif; ?>
                    <div class="aria-typing-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
                
                <!-- ناحیه ورودی -->
                <div class="aria-input-container">
                    <!-- نوار ابزار -->
                    <div class="aria-input-toolbar" id="aria-input-toolbar">
                        <?php if ($voice['voice_input_enabled']): ?>
                            <button class="aria-toolbar-btn" id="aria-voice-input" title="<?php _e('ورودی صوتی', 'aria-chatbot'); ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/>
                                    <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                                    <line x1="12" y1="19" x2="12" y2="23"/>
                                    <line x1="8" y1="23" x2="16" y2="23"/>
                                </svg>
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($this->settings['enable_file_upload']): ?>
                            <button class="aria-toolbar-btn" id="aria-file-upload" title="<?php _e('آپلود فایل', 'aria-chatbot'); ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66L9.64 16.2a2 2 0 0 1-2.83-2.83l8.49-8.49"/>
                                </svg>
                            </button>
                            <input type="file" id="aria-file-input" style="display: none;" accept="<?php echo esc_attr(implode(',', $this->settings['allowed_file_types'] ?? array())); ?>">
                        <?php endif; ?>
                        
                        <button class="aria-toolbar-btn" id="aria-emoji-picker" title="<?php _e('ایموجی', 'aria-chatbot'); ?>">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M8 14s1.5 2 4 2 4-2 4-2"/>
                                <line x1="9" y1="9" x2="9.01" y2="9"/>
                                <line x1="15" y1="9" x2="15.01" y2="9"/>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- فیلد ورودی -->
                    <div class="aria-input-field">
                        <textarea 
                            id="aria-message-input" 
                            class="aria-message-input" 
                            placeholder="<?php _e('پیام خود را بنویسید...', 'aria-chatbot'); ?>"
                            rows="1"
                            maxlength="<?php echo intval($this->settings['max_message_length'] ?? 500); ?>"></textarea>
                        <button class="aria-send-btn" id="aria-send-message" disabled>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="22" y1="2" x2="11" y2="13"/>
                                <polygon points="22,2 15,22 11,13 2,9"/>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- شمارنده کاراکتر -->
                    <div class="aria-character-count" id="aria-character-count">
                        <span class="aria-count">0</span>/<span class="aria-max"><?php echo intval($this->settings['max_message_length'] ?? 500); ?></span>
                    </div>
                </div>
                
                <!-- فوتر -->
                <div class="aria-chatbot-footer">
                    <div class="aria-powered-by">
                        <?php printf(__('قدرت گرفته از %s', 'aria-chatbot'), '<strong>آریا</strong>'); ?>
                    </div>
                    <div class="aria-footer-actions">
                        <button class="aria-feedback-btn" id="aria-feedback" title="<?php _e('بازخورد', 'aria-chatbot'); ?>">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- مودال بازخورد -->
        <div id="aria-feedback-modal" class="aria-modal" style="display: none;">
            <div class="aria-modal-content">
                <div class="aria-modal-header">
                    <h3><?php _e('بازخورد شما', 'aria-chatbot'); ?></h3>
                    <button class="aria-modal-close">×</button>
                </div>
                <div class="aria-modal-body">
                    <div class="aria-rating-section">
                        <p><?php _e('تجربه شما چطور بود؟', 'aria-chatbot'); ?></p>
                        <div class="aria-rating-stars">
                            <button class="aria-star" data-rating="1">⭐</button>
                            <button class="aria-star" data-rating="2">⭐</button>
                            <button class="aria-star" data-rating="3">⭐</button>
                            <button class="aria-star" data-rating="4">⭐</button>
                            <button class="aria-star" data-rating="5">⭐</button>
                        </div>
                    </div>
                    <div class="aria-comment-section">
                        <textarea id="aria-feedback-text" placeholder="<?php _e('نظر خود را بنویسید...', 'aria-chatbot'); ?>"></textarea>
                    </div>
                    <div class="aria-modal-actions">
                        <button class="aria-btn aria-btn-primary" id="aria-submit-feedback"><?php _e('ارسال', 'aria-chatbot'); ?></button>
                        <button class="aria-btn aria-btn-secondary" id="aria-cancel-feedback"><?php _e('انصراف', 'aria-chatbot'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- صداها -->
        <?php if ($this->settings['enable_sound_effects']): ?>
            <audio id="aria-sound-message-sent" preload="auto">
                <source src="<?php echo ARIA_CHATBOT_PLUGIN_URL . 'public/sounds/message-sent.mp3'; ?>" type="audio/mpeg">
            </audio>
            <audio id="aria-sound-message-received" preload="auto">
                <source src="<?php echo ARIA_CHATBOT_PLUGIN_URL . 'public/sounds/message-received.mp3'; ?>" type="audio/mpeg">
            </audio>
            <audio id="aria-sound-notification" preload="auto">
                <source src="<?php echo ARIA_CHATBOT_PLUGIN_URL . 'public/sounds/notification.mp3'; ?>" type="audio/mpeg">
            </audio>
        <?php endif; ?>
        
        <script>
        // راه‌اندازی چت بات
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof AriaChatBot !== 'undefined') {
                window.ariaChatBotInstance = new AriaChatBot();
            }
        });
        </script>
        <?php
    }
    
    /**
     * بررسی نمایش چت بات
     */
    public function should_display_chatbot() {
        // بررسی فعال بودن
        if (!($this->settings['enabled'] ?? true)) {
            return false;
        }
        
        // بررسی دسترسی کاربر
        if (!$this->check_user_permissions()) {
            return false;
        }
        
        // بررسی صفحات محدود شده
        if ($this->is_restricted_page()) {
            return false;
        }
        
        // بررسی schedule
        if (!$this->is_within_schedule()) {
            return false;
        }
        
        return apply_filters('aria_chatbot_should_display', true);
    }
    
    /**
     * بررسی مجوزهای کاربر
     */
    public function check_user_permissions() {
        $allowed_roles = $this->settings['allowed_user_roles'] ?? array('all');
        
        // اگر همه مجاز هستند
        if (in_array('all', $allowed_roles)) {
            return true;
        }
        
        // کاربران مهمان
        if (!is_user_logged_in()) {
            return in_array('guest', $allowed_roles);
        }
        
        // کاربران وارد شده
        $user = wp_get_current_user();
        return !empty(array_intersect($user->roles, $allowed_roles));
    }
    
    /**
     * بررسی صفحات محدود شده
     */
    private function is_restricted_page() {
        $restricted_pages = $this->settings['restricted_pages'] ?? array();
        
        if (empty($restricted_pages)) {
            return false;
        }
        
        $current_page_id = get_queried_object_id();
        
        return in_array($current_page_id, $restricted_pages);
    }
    
    /**
     * بررسی زمان‌بندی
     */
    private function is_within_schedule() {
        $schedule_enabled = $this->settings['schedule_enabled'] ?? false;
        
        if (!$schedule_enabled) {
            return true;
        }
        
        $current_time = current_time('H:i');
        $current_day = current_time('w'); // 0 = Sunday
        
        $start_time = $this->settings['schedule_start_time'] ?? '09:00';
        $end_time = $this->settings['schedule_end_time'] ?? '18:00';
        $working_days = $this->settings['schedule_working_days'] ?? array(1, 2, 3, 4, 5, 6); // Mon-Sat
        
        // بررسی روز
        if (!in_array($current_day, $working_days)) {
            return false;
        }
        
        // بررسی ساعت
        return ($current_time >= $start_time && $current_time <= $end_time);
    }
    
    /**
     * مدیریت پیام AJAX
     */
    public function handle_message() {
        // بررسی امنیت
        if (!check_ajax_referer('aria_chatbot_nonce', 'nonce', false)) {
            wp_send_json_error(__('درخواست نامعتبر', 'aria-chatbot'));
        }
        
        // دریافت داده‌ها
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $session_id = sanitize_text_field($_POST['session_id'] ?? $this->session_id);
        $message_type = sanitize_text_field($_POST['message_type'] ?? 'text');
        
        if (empty($message)) {
            wp_send_json_error(__('پیام خالی است', 'aria-chatbot'));
        }
        
        // بررسی محدودیت طول پیام
        $max_length = intval($this->settings['max_message_length'] ?? 500);
        if (mb_strlen($message) > $max_length) {
            wp_send_json_error(__('پیام شما خیلی طولانی است', 'aria-chatbot'));
        }
        
        // آماده‌سازی زمینه
        $context = array(
            'session_id' => $session_id,
            'user_id' => $this->user_id,
            'message_type' => $message_type,
            'ip_address' => $this->get_user_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'page_url' => esc_url_raw($_POST['page_url'] ?? ''),
            'page_type' => $this->get_page_type(),
            'timestamp' => current_time('mysql')
        );
        
        // اعمال فیلترهای امنیتی
        $security_check = apply_filters('aria_chatbot_security_check', true, $message, $context);
        if (!$security_check) {
            wp_send_json_error(__('درخواست رد شد', 'aria-chatbot'));
        }
        
        // ارسال به API handler
        $api_handler = new Aria_API_Handler();
        $response = $api_handler->send_message($message, $context);
        
        if ($response['success']) {
            // آماده‌سازی پاسخ برای فرانت‌اند
            $frontend_response = array(
                'message' => $response['message'],
                'message_id' => uniqid('msg_'),
                'timestamp' => current_time('H:i'),
                'avatar' => $this->get_avatar_url(),
                'actions' => $this->get_message_actions($response),
                'suggestions' => $this->get_follow_up_suggestions($response, $context)
            );
            
            // اضافه کردن پاسخ صوتی
            if (!empty($response['audio_url'])) {
                $frontend_response['audio_url'] = $response['audio_url'];
            }
            
            wp_send_json_success($frontend_response);
        } else {
            wp_send_json_error($response['error'] ?? __('خطا در پردازش پیام', 'aria-chatbot'));
        }
    }
    
    /**
     * دریافت اقدامات پیام
     */
    private function get_message_actions($response) {
        $actions = array();
        
        // دکمه پخش صوتی
        if ($this->voice_settings['tts_enabled']) {
            $actions[] = array(
                'type' => 'speak',
                'icon' => 'volume',
                'title' => __('پخش صوتی', 'aria-chatbot')
            );
        }
        
        // دکمه امتیازدهی
        if ($this->settings['show_rating_buttons']) {
            $actions[] = array(
                'type' => 'rate',
                'icon' => 'thumbs-up',
                'title' => __('مفید بود', 'aria-chatbot')
            );
        }
        
        return $actions;
    }
    
    /**
     * دریافت پیشنهادات دنباله‌دار
     */
    private function get_follow_up_suggestions($response, $context) {
        $suggestions = array();
        
        // بر اساس نوع صفحه
        switch ($context['page_type']) {
            case 'product':
                $suggestions = array(
                    __('قیمت چقدره؟', 'aria-chatbot'),
                    __('چطور سفارش بدم؟', 'aria-chatbot'),
                    __('ارسال چقدر طول می‌کشه؟', 'aria-chatbot')
                );
                break;
                
            case 'shop':
                $suggestions = array(
                    __('محصولات پرفروش', 'aria-chatbot'),
                    __('تخفیف‌های موجود', 'aria-chatbot'),
                    __('راهنمای خرید', 'aria-chatbot')
                );
                break;
                
            default:
                $suggestions = array(
                    __('سوال دیگری دارم', 'aria-chatbot'),
                    __('با پشتیبانی صحبت کنم', 'aria-chatbot'),
                    __('ممنون', 'aria-chatbot')
                );
        }
        
        return array_slice($suggestions, 0, 3); // حداکثر 3 پیشنهاد
    }
    
    /**
     * دریافت تاریخچه مکالمه
     */
    public function get_conversation_history() {
        check_ajax_referer('aria_chatbot_nonce', 'nonce');
        
        $session_id = sanitize_text_field($_POST['session_id'] ?? $this->session_id);
        $limit = intval($_POST['limit'] ?? 20);
        
        global $wpdb;
        $table_name = Aria_Database::get_table_name('conversations');
        
        $conversations = $wpdb->get_results($wpdb->prepare(
            "SELECT message, response, timestamp, user_satisfaction 
             FROM {$table_name} 
             WHERE session_id = %s 
             ORDER BY timestamp DESC 
             LIMIT %d",
            $session_id,
            $limit
        ));
        
        $history = array();
        foreach ($conversations as $conv) {
            $history[] = array(
                'user_message' => $conv->message,
                'bot_response' => $conv->response,
                'timestamp' => date('H:i', strtotime($conv->timestamp)),
                'satisfaction' => $conv->user_satisfaction
            );
        }
        
        wp_send_json_success(array_reverse($history));
    }
    
    /**
     * امتیازدهی پاسخ
     */
    public function rate_response() {
        check_ajax_referer('aria_chatbot_nonce', 'nonce');
        
        $conversation_id = intval($_POST['conversation_id'] ?? 0);
        $rating = sanitize_text_field($_POST['rating']); // 'helpful' or 'not_helpful'
        $feedback_text = sanitize_textarea_field($_POST['feedback_text'] ?? '');
        
        if (empty($conversation_id)) {
            wp_send_json_error(__('شناسه مکالمه مشخص نشده', 'aria-chatbot'));
        }
        
        global $wpdb;
        $conversations_table = Aria_Database::get_table_name('conversations');
        
        // به‌روزرسانی امتیاز
        $satisfaction = ($rating === 'helpful') ? 'satisfied' : 'dissatisfied';
        
        $result = $wpdb->update(
            $conversations_table,
            array('user_satisfaction' => $satisfaction),
            array('id' => $conversation_id),
            array('%s'),
            array('%d')
        );
        
        // ذخیره بازخورد تفصیلی
        if (!empty($feedback_text)) {
            $feedback_table = Aria_Database::get_table_name('feedback');
            
            $wpdb->insert(
                $feedback_table,
                array(
                    'user_id' => $this->user_id,
                    'session_id' => $this->session_id,
                    'conversation_id' => $conversation_id,
                    'feedback_type' => 'satisfaction',
                    'rating' => ($rating === 'helpful') ? 5 : 1,
                    'feedback_text' => $feedback_text,
                    'ip_address' => $this->get_user_ip(),
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
                ),
                array('%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s')
            );
        }
        
        if ($result !== false) {
            wp_send_json_success(__('از بازخوردتان متشکریم', 'aria-chatbot'));
        } else {
            wp_send_json_error(__('خطا در ثبت امتیاز', 'aria-chatbot'));
        }
    }
    
    /**
     * پایان جلسه
     */
    public function end_session() {
        check_ajax_referer('aria_chatbot_nonce', 'nonce');
        
        $session_id = sanitize_text_field($_POST['session_id'] ?? $this->session_id);
        $satisfaction = sanitize_text_field($_POST['satisfaction'] ?? '');
        $duration = intval($_POST['duration'] ?? 0);
        
        global $wpdb;
        $sessions_table = Aria_Database::get_table_name('user_sessions');
        
        // به‌روزرسانی جلسه
        $wpdb->update(
            $sessions_table,
            array(
                'end_time' => current_time('mysql'),
                'duration_seconds' => $duration,
                'user_satisfaction' => $satisfaction,
                'is_completed' => 1
            ),
            array('session_id' => $session_id),
            array('%s', '%d', '%s', '%d'),
            array('%s')
        );
        
        // پاک کردن کش جلسه
        wp_cache_delete('aria_session_' . $this->user_id);
        
        wp_send_json_success(__('جلسه به پایان رسید', 'aria-chatbot'));
    }
    
    /**
     * Shortcode چت بات
     */
    public function chatbot_shortcode($atts) {
        $atts = shortcode_atts(array(
            'inline' => 'false',
            'height' => '400px',
            'width' => '100%'
        ), $atts);
        
        if ($atts['inline'] === 'true') {
            ob_start();
            $this->render_inline_chatbot($atts);
            return ob_get_clean();
        }
        
        return '<!-- Aria ChatBot will be displayed in footer -->';
    }
    
    /**
     * نمایش چت بات درون‌خطی
     */
    private function render_inline_chatbot($atts) {
        // implementation for inline chatbot
        echo '<div class="aria-chatbot-inline" style="height: ' . esc_attr($atts['height']) . '; width: ' . esc_attr($atts['width']) . ';">';
        echo 'Inline ChatBot - Coming Soon';
        echo '</div>';
    }
    
    /**
     * ثبت شروع جلسه
     */
    public function register_session_start() {
        if (!$this->should_display_chatbot()) {
            return;
        }
        
        global $wpdb;
        $sessions_table = Aria_Database::get_table_name('user_sessions');
        
        // بررسی وجود جلسه
        $existing_session = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$sessions_table} WHERE session_id = %s",
            $this->session_id
        ));
        
        if (!$existing_session) {
            // ثبت جلسه جدید
            $wpdb->insert(
                $sessions_table,
                array(
                    'session_id' => $this->session_id,
                    'user_id' => $this->user_id,
                    'start_time' => current_time('mysql'),
                    'ip_address' => $this->get_user_ip(),
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                    'referrer_url' => wp_get_referer(),
                    'entry_page' => get_permalink(),
                    'device_type' => $this->detect_device_type(),
                    'browser_info' => $this->get_browser_info()
                ),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
            );
        }
    }
    
    /**
     * تشخیص نوع دستگاه
     */
    private function detect_device_type() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (wp_is_mobile()) {
            if (strpos($user_agent, 'iPad') !== false) {
                return 'tablet';
            }
            return 'mobile';
        }
        
        return 'desktop';
    }
    
    /**
     * دریافت اطلاعات مرورگر
     */
    private function get_browser_info() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // ساده‌سازی شده - در واقعیت باید parser پیچیده‌تری استفاده کرد
        if (strpos($user_agent, 'Chrome') !== false) {
            return 'Chrome';
        } elseif (strpos($user_agent, 'Firefox') !== false) {
            return 'Firefox';
        } elseif (strpos($user_agent, 'Safari') !== false) {
            return 'Safari';
        } elseif (strpos($user_agent, 'Edge') !== false) {
            return 'Edge';
        }
        
        return 'Unknown';
    }
    
    /**
     * دریافت پیام خوش‌آمدگویی شخصی‌سازی شده
     */
    private function get_personalized_greeting() {
        $personality_engine = new Aria_Personality_Engine();
        return $personality_engine->get_conversation_starter();
    }
    
    /**
     * دریافت URL آواتار
     */
    private function get_avatar_url() {
        $custom_avatar = $this->design_settings['custom_avatar'] ?? '';
        
        if (!empty($custom_avatar)) {
            return $custom_avatar;
        }
        
        // آواتار پیش‌فرض
        return ARIA_CHATBOT_PLUGIN_URL . 'public/images/avatar-default.png';
    }
    
    /**
     * دریافت IP کاربر
     */
    private function get_user_ip() {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
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
}

// راه‌اندازی کلاس
if (!is_admin()) {
    new Aria_Frontend();
}