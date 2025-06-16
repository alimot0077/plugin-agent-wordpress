<?php
/**
 * صفحه تنظیمات هوش مصنوعی پشتیبان آریا
 * 
 * @package Aria_Chatbot
 * @path admin/views/ai-settings.php
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_settings = get_option('aria_chatbot_options', array());
$models = $this->get_available_models();
?>

<div class="wrap aria-admin-wrap">
    <div class="aria-header">
        <h1 class="aria-page-title">
            <span class="aria-icon">🧠</span>
            <?php _e('تنظیمات هوش مصنوعی', 'aria-chatbot'); ?>
        </h1>
        <p class="aria-page-subtitle">
            <?php _e('پیکربندی مدل‌های OpenAI و تنظیمات هوش مصنوعی', 'aria-chatbot'); ?>
        </p>
    </div>

    <div class="aria-settings-container">
        <form method="post" action="options.php" id="aria-ai-settings-form">
            <?php settings_fields('aria_chatbot_options'); ?>
            
            <!-- تب‌ها -->
            <div class="aria-tabs">
                <nav class="aria-tabs-nav">
                    <button type="button" class="aria-tab-button active" data-tab="connection">
                        <span class="dashicons dashicons-admin-links"></span>
                        <?php _e('اتصال API', 'aria-chatbot'); ?>
                    </button>
                    <button type="button" class="aria-tab-button" data-tab="models">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php _e('مدل‌ها', 'aria-chatbot'); ?>
                    </button>
                    <button type="button" class="aria-tab-button" data-tab="parameters">
                        <span class="dashicons dashicons-admin-tools"></span>
                        <?php _e('پارامترها', 'aria-chatbot'); ?>
                    </button>
                    <button type="button" class="aria-tab-button" data-tab="prompts">
                        <span class="dashicons dashicons-edit"></span>
                        <?php _e('پرامپت‌ها', 'aria-chatbot'); ?>
                    </button>
                    <button type="button" class="aria-tab-button" data-tab="advanced">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <?php _e('پیشرفته', 'aria-chatbot'); ?>
                    </button>
                </nav>

                <!-- تب اتصال API -->
                <div class="aria-tab-content active" id="connection">
                    <div class="aria-settings-section">
                        <h3><?php _e('تنظیمات اتصال OpenAI', 'aria-chatbot'); ?></h3>
                        
                        <div class="aria-form-group">
                            <label for="openai_api_key" class="aria-label">
                                <?php _e('کلید API OpenAI', 'aria-chatbot'); ?>
                                <span class="aria-required">*</span>
                            </label>
                            <div class="aria-input-group">
                                <input type="password" 
                                       id="openai_api_key" 
                                       name="aria_chatbot_options[openai_api_key]" 
                                       value="<?php echo esc_attr($current_settings['openai_api_key'] ?? ''); ?>"
                                       class="aria-input"
                                       placeholder="sk-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                                <button type="button" class="aria-btn aria-btn-secondary" id="toggle-api-key">
                                    <span class="dashicons dashicons-visibility"></span>
                                </button>
                                <button type="button" class="aria-btn aria-btn-primary" id="test-api-connection">
                                    <?php _e('تست اتصال', 'aria-chatbot'); ?>
                                </button>
                            </div>
                            <p class="aria-help-text">
                                <?php _e('کلید API خود را از', 'aria-chatbot'); ?> 
                                <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Dashboard</a> 
                                <?php _e('دریافت کنید', 'aria-chatbot'); ?>
                            </p>
                            <div id="api-connection-status" class="aria-status-message" style="display: none;"></div>
                        </div>

                        <div class="aria-form-group">
                            <label for="backup_api_keys" class="aria-label">
                                <?php _e('کلیدهای پشتیبان', 'aria-chatbot'); ?>
                            </label>
                            <div id="backup-keys-container">
                                <?php 
                                $backup_keys = $current_settings['backup_api_keys'] ?? array();
                                foreach ($backup_keys as $index => $key): 
                                ?>
                                <div class="aria-backup-key-item">
                                    <input type="password" 
                                           name="aria_chatbot_options[backup_api_keys][]" 
                                           value="<?php echo esc_attr($key); ?>"
                                           class="aria-input"
                                           placeholder="کلید پشتیبان">
                                    <button type="button" class="aria-btn-icon remove-backup-key">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" id="add-backup-key" class="aria-btn aria-btn-small">
                                <span class="dashicons dashicons-plus-alt"></span>
                                <?php _e('افزودن کلید پشتیبان', 'aria-chatbot'); ?>
                            </button>
                            <p class="aria-help-text">
                                <?php _e('در صورت خطا در کلید اصلی، از کلیدهای پشتیبان استفاده می‌شود', 'aria-chatbot'); ?>
                            </p>
                        </div>

                        <div class="aria-form-group">
                            <label for="api_organization" class="aria-label">
                                <?php _e('شناسه سازمان (اختیاری)', 'aria-chatbot'); ?>
                            </label>
                            <input type="text" 
                                   id="api_organization" 
                                   name="aria_chatbot_options[api_organization]" 
                                   value="<?php echo esc_attr($current_settings['api_organization'] ?? ''); ?>"
                                   class="aria-input"
                                   placeholder="org-xxxxxxxxxxxxxxxxxxxxxxxx">
                            <p class="aria-help-text">
                                <?php _e('برای حساب‌های سازمانی OpenAI', 'aria-chatbot'); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- تب مدل‌ها -->
                <div class="aria-tab-content" id="models">
                    <div class="aria-settings-section">
                        <h3><?php _e('انتخاب و پیکربندی مدل', 'aria-chatbot'); ?></h3>
                        
                        <div class="aria-form-group">
                            <label for="openai_model" class="aria-label">
                                <?php _e('مدل اصلی', 'aria-chatbot'); ?>
                            </label>
                            <select id="openai_model" name="aria_chatbot_options[openai_model]" class="aria-select">
                                <?php foreach ($models as $model_id => $model_info): ?>
                                <option value="<?php echo esc_attr($model_id); ?>" 
                                        <?php selected($current_settings['openai_model'] ?? 'gpt-4.1', $model_id); ?>
                                        data-description="<?php echo esc_attr($model_info['description']); ?>"
                                        data-max-tokens="<?php echo esc_attr($model_info['max_tokens']); ?>"
                                        data-cost="<?php echo esc_attr($model_info['cost_per_token']); ?>">
                                    <?php echo esc_html($model_info['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="model-info-display" class="aria-model-info">
                                <!-- اطلاعات مدل انتخابی اینجا نمایش داده می‌شود -->
                            </div>
                        </div>

                        <div class="aria-form-group">
                            <label for="fallback_model" class="aria-label">
                                <?php _e('مدل پشتیبان', 'aria-chatbot'); ?>
                            </label>
                            <select id="fallback_model" name="aria_chatbot_options[fallback_model]" class="aria-select">
                                <option value=""><?php _e('بدون مدل پشتیبان', 'aria-chatbot'); ?></option>
                                <?php foreach ($models as $model_id => $model_info): ?>
                                <option value="<?php echo esc_attr($model_id); ?>" 
                                        <?php selected($current_settings['fallback_model'] ?? '', $model_id); ?>>
                                    <?php echo esc_html($model_info['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="aria-help-text">
                                <?php _e('در صورت عدم دسترسی به مدل اصلی، از این مدل استفاده می‌شود', 'aria-chatbot'); ?>
                            </p>
                        </div>

                        <div class="aria-form-group">
                            <label class="aria-label">
                                <?php _e('مدل‌های تخصصی', 'aria-chatbot'); ?>
                            </label>
                            <div class="aria-specialized-models">
                                <div class="aria-model-assignment">
                                    <label><?php _e('تحلیل تصاویر:', 'aria-chatbot'); ?></label>
                                    <select name="aria_chatbot_options[image_analysis_model]" class="aria-select">
                                        <option value=""><?php _e('استفاده از مدل اصلی', 'aria-chatbot'); ?></option>
                                        <option value="gpt-4o" <?php selected($current_settings['image_analysis_model'] ?? '', 'gpt-4o'); ?>>GPT-4O</option>
                                        <option value="gpt-4o-mini" <?php selected($current_settings['image_analysis_model'] ?? '', 'gpt-4o-mini'); ?>>GPT-4O Mini</option>
                                    </select>
                                </div>
                                
                                <div class="aria-model-assignment">
                                    <label><?php _e('استدلال پیچیده:', 'aria-chatbot'); ?></label>
                                    <select name="aria_chatbot_options[reasoning_model]" class="aria-select">
                                        <option value=""><?php _e('استفاده از مدل اصلی', 'aria-chatbot'); ?></option>
                                        <option value="o3" <?php selected($current_settings['reasoning_model'] ?? '', 'o3'); ?>>O3</option>
                                        <option value="o3-pro" <?php selected($current_settings['reasoning_model'] ?? '', 'o3-pro'); ?>>O3 Pro</option>
                                    </select>
                                </div>
                                
                                <div class="aria-model-assignment">
                                    <label><?php _e('پاسخ‌های سریع:', 'aria-chatbot'); ?></label>
                                    <select name="aria_chatbot_options[quick_response_model]" class="aria-select">
                                        <option value=""><?php _e('استفاده از مدل اصلی', 'aria-chatbot'); ?></option>
                                        <option value="gpt-4.1-nano" <?php selected($current_settings['quick_response_model'] ?? '', 'gpt-4.1-nano'); ?>>GPT-4.1 Nano</option>
                                        <option value="o4-mini" <?php selected($current_settings['quick_response_model'] ?? '', 'o4-mini'); ?>>O4 Mini</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- تب پارامترها -->
                <div class="aria-tab-content" id="parameters">
                    <div class="aria-settings-section">
                        <h3><?php _e('پارامترهای تولید متن', 'aria-chatbot'); ?></h3>
                        
                        <div class="aria-form-group">
                            <label for="max_tokens" class="aria-label">
                                <?php _e('حداکثر توکن‌ها', 'aria-chatbot'); ?>
                            </label>
                            <div class="aria-range-input">
                                <input type="range" 
                                       id="max_tokens_range" 
                                       min="100" 
                                       max="8192" 
                                       value="<?php echo esc_attr($current_settings['max_tokens'] ?? 1000); ?>"
                                       class="aria-range">
                                <input type="number" 
                                       id="max_tokens" 
                                       name="aria_chatbot_options[max_tokens]" 
                                       min="100" 
                                       max="8192" 
                                       value="<?php echo esc_attr($current_settings['max_tokens'] ?? 1000); ?>"
                                       class="aria-input aria-input-number">
                            </div>
                            <p class="aria-help-text">
                                <?php _e('طولانی‌تر = پاسخ‌های تفصیلی‌تر، کوتاه‌تر = پاسخ‌های مختصر و سریع‌تر', 'aria-chatbot'); ?>
                            </p>
                        </div>

                        <div class="aria-form-group">
                            <label for="temperature" class="aria-label">
                                <?php _e('دمای خلاقیت (Temperature)', 'aria-chatbot'); ?>
                            </label>
                            <div class="aria-range-input">
                                <input type="range" 
                                       id="temperature_range" 
                                       min="0" 
                                       max="2" 
                                       step="0.1" 
                                       value="<?php echo esc_attr($current_settings['temperature'] ?? 0.7); ?>"
                                       class="aria-range">
                                <input type="number" 
                                       id="temperature" 
                                       name="aria_chatbot_options[temperature]" 
                                       min="0" 
                                       max="2" 
                                       step="0.1" 
                                       value="<?php echo esc_attr($current_settings['temperature'] ?? 0.7); ?>"
                                       class="aria-input aria-input-number">
                            </div>
                            <div class="aria-temperature-guide">
                                <span class="aria-temp-label" data-range="0-0.3"><?php _e('محافظه‌کار', 'aria-chatbot'); ?></span>
                                <span class="aria-temp-label" data-range="0.4-0.7"><?php _e('متعادل', 'aria-chatbot'); ?></span>
                                <span class="aria-temp-label" data-range="0.8-1.2"><?php _e('خلاق', 'aria-chatbot'); ?></span>
                                <span class="aria-temp-label" data-range="1.3-2.0"><?php _e('بسیار خلاق', 'aria-chatbot'); ?></span>
                            </div>
                        </div>

                        <div class="aria-form-group">
                            <label for="top_p" class="aria-label">
                                <?php _e('تنوع پاسخ (Top P)', 'aria-chatbot'); ?>
                            </label>
                            <div class="aria-range-input">
                                <input type="range" 
                                       id="top_p_range" 
                                       min="0.1" 
                                       max="1" 
                                       step="0.1" 
                                       value="<?php echo esc_attr($current_settings['top_p'] ?? 1); ?>"
                                       class="aria-range">
                                <input type="number" 
                                       id="top_p" 
                                       name="aria_chatbot_options[top_p]" 
                                       min="0.1" 
                                       max="1" 
                                       step="0.1" 
                                       value="<?php echo esc_attr($current_settings['top_p'] ?? 1); ?>"
                                       class="aria-input aria-input-number">
                            </div>
                            <p class="aria-help-text">
                                <?php _e('کنترل تنوع پاسخ‌ها. مقدار پایین‌تر = پاسخ‌های مشابه‌تر', 'aria-chatbot'); ?>
                            </p>
                        </div>

                        <div class="aria-form-group">
                            <label for="frequency_penalty" class="aria-label">
                                <?php _e('جریمه تکرار (Frequency Penalty)', 'aria-chatbot'); ?>
                            </label>
                            <div class="aria-range-input">
                                <input type="range" 
                                       id="frequency_penalty_range" 
                                       min="-2" 
                                       max="2" 
                                       step="0.1" 
                                       value="<?php echo esc_attr($current_settings['frequency_penalty'] ?? 0); ?>"
                                       class="aria-range">
                                <input type="number" 
                                       id="frequency_penalty" 
                                       name="aria_chatbot_options[frequency_penalty]" 
                                       min="-2" 
                                       max="2" 
                                       step="0.1" 
                                       value="<?php echo esc_attr($current_settings['frequency_penalty'] ?? 0); ?>"
                                       class="aria-input aria-input-number">
                            </div>
                            <p class="aria-help-text">
                                <?php _e('کاهش احتمال تکرار کلمات. مقدار مثبت = کمتر تکرار', 'aria-chatbot'); ?>
                            </p>
                        </div>

                        <div class="aria-form-group">
                            <label for="presence_penalty" class="aria-label">
                                <?php _e('جریمه حضور (Presence Penalty)', 'aria-chatbot'); ?>
                            </label>
                            <div class="aria-range-input">
                                <input type="range" 
                                       id="presence_penalty_range" 
                                       min="-2" 
                                       max="2" 
                                       step="0.1" 
                                       value="<?php echo esc_attr($current_settings['presence_penalty'] ?? 0); ?>"
                                       class="aria-range">
                                <input type="number" 
                                       id="presence_penalty" 
                                       name="aria_chatbot_options[presence_penalty]" 
                                       min="-2" 
                                       max="2" 
                                       step="0.1" 
                                       value="<?php echo esc_attr($current_settings['presence_penalty'] ?? 0); ?>"
                                       class="aria-input aria-input-number">
                            </div>
                            <p class="aria-help-text">
                                <?php _e('تشویق به صحبت در موضوعات جدید. مقدار مثبت = موضوعات متنوع‌تر', 'aria-chatbot'); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- تب پرامپت‌ها -->
                <div class="aria-tab-content" id="prompts">
                    <div class="aria-settings-section">
                        <h3><?php _e('مدیریت پرامپت‌های سیستم', 'aria-chatbot'); ?></h3>
                        
                        <div class="aria-form-group">
                            <label for="system_prompt" class="aria-label">
                                <?php _e('پرامپت سیستم اصلی', 'aria-chatbot'); ?>
                            </label>
                            <textarea id="system_prompt" 
                                      name="aria_chatbot_options[system_prompt]" 
                                      rows="8" 
                                      class="aria-textarea"
                                      placeholder="شما یک دستیار هوشمند و مفید هستید..."><?php echo esc_textarea($current_settings['system_prompt'] ?? ''); ?></textarea>
                            <p class="aria-help-text">
                                <?php _e('این پرامپت رفتار کلی چت بات را تعیین می‌کند', 'aria-chatbot'); ?>
                            </p>
                        </div>

                        <div class="aria-form-group">
                            <label class="aria-label">
                                <?php _e('پرامپت‌های تخصصی', 'aria-chatbot'); ?>
                            </label>
                            <div class="aria-specialized-prompts">
                                <div class="aria-prompt-item">
                                    <label><?php _e('پرامپت فروش:', 'aria-chatbot'); ?></label>
                                    <textarea name="aria_chatbot_options[sales_prompt]" 
                                              rows="3" 
                                              class="aria-textarea"
                                              placeholder="برای درخواست‌های مربوط به خرید و فروش..."><?php echo esc_textarea($current_settings['sales_prompt'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="aria-prompt-item">
                                    <label><?php _e('پرامپت پشتیبانی:', 'aria-chatbot'); ?></label>
                                    <textarea name="aria_chatbot_options[support_prompt]" 
                                              rows="3" 
                                              class="aria-textarea"
                                              placeholder="برای حل مشکلات و پشتیبانی فنی..."><?php echo esc_textarea($current_settings['support_prompt'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="aria-prompt-item">
                                    <label><?php _e('پرامپت اطلاعات عمومی:', 'aria-chatbot'); ?></label>
                                    <textarea name="aria_chatbot_options[info_prompt]" 
                                              rows="3" 
                                              class="aria-textarea"
                                              placeholder="برای ارائه اطلاعات عمومی..."><?php echo esc_textarea($current_settings['info_prompt'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="aria-form-group">
                            <label class="aria-label">
                                <?php _e('قوانین و محدودیت‌ها', 'aria-chatbot'); ?>
                            </label>
                            <div class="aria-rules-container">
                                <div class="aria-rule-item">
                                    <input type="checkbox" 
                                           id="no_harmful_content" 
                                           name="aria_chatbot_options[content_rules][no_harmful]" 
                                           value="1" 
                                           <?php checked($current_settings['content_rules']['no_harmful'] ?? 1, 1); ?>>
                                    <label for="no_harmful_content"><?php _e('ممنوعیت محتوای مضر', 'aria-chatbot'); ?></label>
                                </div>
                                
                                <div class="aria-rule-item">
                                    <input type="checkbox" 
                                           id="stay_on_topic" 
                                           name="aria_chatbot_options[content_rules][stay_on_topic]" 
                                           value="1" 
                                           <?php checked($current_settings['content_rules']['stay_on_topic'] ?? 1, 1); ?>>
                                    <label for="stay_on_topic"><?php _e('تمرکز بر موضوعات مرتبط', 'aria-chatbot'); ?></label>
                                </div>
                                
                                <div class="aria-rule-item">
                                    <input type="checkbox" 
                                           id="professional_tone" 
                                           name="aria_chatbot_options[content_rules][professional_tone]" 
                                           value="1" 
                                           <?php checked($current_settings['content_rules']['professional_tone'] ?? 1, 1); ?>>
                                    <label for="professional_tone"><?php _e('حفظ لحن حرفه‌ای', 'aria-chatbot'); ?></label>
                                </div>
                                
                                <div class="aria-rule-item">
                                    <input type="checkbox" 
                                           id="no_personal_info" 
                                           name="aria_chatbot_options[content_rules][no_personal_info]" 
                                           value="1" 
                                           <?php checked($current_settings['content_rules']['no_personal_info'] ?? 1, 1); ?>>
                                    <label for="no_personal_info"><?php _e('عدم درخواست اطلاعات شخصی', 'aria-chatbot'); ?></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- تب پیشرفته -->
                <div class="aria-tab-content" id="advanced">
                    <div class="aria-settings-section">
                        <h3><?php _e('تنظیمات پیشرفته', 'aria-chatbot'); ?></h3>
                        
                        <div class="aria-form-group">
                            <label for="context_window" class="aria-label">
                                <?php _e('اندازه پنجره زمینه', 'aria-chatbot'); ?>
                            </label>
                            <select id="context_window" name="aria_chatbot_options[context_window]" class="aria-select">
                                <option value="5" <?php selected($current_settings['context_window'] ?? 10, 5); ?>>
                                    <?php _e('5 پیام قبلی', 'aria-chatbot'); ?>
                                </option>
                                <option value="10" <?php selected($current_settings['context_window'] ?? 10, 10); ?>>
                                    <?php _e('10 پیام قبلی', 'aria-chatbot'); ?>
                                </option>
                                <option value="20" <?php selected($current_settings['context_window'] ?? 10, 20); ?>>
                                    <?php _e('20 پیام قبلی', 'aria-chatbot'); ?>
                                </option>
                                <option value="50" <?php selected($current_settings['context_window'] ?? 10, 50); ?>>
                                    <?php _e('50 پیام قبلی', 'aria-chatbot'); ?>
                                </option>
                            </select>
                            <p class="aria-help-text">
                                <?php _e('تعداد پیام‌های قبلی که در نظر گرفته می‌شود', 'aria-chatbot'); ?>
                            </p>
                        </div>

                        <div class="aria-form-group">
                            <label for="response_timeout" class="aria-label">
                                <?php _e('مهلت زمانی پاسخ (ثانیه)', 'aria-chatbot'); ?>
                            </label>
                            <input type="number" 
                                   id="response_timeout" 
                                   name="aria_chatbot_options[response_timeout]" 
                                   min="10" 
                                   max="120" 
                                   value="<?php echo esc_attr($current_settings['response_timeout'] ?? 30); ?>"
                                   class="aria-input">
                            <p class="aria-help-text">
                                <?php _e('حداکثر زمان انتظار برای دریافت پاسخ از API', 'aria-chatbot'); ?>
                            </p>
                        </div>

                        <div class="aria-form-group">
                            <label for="retry_attempts" class="aria-label">
                                <?php _e('تعداد تلاش مجدد', 'aria-chatbot'); ?>
                            </label>
                            <input type="number" 
                                   id="retry_attempts" 
                                   name="aria_chatbot_options[retry_attempts]" 
                                   min="0" 
                                   max="5" 
                                   value="<?php echo esc_attr($current_settings['retry_attempts'] ?? 2); ?>"
                                   class="aria-input">
                            <p class="aria-help-text">
                                <?php _e('تعداد تلاش‌های مجدد در صورت خطا', 'aria-chatbot'); ?>
                            </p>
                        </div>

                        <div class="aria-form-group">
                            <label class="aria-label"><?php _e('کش کردن پاسخ‌ها', 'aria-chatbot'); ?></label>
                            <div class="aria-toggle-group">
                                <input type="checkbox" 
                                       id="enable_caching" 
                                       name="aria_chatbot_options[enable_caching]" 
                                       value="1" 
                                       <?php checked($current_settings['enable_caching'] ?? 1, 1); ?>>
                                <label for="enable_caching" class="aria-toggle-label">
                                    <?php _e('فعال‌سازی کش', 'aria-chatbot'); ?>
                                </label>
                            </div>
                            <div class="aria-cache-settings" <?php echo !($current_settings['enable_caching'] ?? 1) ? 'style="display:none;"' : ''; ?>>
                                <label for="cache_duration"><?php _e('مدت زمان کش (ساعت):', 'aria-chatbot'); ?></label>
                                <input type="number" 
                                       id="cache_duration" 
                                       name="aria_chatbot_options[cache_duration]" 
                                       min="1" 
                                       max="168" 
                                       value="<?php echo esc_attr($current_settings['cache_duration'] ?? 24); ?>"
                                       class="aria-input aria-input-small">
                            </div>
                        </div>

                        <div class="aria-form-group">
                            <label class="aria-label"><?php _e('رجیستری خطاها', 'aria-chatbot'); ?></label>
                            <div class="aria-toggle-group">
                                <input type="checkbox" 
                                       id="enable_error_logging" 
                                       name="aria_chatbot_options[enable_error_logging]" 
                                       value="1" 
                                       <?php checked($current_settings['enable_error_logging'] ?? 1, 1); ?>>
                                <label for="enable_error_logging" class="aria-toggle-label">
                                    <?php _e('ثبت خطاها', 'aria-chatbot'); ?>
                                </label>
                            </div>
                            <div class="aria-toggle-group">
                                <input type="checkbox" 
                                       id="enable_debug_mode" 
                                       name="aria_chatbot_options[enable_debug_mode]" 
                                       value="1" 
                                       <?php checked($current_settings['enable_debug_mode'] ?? 0, 1); ?>>
                                <label for="enable_debug_mode" class="aria-toggle-label">
                                    <?php _e('حالت دیباگ', 'aria-chatbot'); ?>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- دکمه‌های عمل -->
            <div class="aria-form-actions">
                <button type="submit" class="aria-btn aria-btn-primary aria-btn-large">
                    <span class="dashicons dashicons-yes"></span>
                    <?php _e('ذخیره تنظیمات', 'aria-chatbot'); ?>
                </button>
                
                <button type="button" class="aria-btn aria-btn-secondary" id="reset-to-defaults">
                    <span class="dashicons dashicons-undo"></span>
                    <?php _e('بازگشت به پیش‌فرض', 'aria-chatbot'); ?>
                </button>
                
                <button type="button" class="aria-btn aria-btn-secondary" id="test-current-settings">
                    <span class="dashicons dashicons-admin-tools"></span>
                    <?php _e('تست تنظیمات فعلی', 'aria-chatbot'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- مودال تست تنظیمات -->
<div id="aria-test-modal" class="aria-modal" style="display: none;">
    <div class="aria-modal-content">
        <div class="aria-modal-header">
            <h3><?php _e('تست تنظیمات AI', 'aria-chatbot'); ?></h3>
            <button class="aria-modal-close" onclick="closeTestModal()">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="aria-modal-body">
            <div class="aria-test-chat">
                <div class="aria-test-messages" id="test-messages">
                    <div class="aria-test-message bot">
                        <?php _e('سلام! من آریا هستم. برای تست تنظیمات، سؤالی بپرسید.', 'aria-chatbot'); ?>
                    </div>
                </div>
                <div class="aria-test-input">
                    <input type="text" id="test-message-input" placeholder="پیام تست خود را بنویسید...">
                    <button id="send-test-message" class="aria-btn aria-btn-primary">
                        <?php _e('ارسال', 'aria-chatbot'); ?>
                    </button>
                </div>
            </div>
            <div class="aria-test-info">
                <h4><?php _e('اطلاعات آخرین درخواست:', 'aria-chatbot'); ?></h4>
                <div id="test-request-info">
                    <!-- اطلاعات درخواست اینجا نمایش داده می‌شود -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // مدیریت تب‌ها
    $('.aria-tab-button').click(function() {
        const targetTab = $(this).data('tab');
        
        $('.aria-tab-button').removeClass('active');
        $('.aria-tab-content').removeClass('active');
        
        $(this).addClass('active');
        $('#' + targetTab).addClass('active');
    });
    
    // همگام‌سازی range و number inputs
    $('.aria-range').on('input', function() {
        const targetId = $(this).attr('id').replace('_range', '');
        $('#' + targetId).val($(this).val());
        updateTemperatureGuide();
    });
    
    $('.aria-input-number').on('input', function() {
        const rangeId = $(this).attr('id') + '_range';
        $('#' + rangeId).val($(this).val());
        updateTemperatureGuide();
    });
    
    // راهنمای دما
    function updateTemperatureGuide() {
        const temp = parseFloat($('#temperature').val());
        $('.aria-temp-label').removeClass('active');
        
        if (temp <= 0.3) {
            $('.aria-temp-label[data-range="0-0.3"]').addClass('active');
        } else if (temp <= 0.7) {
            $('.aria-temp-label[data-range="0.4-0.7"]').addClass('active');
        } else if (temp <= 1.2) {
            $('.aria-temp-label[data-range="0.8-1.2"]').addClass('active');
        } else {
            $('.aria-temp-label[data-range="1.3-2.0"]').addClass('active');
        }
    }
    
    // نمایش/پنهان کردن API key
    $('#toggle-api-key').click(function() {
        const input = $('#openai_api_key');
        const icon = $(this).find('.dashicons');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
        } else {
            input.attr('type', 'password');
            icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
        }
    });
    
    // تست اتصال API
    $('#test-api-connection').click(function() {
        const button = $(this);
        const apiKey = $('#openai_api_key').val();
        const model = $('#openai_model').val();
        
        if (!apiKey) {
            showNotification('لطفاً کلید API را وارد کنید', 'error');
            return;
        }
        
        button.prop('disabled', true).text('در حال تست...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aria_test_api',
                api_key: apiKey,
                model: model,
                nonce: ariaAdmin.nonce
            },
            success: function(response) {
                const statusDiv = $('#api-connection-status');
                statusDiv.show();
                
                if (response.success) {
                    statusDiv.html('<span class="success">✓ اتصال موفق</span>');
                    showNotification('اتصال به API موفق بود', 'success');
                } else {
                    statusDiv.html('<span class="error">✗ خطا: ' + response.data + '</span>');
                    showNotification('خطا در اتصال: ' + response.data, 'error');
                }
            },
            error: function() {
                $('#api-connection-status').show().html('<span class="error">✗ خطا در برقراری ارتباط</span>');
                showNotification('خطا در برقراری ارتباط', 'error');
            },
            complete: function() {
                button.prop('disabled', false).text('تست اتصال');
            }
        });
    });
    
    // نمایش اطلاعات مدل
    $('#openai_model').change(function() {
        updateModelInfo();
    });
    
    function updateModelInfo() {
        const selectedOption = $('#openai_model option:selected');
        const description = selectedOption.data('description');
        const maxTokens = selectedOption.data('max-tokens');
        const cost = selectedOption.data('cost');
        
        const infoHtml = `
            <div class="aria-model-details">
                <p><strong>توضیحات:</strong> ${description}</p>
                <p><strong>حداکثر توکن:</strong> ${maxTokens.toLocaleString()}</p>
                <p><strong>هزینه هر توکن:</strong> $${cost}</p>
            </div>
        `;
        
        $('#model-info-display').html(infoHtml);
    }
    
    // مدیریت کلیدهای پشتیبان
    $('#add-backup-key').click(function() {
        const newKeyHtml = `
            <div class="aria-backup-key-item">
                <input type="password" 
                       name="aria_chatbot_options[backup_api_keys][]" 
                       value=""
                       class="aria-input"
                       placeholder="کلید پشتیبان">
                <button type="button" class="aria-btn-icon remove-backup-key">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>
        `;
        $('#backup-keys-container').append(newKeyHtml);
    });
    
    $(document).on('click', '.remove-backup-key', function() {
        $(this).closest('.aria-backup-key-item').remove();
    });
    
    // کش کردن
    $('#enable_caching').change(function() {
        if ($(this).is(':checked')) {
            $('.aria-cache-settings').show();
        } else {
            $('.aria-cache-settings').hide();
        }
    });
    
    // تست تنظیمات فعلی
    $('#test-current-settings').click(function() {
        $('#aria-test-modal').show();
    });
    
    // ارسال پیام تست
    $('#send-test-message').click(function() {
        sendTestMessage();
    });
    
    $('#test-message-input').keypress(function(e) {
        if (e.which === 13) {
            sendTestMessage();
        }
    });
    
    function sendTestMessage() {
        const message = $('#test-message-input').val().trim();
        if (!message) return;
        
        // اضافه کردن پیام کاربر
        $('#test-messages').append(`
            <div class="aria-test-message user">${message}</div>
        `);
        
        $('#test-message-input').val('');
        
        // نمایش حالت تایپ
        $('#test-messages').append(`
            <div class="aria-test-message bot typing">در حال تایپ...</div>
        `);
        
        // ارسال درخواست
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aria_test_message',
                message: message,
                nonce: ariaAdmin.nonce
            },
            success: function(response) {
                $('.typing').remove();
                
                if (response.success) {
                    $('#test-messages').append(`
                        <div class="aria-test-message bot">${response.data.response}</div>
                    `);
                    
                    // نمایش اطلاعات درخواست
                    $('#test-request-info').html(`
                        <p><strong>مدل استفاده شده:</strong> ${response.data.model}</p>
                        <p><strong>توکن‌های مصرفی:</strong> ${response.data.tokens_used}</p>
                        <p><strong>زمان پاسخ:</strong> ${response.data.response_time}ms</p>
                        <p><strong>هزینه تقریبی:</strong> $${response.data.estimated_cost}</p>
                    `);
                } else {
                    $('#test-messages').append(`
                        <div class="aria-test-message error">خطا: ${response.data}</div>
                    `);
                }
                
                // اسکرول به پایین
                const messagesContainer = $('#test-messages');
                messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
            }
        });
    }
    
    // بازگشت به پیش‌فرض
    $('#reset-to-defaults').click(function() {
        if (confirm('آیا مطمئن هستید؟ تمام تنظیمات به حالت پیش‌فرض بازخواهد گشت.')) {
            resetToDefaults();
        }
    });
    
    function resetToDefaults() {
        // بازگشت تمام فیلدها به مقادیر پیش‌فرض
        $('#openai_model').val('gpt-4.1');
        $('#max_tokens').val(1000);
        $('#temperature').val(0.7);
        $('#top_p').val(1);
        $('#frequency_penalty').val(0);
        $('#presence_penalty').val(0);
        
        // به‌روزرسانی range ها
        $('#max_tokens_range').val(1000);
        $('#temperature_range').val(0.7);
        $('#top_p_range').val(1);
        $('#frequency_penalty_range').val(0);
        $('#presence_penalty_range').val(0);
        
        updateModelInfo();
        updateTemperatureGuide();
        
        showNotification('تنظیمات به حالت پیش‌فرض بازگشت', 'info');
    }
    
    // راه اندازی اولیه
    updateModelInfo();
    updateTemperatureGuide();
});

function closeTestModal() {
    jQuery('#aria-test-modal').hide();
}

function showNotification(message, type = 'info') {
    const notification = jQuery('<div class="aria-notification aria-notification-' + type + '">' + message + '</div>');
    jQuery('body').append(notification);
    
    setTimeout(function() {
        notification.fadeOut(function() {
            jQuery(this).remove();
        });
    }, 3000);
}
</script>
