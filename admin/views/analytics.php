<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap aria-admin-wrap">
    <h1><?php _e('گزارش آمار', 'aria-chatbot'); ?></h1>
    <p class="description"><?php _e('نمایی کلی از استفاده کاربران از چت‌بات', 'aria-chatbot'); ?></p>

    <ul class="aria-summary">
        <li><?php _e('کل مکالمات:', 'aria-chatbot'); ?> <?php echo number_format($analytics_data['totals']['conversations']); ?></li>
        <li><?php _e('کل توکن‌ها:', 'aria-chatbot'); ?> <?php echo number_format($analytics_data['totals']['tokens']); ?></li>
    </ul>

    <canvas id="aria-analytics-chart" width="400" height="180"></canvas>
</div>
<script>
if (window.Chart) {
    const ctx = document.getElementById('aria-analytics-chart').getContext('2d');
    const data = <?php echo json_encode($analytics_data['chart']); ?>;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Conversations',
                data: data.conversations,
                backgroundColor: '#667eea'
            }]
        },
        options: {responsive: true, scales: {y: {beginAtZero:true}}}
    });
}
</script>
