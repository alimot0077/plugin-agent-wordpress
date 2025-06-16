<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('بررسی‌های امنیتی', 'aria-chatbot'); ?></h1>

    <table class="widefat striped">
        <thead>
        <tr>
            <th><?php _e('چک', 'aria-chatbot'); ?></th>
            <th><?php _e('وضعیت', 'aria-chatbot'); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($security_checks as $check => $result) : ?>
            <tr>
                <td><?php echo esc_html($check); ?></td>
                <td><?php echo $result ? '✅' : '❌'; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
