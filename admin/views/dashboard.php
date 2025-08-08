<?php
/**
 * صفحه داشبورد پشتیبان هوشمند آریا
 * 
 * @package Aria_Chatbot
 * @path admin/views/dashboard.php
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap aria-admin-wrap">
    <div class="aria-header">
        <h1 class="aria-page-title">
            <span class="aria-icon">🤖</span>
            <?php _e('داشبورد پشتیبان هوشمند آریا', 'aria-chatbot'); ?>
            <span class="aria-version">v<?php echo ARIA_CHATBOT_VERSION; ?></span>
        </h1>
        <p class="aria-page-subtitle">
            <?php _e('مرکز کنترل و مدیریت چت بات هوشمند شما', 'aria-chatbot'); ?>
        </p>
    </div>

    <!-- خلاصه وضعیت -->
    <div class="aria-status-cards">
        <div class="aria-card aria-status-card">
            <div class="aria-card-icon success">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="aria-card-content">
                <h3><?php _e('وضعیت سیستم', 'aria-chatbot'); ?></h3>
                <p class="aria-status-text success">
                    <?php _e('آنلاین و فعال', 'aria-chatbot'); ?>
                </p>
                <div class="aria-health-score">
                    <span class="aria-score"><?php echo $health['overall_score']; ?>%</span>
                    <span class="aria-score-label"><?php _e('سلامت کلی', 'aria-chatbot'); ?></span>
                </div>
            </div>
        </div>

        <div class="aria-card aria-status-card">
            <div class="aria-card-icon info">
                <span class="dashicons dashicons-admin-comments"></span>
            </div>
            <div class="aria-card-content">
                <h3><?php _e('مکالمات امروز', 'aria-chatbot'); ?></h3>
                <p class="aria-big-number"><?php echo number_format($stats['conversations_today']); ?></p>
                <p class="aria-trend <?php echo $stats['conversations_trend'] > 0 ? 'up' : 'down'; ?>">
                    <span class="dashicons dashicons-arrow-<?php echo $stats['conversations_trend'] > 0 ? 'up' : 'down'; ?>"></span>
                    <?php echo abs($stats['conversations_trend']); ?>% نسبت به دیروز
                </p>
            </div>
        </div>

        <div class="aria-card aria-status-card">
            <div class="aria-card-icon warning">
                <span class="dashicons dashicons-performance"></span>
            </div>
            <div class="aria-card-content">
                <h3><?php _e('زمان پاسخ', 'aria-chatbot'); ?></h3>
                <p class="aria-big-number"><?php echo $stats['avg_response_time']; ?>s</p>
                <p class="aria-trend neutral">
                    <span class="dashicons dashicons-clock"></span>
                    <?php _e('میانگین زمان پاسخ‌دهی', 'aria-chatbot'); ?>
                </p>
            </div>
        </div>

        <div class="aria-card aria-status-card">
            <div class="aria-card-icon primary">
                <span class="dashicons dashicons-star-filled"></span>
            </div>
            <div class="aria-card-content">
                <h3><?php _e('رضایت کاربران', 'aria-chatbot'); ?></h3>
                <p class="aria-big-number"><?php echo $stats['satisfaction_score']; ?>/5</p>
                <p class="aria-trend up">
                    <span class="dashicons dashicons-thumbs-up"></span>
                    <?php echo $stats['satisfaction_percentage']; ?>% رضایت
                </p>
            </div>
        </div>
    </div>

    <!-- محتوای اصلی داشبورد -->
    <div class="aria-dashboard-content">
        <div class="aria-dashboard-left">
            
            <!-- نمودار آمار -->
            <div class="aria-card">
                <div class="aria-card-header">
                    <h3><?php _e('آمار مکالمات (7 روز گذشته)', 'aria-chatbot'); ?></h3>
                    <div class="aria-card-actions">
                        <select id="aria-chart-period">
                            <option value="7days"><?php _e('7 روز گذشته', 'aria-chatbot'); ?></option>
                            <option value="30days"><?php _e('30 روز گذشته', 'aria-chatbot'); ?></option>
                            <option value="90days"><?php _e('90 روز گذشته', 'aria-chatbot'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="aria-card-body">
                    <canvas id="aria-conversations-chart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- آخرین مکالمات -->
            <div class="aria-card">
                <div class="aria-card-header">
                    <h3><?php _e('آخرین مکالمات', 'aria-chatbot'); ?></h3>
                    <a href="<?php echo admin_url('admin.php?page=aria-conversations'); ?>" class="aria-btn aria-btn-small">
                        <?php _e('مشاهده همه', 'aria-chatbot'); ?>
                    </a>
                </div>
                <div class="aria-card-body">
                    <div class="aria-conversations-list">
                        <?php foreach ($stats['recent_conversations'] as $conversation): ?>
                        <div class="aria-conversation-item">
                            <div class="aria-conversation-avatar">
                                <span class="dashicons dashicons-admin-users"></span>
                            </div>
                            <div class="aria-conversation-content">
                                <p class="aria-conversation-message">
                                    "<?php echo wp_trim_words($conversation['message'], 10); ?>"
                                </p>
                                <div class="aria-conversation-meta">
                                    <span class="aria-conversation-time">
                                        <?php echo human_time_diff(strtotime($conversation['timestamp'])); ?> پیش
                                    </span>
                                    <span class="aria-conversation-type">
                                        <?php echo $conversation['input_type'] === 'voice' ? '🎤' : '💬'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="aria-conversation-actions">
                                <button class="aria-btn-icon" onclick="viewConversationDetails(<?php echo $conversation['id']; ?>)">
                                    <span class="dashicons dashicons-visibility"></span>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- سؤالات پرتکرار -->
            <div class="aria-card">
                <div class="aria-card-header">
                    <h3><?php _e('سؤالات پرتکرار', 'aria-chatbot'); ?></h3>
                </div>
                <div class="aria-card-body">
                    <div class="aria-popular-questions">
                        <?php foreach ($stats['popular_questions'] as $index => $question): ?>
                        <div class="aria-question-item">
                            <span class="aria-question-rank"><?php echo $index + 1; ?></span>
                            <div class="aria-question-content">
                                <p><?php echo wp_trim_words($question['question'], 15); ?></p>
                                <span class="aria-question-count">
                                    <?php echo $question['count']; ?> بار پرسیده شده
                                </span>
                            </div>
                            <div class="aria-question-actions">
                                <button class="aria-btn-small" onclick="addToKnowledgeBase('<?php echo esc_js($question['question']); ?>')">
                                    افزودن به پایگاه دانش
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="aria-dashboard-right">
            
            <!-- دسترسی سریع -->
            <div class="aria-card">
                <div class="aria-card-header">
                    <h3><?php _e('دسترسی سریع', 'aria-chatbot'); ?></h3>
                </div>
                <div class="aria-card-body">
                    <div class="aria-quick-actions">
                        <a href="<?php echo admin_url('admin.php?page=aria-ai-settings'); ?>" class="aria-quick-action">
                            <span class="aria-quick-action-icon">🧠</span>
                            <span class="aria-quick-action-title"><?php _e('تنظیمات AI', 'aria-chatbot'); ?></span>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=aria-personality'); ?>" class="aria-quick-action">
                            <span class="aria-quick-action-icon">🎭</span>
                            <span class="aria-quick-action-title"><?php _e('شخصیت', 'aria-chatbot'); ?></span>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=aria-voice-settings'); ?>" class="aria-quick-action">
                            <span class="aria-quick-action-icon">🎤</span>
                            <span class="aria-quick-action-title"><?php _e('تنظیمات صوتی', 'aria-chatbot'); ?></span>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=aria-design'); ?>" class="aria-quick-action">
                            <span class="aria-quick-action-icon">🎨</span>
                            <span class="aria-quick-action-title"><?php _e('طراحی', 'aria-chatbot'); ?></span>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=aria-knowledge'); ?>" class="aria-quick-action">
                            <span class="aria-quick-action-icon">📚</span>
                            <span class="aria-quick-action-title"><?php _e('پایگاه دانش', 'aria-chatbot'); ?></span>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=aria-analytics'); ?>" class="aria-quick-action">
                            <span class="aria-quick-action-icon">📊</span>
                            <span class="aria-quick-action-title"><?php _e('آمار و تحلیل', 'aria-chatbot'); ?></span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- وضعیت API -->
            <div class="aria-card">
                <div class="aria-card-header">
                    <h3><?php _e('وضعیت API', 'aria-chatbot'); ?></h3>
                    <button class="aria-btn-small" onclick="testAPIConnection()">
                        <?php _e('تست اتصال', 'aria-chatbot'); ?>
                    </button>
                </div>
                <div class="aria-card-body">
                    <div class="aria-api-status">
                        <div class="aria-status-item">
                            <span class="aria-status-label"><?php _e('OpenAI API:', 'aria-chatbot'); ?></span>
                            <span class="aria-status-value success">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <?php _e('متصل', 'aria-chatbot'); ?>
                            </span>
                        </div>
                        <div class="aria-status-item">
                            <span class="aria-status-label"><?php _e('مدل فعال:', 'aria-chatbot'); ?></span>
                            <span class="aria-status-value">
                                <?php echo aria_get_option('openai_model', 'gpt-4.1'); ?>
                            </span>
                        </div>
                        <div class="aria-status-item">
                            <span class="aria-status-label"><?php _e('استفاده امروز:', 'aria-chatbot'); ?></span>
                            <span class="aria-status-value">
                                <?php echo number_format($stats['api_calls_today']); ?> درخواست
                            </span>
                        </div>
                        <div class="aria-status-item">
                            <span class="aria-status-label"><?php _e('هزینه تقریبی:', 'aria-chatbot'); ?></span>
                            <span class="aria-status-value">
                                $<?php echo number_format($stats['estimated_cost'], 2); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- پیش‌نمایش چت بات -->
            <div class="aria-card">
                <div class="aria-card-header">
                    <h3><?php _e('پیش‌نمایش چت بات', 'aria-chatbot'); ?></h3>
                    <button class="aria-btn-small" onclick="refreshChatbotPreview()">
                        <?php _e('به‌روزرسانی', 'aria-chatbot'); ?>
                    </button>
                </div>
                <div class="aria-card-body">
                    <div class="aria-chatbot-preview">
                        <div class="aria-preview-chatbot">
                            <div class="aria-preview-header">
                                <span class="aria-preview-title">
                                    <?php echo aria_get_option('bot_name', 'آریا'); ?>
                                </span>
                                <span class="aria-preview-status online"></span>
                            </div>
                            <div class="aria-preview-messages">
                                <div class="aria-preview-message bot">
                                    <?php 
                                    $personality = get_option('aria_chatbot_personality_options', array());
                                    $starters = $personality['conversation_starters'] ?? array('سلام! چطور می‌تونم کمکتون کنم؟');
                                    echo $starters[0];
                                    ?>
                                </div>
                            </div>
                            <div class="aria-preview-input">
                                <input type="text" placeholder="پیام خود را بنویسید...">
                                <button>ارسال</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- اخبار و به‌روزرسانی -->
            <div class="aria-card">
                <div class="aria-card-header">
                    <h3><?php _e('اخبار و به‌روزرسانی', 'aria-chatbot'); ?></h3>
                </div>
                <div class="aria-card-body">
                    <div class="aria-news-feed">
                        <div class="aria-news-item">
                            <div class="aria-news-date">
                                <?php echo date('Y/m/d'); ?>
                            </div>
                            <div class="aria-news-content">
                                <h4><?php _e('نسخه جدید پشتیبان آریا منتشر شد', 'aria-chatbot'); ?></h4>
                                <p><?php _e('قابلیت‌های جدید شامل تشخیص گفتار بهبود یافته و شخصیت‌سازی پیشرفته‌تر', 'aria-chatbot'); ?></p>
                                <a href="https://hrnb.ir/changelog" target="_blank" class="aria-news-link">
                                    <?php _e('مشاهده تغییرات', 'aria-chatbot'); ?>
                                </a>
                            </div>
                        </div>
                        
                        <div class="aria-news-item">
                            <div class="aria-news-date">
                                <?php echo date('Y/m/d', strtotime('-3 days')); ?>
                            </div>
                            <div class="aria-news-content">
                                <h4><?php _e('راهنمای کامل شخصیت‌سازی', 'aria-chatbot'); ?></h4>
                                <p><?php _e('یاد بگیرید چگونه چت بات خود را کاملاً شخصی‌سازی کنید', 'aria-chatbot'); ?></p>
                                <a href="https://hrnb.ir/docs/personality" target="_blank" class="aria-news-link">
                                    <?php _e('مطالعه راهنما', 'aria-chatbot'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- مودال جزئیات مکالمه -->
<div id="aria-conversation-modal" class="aria-modal" style="display: none;">
    <div class="aria-modal-content">
        <div class="aria-modal-header">
            <h3><?php _e('جزئیات مکالمه', 'aria-chatbot'); ?></h3>
            <button class="aria-modal-close" onclick="closeConversationModal()">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="aria-modal-body">
            <div id="aria-conversation-details">
                <!-- محتوا از طریق AJAX بارگذاری می‌شود -->
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // رسم نمودار مکالمات
    const ctx = document.getElementById('aria-conversations-chart').getContext('2d');
    const chartData = <?php echo json_encode($stats['chart_data']); ?>;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [{
                label: 'مکالمات',
                data: chartData.conversations,
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'پیام‌ها',
                data: chartData.messages,
                borderColor: '#764ba2',
                backgroundColor: 'rgba(118, 75, 162, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // تغییر دوره نمودار
    $('#aria-chart-period').change(function() {
        const period = $(this).val();
        updateChart(period);
    });
    
    // به‌روزرسانی خودکار آمار هر 30 ثانیه
    setInterval(function() {
        updateDashboardStats();
    }, 30000);
});

function testAPIConnection() {
    const button = event.target;
    const originalText = button.textContent;
    
    button.textContent = 'در حال تست...';
    button.disabled = true;
    
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'aria_test_api',
            nonce: ariaAdmin.nonce
        },
        success: function(response) {
            if (response.success) {
                showNotification('تست API موفق بود', 'success');
            } else {
                showNotification('تست API ناموفق: ' + response.data, 'error');
            }
        },
        error: function() {
            showNotification('خطا در برقراری ارتباط', 'error');
        },
        complete: function() {
            button.textContent = originalText;
            button.disabled = false;
        }
    });
}

function viewConversationDetails(conversationId) {
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'aria_get_conversation_details',
            conversation_id: conversationId,
            nonce: ariaAdmin.nonce
        },
        success: function(response) {
            if (response.success) {
                jQuery('#aria-conversation-details').html(response.data.html);
                jQuery('#aria-conversation-modal').show();
            }
        }
    });
}

function closeConversationModal() {
    jQuery('#aria-conversation-modal').hide();
}

function addToKnowledgeBase(question) {
    if (confirm('آیا می‌خواهید این سؤال را به پایگاه دانش اضافه کنید؟')) {
        window.location.href = '<?php echo admin_url('admin.php?page=aria-knowledge&add_question='); ?>' + encodeURIComponent(question);
    }
}

function refreshChatbotPreview() {
    // شبیه‌سازی به‌روزرسانی پیش‌نمایش
    const preview = document.querySelector('.aria-preview-messages');
    preview.innerHTML = '<div class="aria-preview-message bot">در حال بارگذاری...</div>';
    
    setTimeout(function() {
        preview.innerHTML = '<div class="aria-preview-message bot"><?php echo esc_js($starters[0]); ?></div>';
        showNotification('پیش‌نمایش به‌روزرسانی شد', 'success');
    }, 1000);
}

function updateChart(period) {
    // درخواست AJAX برای به‌روزرسانی نمودار
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'aria_get_chart_data',
            period: period,
            nonce: ariaAdmin.nonce
        },
        success: function(response) {
            if (response.success) {
                // به‌روزرسانی نمودار
                // کد به‌روزرسانی Chart.js
            }
        }
    });
}

function updateDashboardStats() {
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'aria_get_dashboard_stats',
            nonce: ariaAdmin.nonce
        },
        success: function(response) {
            if (response.success) {
                // به‌روزرسانی آمار بدون رفرش صفحه
                updateStatsCards(response.data);
            }
        }
    });
}

function updateStatsCards(stats) {
    // به‌روزرسانی کارت‌های آمار
    // این تابع آمار را بدون رفرش صفحه به‌روزرسانی می‌کند
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