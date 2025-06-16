<?php
/**
 * کلاس موتور شخصیت پشتیبان هوشمند آریا
 * 
 * @package Aria_Chatbot
 * @author علی مطلقیان
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Aria_Personality_Engine {
    
    /**
     * تنظیمات شخصیت
     */
    private $personality_settings;
    
    /**
     * ویژگی‌های شخصیتی
     */
    private $personality_traits;
    
    /**
     * قالب‌های پاسخ
     */
    private $response_templates;
    
    /**
     * حالات عاطفی
     */
    private $emotional_states;
    
    /**
     * سبک‌های گفتگو
     */
    private $conversation_styles;
    
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
        $this->load_personality_settings();
        
        // راه‌اندازی ویژگی‌های شخصیتی
        $this->init_personality_traits();
        
        // تنظیم قالب‌های پاسخ
        $this->setup_response_templates();
        
        // تنظیم هوک‌ها
        $this->setup_hooks();
    }
    
    /**
     * بارگذاری تنظیمات شخصیت
     */
    private function load_personality_settings() {
        $this->personality_settings = get_option('aria_chatbot_personality_options', array());
        
        // تنظیمات پیش‌فرض
        $defaults = array(
            'bot_name' => 'آریا',
            'personality_type' => 'friendly',
            'tone' => 'professional',
            'humor_level' => 3,
            'formality_level' => 3,
            'empathy_level' => 4,
            'enthusiasm_level' => 3,
            'patience_level' => 5,
            'emoji_usage' => 'moderate',
            'greeting_style' => 'warm',
            'goodbye_style' => 'polite',
            'error_handling_style' => 'apologetic',
            'custom_traits' => '',
            'conversation_starters' => array(
                'سلام! چطور می‌تونم کمکتون کنم؟',
                'چه سوالی دارید؟',
                'کدوم بخش رو می‌خواین بررسی کنیم؟'
            ),
            'catch_phrases' => array(
                'خوشحالم که کمکتون کردم!',
                'امیدوارم مفید بوده باشه',
                'سوال دیگه‌ای دارید؟'
            )
        );
        
        $this->personality_settings = array_merge($defaults, $this->personality_settings);
    }
    
    /**
     * راه‌اندازی ویژگی‌های شخصیتی
     */
    private function init_personality_traits() {
        $this->personality_traits = array(
            'friendly' => array(
                'characteristics' => array(
                    'warm', 'approachable', 'supportive', 'encouraging'
                ),
                'language_style' => 'casual_warm',
                'response_pattern' => 'positive_reinforcement'
            ),
            'professional' => array(
                'characteristics' => array(
                    'competent', 'reliable', 'efficient', 'knowledgeable'
                ),
                'language_style' => 'formal_respectful',
                'response_pattern' => 'direct_informative'
            ),
            'enthusiastic' => array(
                'characteristics' => array(
                    'energetic', 'optimistic', 'motivational', 'passionate'
                ),
                'language_style' => 'expressive_dynamic',
                'response_pattern' => 'engaging_inspiring'
            ),
            'wise' => array(
                'characteristics' => array(
                    'thoughtful', 'patient', 'insightful', 'philosophical'
                ),
                'language_style' => 'calm_reflective',
                'response_pattern' => 'deep_understanding'
            ),
            'playful' => array(
                'characteristics' => array(
                    'humorous', 'creative', 'lighthearted', 'witty'
                ),
                'language_style' => 'casual_fun',
                'response_pattern' => 'entertaining_clever'
            ),
            'caring' => array(
                'characteristics' => array(
                    'empathetic', 'nurturing', 'understanding', 'compassionate'
                ),
                'language_style' => 'gentle_supportive',
                'response_pattern' => 'emotional_support'
            )
        );
        
        $this->emotional_states = array(
            'happy' => array(
                'indicators' => array('خوشحال', 'شاد', 'راضی'),
                'response_modifiers' => array('enthusiasm_boost', 'positive_language'),
                'emoji_suggestions' => array('😊', '😄', '🎉')
            ),
            'sad' => array(
                'indicators' => array('ناراحت', 'غمگین', 'افسرده'),
                'response_modifiers' => array('empathy_increase', 'supportive_language'),
                'emoji_suggestions' => array('🤗', '💙')
            ),
            'frustrated' => array(
                'indicators' => array('عصبی', 'ناراضی', 'خسته'),
                'response_modifiers' => array('patience_increase', 'calm_language'),
                'emoji_suggestions' => array('🙏', '😌')
            ),
            'excited' => array(
                'indicators' => array('هیجان‌زده', 'متشکر', 'عالی'),
                'response_modifiers' => array('enthusiasm_match', 'celebratory_language'),
                'emoji_suggestions' => array('🎉', '✨', '🌟')
            ),
            'confused' => array(
                'indicators' => array('گیج', 'نمی‌فهمم', 'پیچیده'),
                'response_modifiers' => array('clarity_increase', 'simple_language'),
                'emoji_suggestions' => array('🤔', '💭')
            ),
            'neutral' => array(
                'indicators' => array(),
                'response_modifiers' => array('balanced_approach'),
                'emoji_suggestions' => array('👍', '😊')
            )
        );
        
        $this->conversation_styles = array(
            'formal' => array(
                'pronouns' => array('شما', 'جناب', 'سرکار'),
                'phrases' => array('لطفاً', 'متشکرم', 'در خدمتم'),
                'avoid' => array('خب', 'اوکی', 'باشه')
            ),
            'casual' => array(
                'pronouns' => array('تو', 'شما'),
                'phrases' => array('خب', 'اوکی', 'باشه', 'مرسی'),
                'avoid' => array('جناب', 'سرکار', 'محترم')
            ),
            'balanced' => array(
                'pronouns' => array('شما'),
                'phrases' => array('ممنونم', 'خوشحالم', 'امیدوارم'),
                'avoid' => array()
            )
        );
    }
    
    /**
     * تنظیم قالب‌های پاسخ
     */
    private function setup_response_templates() {
        $this->response_templates = array(
            'greeting' => array(
                'formal' => array(
                    'سلام و احترام، چطور می‌تونم کمکتون کنم؟',
                    'درود، در خدمت شما هستم.',
                    'سلام، چه کاری براتون انجام بدم؟'
                ),
                'casual' => array(
                    'سلام! چطوری؟ چی کار داری؟',
                    'های! چطور می‌تونم کمکت کنم؟',
                    'سلام عزیز! چه خبر؟'
                ),
                'balanced' => array(
                    'سلام! چطور می‌تونم کمکتون کنم؟',
                    'درود! چه سوالی دارید؟',
                    'سلام، کدوم کار رو براتون انجام بدم؟'
                )
            ),
            'goodbye' => array(
                'formal' => array(
                    'امیدوارم مفید بوده باشم. موفق باشید.',
                    'خوشحالم که تونستم کمکتون کنم. بدرود.',
                    'در صورت نیاز در خدمتم. روز خوبی داشته باشید.'
                ),
                'casual' => array(
                    'خداحافظ! امیدوارم مفید بوده باشه!',
                    'بای بای! هر وقت نیاز داشتی برگرد.',
                    'فعلاً! روز خوبی داشته باش!'
                ),
                'balanced' => array(
                    'خداحافظ! امیدوارم مفید بوده باشه.',
                    'موفق باشید! هر وقت سوالی داشتید در خدمتم.',
                    'بدرود! روز خوبی داشته باشید.'
                )
            ),
            'apology' => array(
                'formal' => array(
                    'متأسفم، به نظر مشکلی پیش اومده.',
                    'عذرخواهی می‌کنم، اجازه بدید دوباره تلاش کنم.',
                    'ببخشید، ممکنه اشتباه کرده باشم.'
                ),
                'casual' => array(
                    'اوه! متأسفم، یه مشکلی پیش اومد.',
                    'ببخشید! بذار دوباره امتحان کنم.',
                    'اووپس! اشتباه کردم به نظر.'
                ),
                'balanced' => array(
                    'متأسفم، ممکنه مشکلی پیش اومده باشه.',
                    'ببخشید، اجازه بدید دوباره تلاش کنم.',
                    'عذرخواهی می‌کنم اگه کمکی نکردم.'
                )
            ),
            'clarification' => array(
                'formal' => array(
                    'ممکنه لطفاً سوالتون رو واضح‌تر بپرسید؟',
                    'متوجه نشدم. اجازه می‌دید توضیح بیشتری بدید؟',
                    'کمی مبهمه، می‌شه دقیق‌تر بگید؟'
                ),
                'casual' => array(
                    'متوجه نشدم! می‌تونی واضح‌تر بگی؟',
                    'نفهمیدم چی گفتی! دوباره بگو؟',
                    'یکم گیج شدم! می‌شه روشن‌تر توضیح بدی؟'
                ),
                'balanced' => array(
                    'متوجه نشدم. می‌شه واضح‌تر توضیح بدید؟',
                    'کمی مبهمه، ممکنه دقیق‌تر بگید؟',
                    'نفهمیدم منظورتون چیه، می‌شه روشن‌تر بگید؟'
                )
            ),
            'encouragement' => array(
                'all' => array(
                    'عالی! ادامه بدید!',
                    'خیلی خوب پیش می‌رید!',
                    'آفرین! داری خوب کار می‌کنی!',
                    'تبریک! موفق بودید!',
                    'فوق‌العاده! همینطور ادامه بدید!'
                )
            ),
            'empathy' => array(
                'understanding' => array(
                    'متوجه موقعیتتون هستم.',
                    'می‌تونم احساستون رو درک کنم.',
                    'کاملاً حق دارید که اینطور فکر کنید.'
                ),
                'supportive' => array(
                    'نگران نباشید، کنارتونم.',
                    'می‌دونم سخته، ولی می‌تونیم حلش کنیم.',
                    'با هم این مشکل رو برطرف می‌کنیم.'
                )
            )
        );
    }
    
    /**
     * تنظیم هوک‌ها
     */
    private function setup_hooks() {
        // فیلترهای اصلی
        add_filter('aria_system_prompt', array($this, 'add_personality_prompt'), 20);
        add_filter('aria_response_message', array($this, 'apply_personality_style'), 10, 2);
        add_filter('aria_greeting_message', array($this, 'personalize_greeting'));
        add_filter('aria_error_message', array($this, 'personalize_error_response'));
        
        // AJAX handlers
        add_action('wp_ajax_aria_test_personality', array($this, 'test_personality_response'));
        add_action('wp_ajax_aria_generate_sample_responses', array($this, 'generate_sample_responses'));
        add_action('wp_ajax_aria_analyze_personality_consistency', array($this, 'analyze_personality_consistency'));
        
        // هوک‌های تحلیل
        add_action('aria_response_generated', array($this, 'analyze_response_personality'));
        add_action('aria_conversation_ended', array($this, 'evaluate_personality_performance'));
    }
    
    /**
     * اضافه کردن پرامپت شخصیت
     */
    public function add_personality_prompt($system_prompt) {
        $personality_prompt = $this->build_personality_prompt();
        
        if (!empty($personality_prompt)) {
            $system_prompt .= "\n\n=== شخصیت و سبک پاسخ‌دهی ===\n" . $personality_prompt;
        }
        
        return $system_prompt;
    }
    
    /**
     * ساخت پرامپت شخصیت
     */
    private function build_personality_prompt() {
        $prompt_parts = array();
        
        // نام و هویت
        $bot_name = $this->personality_settings['bot_name'];
        $prompt_parts[] = "نام شما: {$bot_name}";
        
        // نوع شخصیت
        $personality_type = $this->personality_settings['personality_type'];
        $traits = $this->personality_traits[$personality_type] ?? $this->personality_traits['friendly'];
        
        $characteristics = implode('، ', $traits['characteristics']);
        $prompt_parts[] = "ویژگی‌های شخصیتی شما: {$characteristics}";
        
        // سطح‌های مختلف شخصیت
        $humor_level = intval($this->personality_settings['humor_level']);
        $empathy_level = intval($this->personality_settings['empathy_level']);
        $enthusiasm_level = intval($this->personality_settings['enthusiasm_level']);
        $patience_level = intval($this->personality_settings['patience_level']);
        
        $prompt_parts[] = $this->generate_level_instruction('شوخ‌طبعی', $humor_level);
        $prompt_parts[] = $this->generate_level_instruction('همدلی', $empathy_level);
        $prompt_parts[] = $this->generate_level_instruction('اشتیاق', $enthusiasm_level);
        $prompt_parts[] = $this->generate_level_instruction('صبر', $patience_level);
        
        // سبک گفتگو
        $tone = $this->personality_settings['tone'];
        $style_guide = $this->conversation_styles[$tone] ?? $this->conversation_styles['balanced'];
        
        if (!empty($style_guide['phrases'])) {
            $phrases = implode('، ', $style_guide['phrases']);
            $prompt_parts[] = "عبارات مناسب برای استفاده: {$phrases}";
        }
        
        if (!empty($style_guide['avoid'])) {
            $avoid_phrases = implode('، ', $style_guide['avoid']);
            $prompt_parts[] = "عبارات قابل اجتناب: {$avoid_phrases}";
        }
        
        // استفاده از ایموجی
        $emoji_usage = $this->personality_settings['emoji_usage'];
        $prompt_parts[] = $this->get_emoji_instruction($emoji_usage);
        
        // ویژگی‌های سفارشی
        $custom_traits = trim($this->personality_settings['custom_traits']);
        if (!empty($custom_traits)) {
            $prompt_parts[] = "ویژگی‌های خاص شما: {$custom_traits}";
        }
        
        // دستورالعمل‌های کلی
        $prompt_parts[] = "همیشه مودب، مفید و دوستانه باشید.";
        $prompt_parts[] = "پاسخ‌هایتان را متناسب با سوال و نیاز کاربر تنظیم کنید.";
        $prompt_parts[] = "در صورت عدم اطمینان، صادقانه اعتراف کنید که نمی‌دانید.";
        
        return implode("\n", $prompt_parts);
    }
    
    /**
     * تولید دستورالعمل بر اساس سطح
     */
    private function generate_level_instruction($trait, $level) {
        $instructions = array(
            'شوخ‌طبعی' => array(
                1 => 'کاملاً جدی باشید و از شوخی خودداری کنید.',
                2 => 'فقط گاهی از شوخی‌های خیلی ملایم استفاده کنید.',
                3 => 'می‌توانید از شوخی‌های مناسب و سالم استفاده کنید.',
                4 => 'شوخ‌طبع باشید و فضای گفتگو را شاد نگه دارید.',
                5 => 'کاملاً شوخ‌طبع و سرگرم‌کننده باشید.'
            ),
            'همدلی' => array(
                1 => 'فقط روی اطلاعات متمرکز شوید.',
                2 => 'کمی به احساسات کاربر توجه کنید.',
                3 => 'همدلی معقولی نشان دهید.',
                4 => 'همدلی زیادی نشان دهید و به احساسات کاربر توجه کنید.',
                5 => 'کاملاً همدل باشید و احساسات را در اولویت قرار دهید.'
            ),
            'اشتیاق' => array(
                1 => 'کاملاً ساده و بی‌طرف پاسخ دهید.',
                2 => 'کمی انرژی مثبت نشان دهید.',
                3 => 'با انگیزه و مثبت باشید.',
                4 => 'پرانرژی و متحمس باشید.',
                5 => 'کاملاً پرشور و هیجان‌زده باشید.'
            ),
            'صبر' => array(
                1 => 'سریع و مستقیم پاسخ دهید.',
                2 => 'کمی صبور باشید.',
                3 => 'صبر معقولی داشته باشید.',
                4 => 'بسیار صبور و تحمل‌گر باشید.',
                5 => 'نهایت صبر و درنگ را داشته باشید.'
            )
        );
        
        return $instructions[$trait][$level] ?? "سطح {$trait} شما: {$level}";
    }
    
    /**
     * دستورالعمل استفاده از ایموجی
     */
    private function get_emoji_instruction($emoji_usage) {
        switch ($emoji_usage) {
            case 'none':
                return 'هیچ‌گاه از ایموجی استفاده نکنید.';
            case 'minimal':
                return 'فقط گاهی و در موارد ضروری از ایموجی استفاده کنید.';
            case 'moderate':
                return 'می‌توانید از ایموجی مناسب استفاده کنید تا پیام‌تان دوستانه‌تر باشد.';
            case 'frequent':
                return 'از ایموجی زیاد استفاده کنید تا گفتگو شاد و پویا باشد.';
            default:
                return 'متعادل از ایموجی استفاده کنید.';
        }
    }
    
    /**
     * اعمال سبک شخصیت به پاسخ
     */
    public function apply_personality_style($response, $context = array()) {
        // تشخیص حالت عاطفی کاربر
        $user_emotion = $this->detect_user_emotion($context['user_message'] ?? '');
        
        // تنظیم پاسخ بر اساس حالت عاطفی
        $response = $this->adjust_response_for_emotion($response, $user_emotion);
        
        // اضافه کردن عبارات شخصیتی
        $response = $this->add_personality_phrases($response, $context);
        
        // اضافه کردن ایموجی
        $response = $this->add_appropriate_emojis($response, $user_emotion);
        
        // تنظیم تن گفتار
        $response = $this->adjust_tone($response);
        
        // اضافه کردن عبارات تشویقی
        $response = $this->add_encouragement_if_needed($response, $context);
        
        return $response;
    }
    
    /**
     * تشخیص حالت عاطفی کاربر
     */
    private function detect_user_emotion($user_message) {
        $detected_emotions = array();
        
        foreach ($this->emotional_states as $emotion => $data) {
            $score = 0;
            foreach ($data['indicators'] as $indicator) {
                if (strpos($user_message, $indicator) !== false) {
                    $score++;
                }
            }
            if ($score > 0) {
                $detected_emotions[$emotion] = $score;
            }
        }
        
        if (empty($detected_emotions)) {
            return 'neutral';
        }
        
        // برگرداندن حالت با بالاترین امتیاز
        return array_keys($detected_emotions, max($detected_emotions))[0];
    }
    
    /**
     * تنظیم پاسخ بر اساس حالت عاطفی
     */
    private function adjust_response_for_emotion($response, $emotion) {
        $emotion_data = $this->emotional_states[$emotion] ?? $this->emotional_states['neutral'];
        
        foreach ($emotion_data['response_modifiers'] as $modifier) {
            $response = $this->apply_response_modifier($response, $modifier);
        }
        
        return $response;
    }
    
    /**
     * اعمال تغییرات بر اساس modifier
     */
    private function apply_response_modifier($response, $modifier) {
        switch ($modifier) {
            case 'enthusiasm_boost':
                // اضافه کردن کلمات پرانرژی
                $energetic_words = array('عالی', 'فوق‌العاده', 'خیلی خوب');
                $random_word = $energetic_words[array_rand($energetic_words)];
                $response = $random_word . '! ' . $response;
                break;
                
            case 'empathy_increase':
                // اضافه کردن عبارات همدلانه
                $empathy_phrases = array('متوجه موقعیتتون هستم', 'می‌تونم احساستون رو درک کنم');
                $random_phrase = $empathy_phrases[array_rand($empathy_phrases)];
                $response = $random_phrase . '. ' . $response;
                break;
                
            case 'patience_increase':
                // اضافه کردن عبارات آرامش‌بخش
                $calming_phrases = array('نگران نباشید', 'آرام باشید', 'با هم حلش می‌کنیم');
                $random_phrase = $calming_phrases[array_rand($calming_phrases)];
                $response = $random_phrase . '. ' . $response;
                break;
                
            case 'clarity_increase':
                // ساده‌تر کردن زبان
                $response = $this->simplify_language($response);
                break;
        }
        
        return $response;
    }
    
    /**
     * ساده‌سازی زبان
     */
    private function simplify_language($text) {
        $complex_replacements = array(
            'استفاده می‌کنید' => 'استفاده کنید',
            'می‌توانید' => 'می‌تونید',
            'امکان دارد' => 'ممکنه',
            'در صورت تمایل' => 'اگه بخواید',
            'به منظور' => 'برای'
        );
        
        foreach ($complex_replacements as $complex => $simple) {
            $text = str_replace($complex, $simple, $text);
        }
        
        return $text;
    }
    
    /**
     * اضافه کردن عبارات شخصیتی
     */
    private function add_personality_phrases($response, $context) {
        $personality_type = $this->personality_settings['personality_type'];
        $traits = $this->personality_traits[$personality_type];
        
        // اضافه کردن عبارات کاراکتریستیک
        if (rand(1, 4) === 1) { // 25% احتمال
            $catch_phrases = $this->personality_settings['catch_phrases'];
            if (!empty($catch_phrases)) {
                $random_phrase = $catch_phrases[array_rand($catch_phrases)];
                $response .= ' ' . $random_phrase;
            }
        }
        
        return $response;
    }
    
    /**
     * اضافه کردن ایموجی مناسب
     */
    private function add_appropriate_emojis($response, $emotion) {
        $emoji_usage = $this->personality_settings['emoji_usage'];
        
        if ($emoji_usage === 'none') {
            return $response;
        }
        
        $emotion_data = $this->emotional_states[$emotion] ?? $this->emotional_states['neutral'];
        $suitable_emojis = $emotion_data['emoji_suggestions'];
        
        $probability = array(
            'minimal' => 0.1,
            'moderate' => 0.3,
            'frequent' => 0.6
        );
        
        $chance = $probability[$emoji_usage] ?? 0.3;
        
        if (mt_rand() / mt_getrandmax() < $chance && !empty($suitable_emojis)) {
            $emoji = $suitable_emojis[array_rand($suitable_emojis)];
            $response .= ' ' . $emoji;
        }
        
        return $response;
    }
    
    /**
     * تنظیم تن گفتار
     */
    private function adjust_tone($response) {
        $tone = $this->personality_settings['tone'];
        $style = $this->conversation_styles[$tone] ?? $this->conversation_styles['balanced'];
        
        // جایگزینی الفاظ بر اساس سبک
        foreach ($style['avoid'] as $avoid_word) {
            if (strpos($response, $avoid_word) !== false) {
                // یافتن جایگزین مناسب
                $replacement = $this->find_tone_replacement($avoid_word, $tone);
                if ($replacement) {
                    $response = str_replace($avoid_word, $replacement, $response);
                }
            }
        }
        
        return $response;
    }
    
    /**
     * یافتن جایگزین مناسب برای کلمات
     */
    private function find_tone_replacement($word, $tone) {
        $replacements = array(
            'formal' => array(
                'خب' => 'بسیار خوب',
                'اوکی' => 'بسیار خوب',
                'باشه' => 'بله، حتماً'
            ),
            'casual' => array(
                'جناب' => '',
                'سرکار' => '',
                'محترم' => ''
            )
        );
        
        return $replacements[$tone][$word] ?? null;
    }
    
    /**
     * اضافه کردن تشویق در صورت نیاز
     */
    private function add_encouragement_if_needed($response, $context) {
        $enthusiasm_level = intval($this->personality_settings['enthusiasm_level']);
        
        if ($enthusiasm_level >= 4 && rand(1, 3) === 1) {
            $encouragements = $this->response_templates['encouragement']['all'];
            $encouragement = $encouragements[array_rand($encouragements)];
            $response .= ' ' . $encouragement;
        }
        
        return $response;
    }
    
    /**
     * شخصی‌سازی پیام خوش‌آمدگویی
     */
    public function personalize_greeting($greeting = null) {
        $tone = $this->personality_settings['tone'];
        $greeting_style = $this->personality_settings['greeting_style'];
        
        $greetings = $this->response_templates['greeting'][$tone] ?? 
                    $this->response_templates['greeting']['balanced'];
        
        if ($greeting_style === 'enthusiastic') {
            $enthusiastic_greetings = array(
                'وای! سلام! خیلی خوشحالم که اینجایی! 🎉',
                'هورا! یه نفر جدید! سلام عزیز! ✨',
                'سلام سلام! چه عالی که اومدی! 😄'
            );
            $greetings = array_merge($greetings, $enthusiastic_greetings);
        } elseif ($greeting_style === 'warm') {
            $warm_greetings = array(
                'سلام گرم! احساس می‌کنم امروز روز خوبیه! 😊',
                'سلام دوست عزیز! خوشحالم که باهاتون آشنا شدم! 🤗',
                'سلام! امیدوارم حالتون عالی باشه! 💙'
            );
            $greetings = array_merge($greetings, $warm_greetings);
        }
        
        return $greetings[array_rand($greetings)];
    }
    
    /**
     * شخصی‌سازی پیام خطا
     */
    public function personalize_error_response($error_message) {
        $error_style = $this->personality_settings['error_handling_style'];
        $tone = $this->personality_settings['tone'];
        
        $apologies = $this->response_templates['apology'][$tone] ?? 
                    $this->response_templates['apology']['balanced'];
        
        $apology = $apologies[array_rand($apologies)];
        
        if ($error_style === 'humorous') {
            $humorous_errors = array(
                'اووپس! به نظر مغزم قهوه می‌خواد! ☕',
                'یه لحظه... نرم‌افزارم داره آپدیت میشه! 🔄',
                'ببخشید، یکم گیج شدم! بذارید دوباره فکر کنم! 🤔'
            );
            $apology = $humorous_errors[array_rand($humorous_errors)];
        } elseif ($error_style === 'empathetic') {
            $empathetic_errors = array(
                'ای وای، واقعاً متأسفم که این اتفاق افتاد! 😔',
                'می‌دونم ناراحت‌کننده است، ببخشید! 🙏',
                'از اینکه مشکلی پیش اومده واقعاً ناراحتم! 💙'
            );
            $apology = $empathetic_errors[array_rand($empathetic_errors)];
        }
        
        return $apology . ' ' . $error_message;
    }
    
    /**
     * تولید پاسخ‌های نمونه برای تست
     */
    public function generate_sample_responses() {
        check_ajax_referer('aria_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('دسترسی غیرمجاز', 'aria-chatbot'));
        }
        
        $test_messages = array(
            'سلام، چطورید؟',
            'ممنون از کمکتون!',
            'این چیه که گفتید؟ نفهمیدم!',
            'عالی بود! خیلی مفید بود!',
            'ناراحتم، کاری درست پیش نمی‌ره'
        );
        
        $samples = array();
        
        foreach ($test_messages as $message) {
            $emotion = $this->detect_user_emotion($message);
            $response = 'این یک پاسخ نمونه است که بر اساس تنظیمات شخصیت شما تولید شده.';
            
            $personalized_response = $this->apply_personality_style(
                $response, 
                array('user_message' => $message)
            );
            
            $samples[] = array(
                'user_message' => $message,
                'detected_emotion' => $emotion,
                'bot_response' => $personalized_response
            );
        }
        
        wp_send_json_success($samples);
    }
    
    /**
     * تست شخصیت
     */
    public function test_personality_response() {
        check_ajax_referer('aria_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('دسترسی غیرمجاز', 'aria-chatbot'));
        }
        
        $test_message = sanitize_text_field($_POST['test_message'] ?? '');
        $test_scenario = sanitize_text_field($_POST['test_scenario'] ?? 'general');
        
        if (empty($test_message)) {
            wp_send_json_error(__('پیام تست خالی است', 'aria-chatbot'));
        }
        
        // پاسخ پایه
        $base_response = 'بر اساس پیام شما، من سعی می‌کنم بهترین کمک را ارائه دهم.';
        
        // اعمال شخصیت
        $context = array(
            'user_message' => $test_message,
            'scenario' => $test_scenario
        );
        
        $personalized_response = $this->apply_personality_style($base_response, $context);
        
        // تحلیل شخصیت
        $analysis = array(
            'detected_emotion' => $this->detect_user_emotion($test_message),
            'applied_modifiers' => $this->get_applied_modifiers($test_message),
            'personality_score' => $this->calculate_personality_score($personalized_response)
        );
        
        wp_send_json_success(array(
            'original_response' => $base_response,
            'personalized_response' => $personalized_response,
            'analysis' => $analysis
        ));
    }
    
    /**
     * محاسبه امتیاز شخصیت
     */
    private function calculate_personality_score($response) {
        $scores = array(
            'friendliness' => $this->measure_friendliness($response),
            'professionalism' => $this->measure_professionalism($response),
            'empathy' => $this->measure_empathy($response),
            'enthusiasm' => $this->measure_enthusiasm($response)
        );
        
        return $scores;
    }
    
    /**
     * اندازه‌گیری دوستانه بودن
     */
    private function measure_friendliness($response) {
        $friendly_indicators = array('سلام', 'خوشحالم', 'عزیز', 'دوست', '😊', '🤗');
        $score = 0;
        
        foreach ($friendly_indicators as $indicator) {
            if (strpos($response, $indicator) !== false) {
                $score++;
            }
        }
        
        return min(5, $score);
    }
    
    /**
     * اندازه‌گیری حرفه‌ای بودن
     */
    private function measure_professionalism($response) {
        $professional_indicators = array('لطفاً', 'متشکرم', 'در خدمت', 'محترم');
        $unprofessional_indicators = array('خب', 'اوکی', 'باشه');
        
        $score = 0;
        
        foreach ($professional_indicators as $indicator) {
            if (strpos($response, $indicator) !== false) {
                $score++;
            }
        }
        
        foreach ($unprofessional_indicators as $indicator) {
            if (strpos($response, $indicator) !== false) {
                $score--;
            }
        }
        
        return max(0, min(5, $score));
    }
    
    /**
     * اندازه‌گیری همدلی
     */
    private function measure_empathy($response) {
        $empathy_indicators = array('متوجه', 'احساس', 'درک می‌کنم', 'نگران', '💙', '🤗');
        $score = 0;
        
        foreach ($empathy_indicators as $indicator) {
            if (strpos($response, $indicator) !== false) {
                $score++;
            }
        }
        
        return min(5, $score);
    }
    
    /**
     * اندازه‌گیری اشتیاق
     */
    private function measure_enthusiasm($response) {
        $enthusiasm_indicators = array('عالی', 'فوق‌العاده', 'خیلی خوب', '!', '🎉', '✨');
        $score = 0;
        
        foreach ($enthusiasm_indicators as $indicator) {
            if (strpos($response, $indicator) !== false) {
                $score++;
            }
        }
        
        return min(5, $score);
    }
    
    /**
     * دریافت تغییرات اعمال شده
     */
    private function get_applied_modifiers($message) {
        $emotion = $this->detect_user_emotion($message);
        $emotion_data = $this->emotional_states[$emotion] ?? $this->emotional_states['neutral'];
        
        return $emotion_data['response_modifiers'];
    }
    
    /**
     * تحلیل پیام تولید شده
     */
    public function analyze_response_personality($response_data) {
        $response = $response_data['response'];
        $context = $response_data['context'] ?? array();
        
        // ذخیره آمار شخصیت
        $personality_stats = array(
            'response_length' => mb_strlen($response),
            'emoji_count' => substr_count($response, '😊') + substr_count($response, '🤗'), // تعداد ایموجی‌ها
            'exclamation_count' => substr_count($response, '!'),
            'personality_consistency' => $this->check_personality_consistency($response),
            'timestamp' => current_time('mysql')
        );
        
        // ذخیره در دیتابیس یا cache
        $this->store_personality_analytics($personality_stats);
    }
    
    /**
     * بررسی ثبات شخصیت
     */
    private function check_personality_consistency($response) {
        $current_personality = $this->personality_settings['personality_type'];
        $expected_traits = $this->personality_traits[$current_personality]['characteristics'];
        
        $consistency_score = 0;
        $total_traits = count($expected_traits);
        
        foreach ($expected_traits as $trait) {
            if ($this->response_exhibits_trait($response, $trait)) {
                $consistency_score++;
            }
        }
        
        return $total_traits > 0 ? ($consistency_score / $total_traits) * 100 : 0;
    }
    
    /**
     * بررسی نمایش ویژگی در پاسخ
     */
    private function response_exhibits_trait($response, $trait) {
        $trait_indicators = array(
            'warm' => array('گرم', 'صمیمی', 'دوستانه'),
            'supportive' => array('کمک', 'حمایت', 'پشتیبانی'),
            'professional' => array('حرفه‌ای', 'دقیق', 'مناسب'),
            'enthusiastic' => array('هیجان', 'انرژی', 'پرشور')
        );
        
        $indicators = $trait_indicators[$trait] ?? array();
        
        foreach ($indicators as $indicator) {
            if (strpos($response, $indicator) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * ذخیره آمار شخصیت
     */
    private function store_personality_analytics($stats) {
        $daily_stats = get_transient('aria_personality_daily_stats') ?: array();
        $daily_stats[] = $stats;
        
        // نگهداری آخرین 100 آمار
        if (count($daily_stats) > 100) {
            $daily_stats = array_slice($daily_stats, -100);
        }
        
        set_transient('aria_personality_daily_stats', $daily_stats, DAY_IN_SECONDS);
    }
    
    /**
     * ارزیابی عملکرد شخصیت
     */
    public function evaluate_personality_performance($conversation_data) {
        $user_satisfaction = $conversation_data['user_satisfaction'] ?? null;
        $conversation_length = $conversation_data['message_count'] ?? 0;
        
        if ($user_satisfaction && $conversation_length > 2) {
            // ذخیره نتایج ارزیابی
            $evaluation = array(
                'satisfaction' => $user_satisfaction,
                'conversation_length' => $conversation_length,
                'personality_type' => $this->personality_settings['personality_type'],
                'evaluation_date' => current_time('mysql')
            );
            
            $this->store_personality_evaluation($evaluation);
        }
    }
    
    /**
     * ذخیره ارزیابی شخصیت
     */
    private function store_personality_evaluation($evaluation) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aria_personality_evaluations';
        
        $wpdb->insert(
            $table_name,
            $evaluation,
            array('%s', '%d', '%s', '%s')
        );
    }
    
    /**
     * دریافت پرامپت شخصیت
     */
    public function get_personality_prompt() {
        return $this->build_personality_prompt();
    }
    
    /**
     * دریافت تنظیمات شخصیت
     */
    public function get_personality_settings() {
        return $this->personality_settings;
    }
    
    /**
     * به‌روزرسانی تنظیمات شخصیت
     */
    public function update_personality_settings($new_settings) {
        $this->personality_settings = array_merge($this->personality_settings, $new_settings);
        update_option('aria_chatbot_personality_options', $this->personality_settings);
        
        // بازیابی مجدد تنظیمات
        $this->load_personality_settings();
    }
    
    /**
     * دریافت استارترهای مکالمه
     */
    public function get_conversation_starter() {
        $starters = $this->personality_settings['conversation_starters'] ?? array();
        
        if (!empty($starters) && is_array($starters)) {
            $filtered_starters = array_filter($starters, function($starter) {
                return !empty(trim($starter));
            });
            
            if (!empty($filtered_starters)) {
                $selected_starter = $filtered_starters[array_rand($filtered_starters)];
                return $this->apply_personality_style($selected_starter);
            }
        }
        
        // استارتر پیش‌فرض
        $default_starter = 'سلام! چطور می‌تونم کمکتون کنم؟';
        return $this->apply_personality_style($default_starter);
    }
}

// راه‌اندازی کلاس
new Aria_Personality_Engine();